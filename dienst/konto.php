<?php
/* Benutzerkonten und Zählung für den Umwandlungsdienst (von api.php und admin.php benutzt).
   Speicher: daten/konten.json  (E-Mail → Konto), daten/protokoll.csv (jede Umwandlung). */
if (!defined('GH_REPO')) require __DIR__ . '/config.php';
date_default_timezone_set(defined('ZEITZONE') ? ZEITZONE : 'Europe/Berlin');
$GLOBALS['DATA'] = __DIR__ . '/daten';
if (!is_dir($GLOBALS['DATA'])) { @mkdir($GLOBALS['DATA'], 0700, true); }
if (!is_file($GLOBALS['DATA'] . '/.htaccess')) @file_put_contents($GLOBALS['DATA'] . '/.htaccess', "Require all denied\nDeny from all\n");

/** Konten gesperrt lesen, ändern, schreiben. $fn bekommt das Array per Referenz; Rückgabe wird durchgereicht. */
function konten_tx(callable $fn) {
  $f = $GLOBALS['DATA'] . '/konten.json';
  $fp = fopen($f, 'c+'); flock($fp, LOCK_EX);
  $k = json_decode(stream_get_contents($fp) ?: '{}', true) ?: [];
  $res = $fn($k);
  ftruncate($fp, 0); rewind($fp); fwrite($fp, json_encode($k, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); fflush($fp);
  flock($fp, LOCK_UN); fclose($fp);
  return $res;
}
function konten_lesen() {
  $f = $GLOBALS['DATA'] . '/konten.json';
  return is_file($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
}
function mail_norm($e) { return strtolower(trim((string)$e)); }

function konto_neu($email, $name, $pw, $tarif = null) {
  return [
    'name' => trim((string)$name), 'email' => $email,
    'pw' => password_hash($pw, PASSWORD_DEFAULT), 'tv' => 1,
    'tarif' => $tarif ?: TARIF_NEU, 'tarif_bis' => '', 'limit_eigen' => null,
    'gesperrt' => false, 'notiz' => '',
    'monate' => [], 'gesamt' => 0, 'fehlgeschlagen' => 0,
    'erstellt' => date('Y-m-d H:i'), 'zuletzt' => '', 'fehlversuche' => 0, 'sperre_bis' => 0,
  ];
}

/** Gültiger Tarif (abgelaufene Zeit-Tarife fallen auf TARIF_NEU zurück) */
function tarif_von($k) {
  $t = $k['tarif'] ?? TARIF_NEU;
  if (!isset(TARIFE[$t])) $t = TARIF_NEU;
  if ($t !== TARIF_NEU && !empty($k['tarif_bis']) && $k['tarif_bis'] < date('Y-m-d')) $t = TARIF_NEU;
  return $t;
}
function limit_von($k) {
  if (isset($k['limit_eigen']) && $k['limit_eigen'] !== null && $k['limit_eigen'] !== '') return (int)$k['limit_eigen'];
  return (int)(TARIFE[tarif_von($k)]['limit'] ?? 0);
}
function konto_info($k) {
  $mon = date('Y-m'); $t = tarif_von($k);
  return [
    'name' => $k['name'], 'email' => $k['email'],
    'tarif' => $t, 'tarif_name' => TARIFE[$t]['name'] ?? $t,
    'tarif_bis' => ($t === ($k['tarif'] ?? '') ? ($k['tarif_bis'] ?? '') : ''),
    'genutzt' => (int)($k['monate'][$mon] ?? 0), 'limit' => limit_von($k), 'gesamt' => (int)($k['gesamt'] ?? 0),
    'pro_link' => PRO_LINK, 'pro_text' => PRO_TEXT,
    'admin' => defined('ADMIN_EMAIL') && ADMIN_EMAIL !== '' && mail_norm(ADMIN_EMAIL) === $k['email'],
  ];
}

/* Anmelde-Schlüssel (Token) ohne Datenbank: E-Mail | Ablauf | Version, signiert mit SERVER_KEY.
   Passwort ändern oder „überall abmelden“ erhöht die Version → alte Schlüssel ungültig. */
function token_neu($email, $tv) {
  $exp = time() + 365 * 86400;
  $p = rtrim(strtr(base64_encode($email . '|' . $exp . '|' . $tv), '+/', '-_'), '=');
  return $p . '.' . substr(hash_hmac('sha256', 'tok|' . $p, SERVER_KEY), 0, 40);
}
function token_pruefen($tok) {
  if (!is_string($tok) || !preg_match('/^([A-Za-z0-9_-]+)\.([a-f0-9]{40})$/', $tok, $m)) return null;
  if (!hash_equals(substr(hash_hmac('sha256', 'tok|' . $m[1], SERVER_KEY), 0, 40), $m[2])) return null;
  $raw = base64_decode(strtr($m[1], '-_', '+/'));
  $p = explode('|', (string)$raw);
  if (count($p) !== 3 || (int)$p[1] < time()) return null;
  $k = konten_lesen()[$p[0]] ?? null;
  if (!$k || (int)($k['tv'] ?? 1) !== (int)$p[2]) return null;
  return $k;
}

function protokoll($email, $job, $ereignis, $titel = '') {
  $f = $GLOBALS['DATA'] . '/protokoll.csv';
  $neu = !is_file($f);
  $fp = fopen($f, 'a'); flock($fp, LOCK_EX);
  if ($neu) fputcsv($fp, ['zeit', 'email', 'auftrag', 'ereignis', 'titel'], ';');
  fputcsv($fp, [date('Y-m-d H:i:s'), $email, $job, $ereignis, mb_substr(str_replace(["\r", "\n"], ' ', (string)$titel), 0, 80)], ';');
  flock($fp, LOCK_UN); fclose($fp);
}

/** Eine Umwandlung zählen (+1) oder bei Fehlschlag gutschreiben (-1). */
function zaehlen($email, $delta, $mon = null) {
  $mon = $mon ?: date('Y-m');
  konten_tx(function (&$K) use ($email, $delta, $mon) {
    if (!isset($K[$email])) return;
    $n = (int)($K[$email]['monate'][$mon] ?? 0) + $delta;
    $K[$email]['monate'][$mon] = max(0, $n);
    $K[$email]['gesamt'] = max(0, (int)($K[$email]['gesamt'] ?? 0) + $delta);
    if ($delta < 0) $K[$email]['fehlgeschlagen'] = (int)($K[$email]['fehlgeschlagen'] ?? 0) + 1;
    if ($delta > 0) $K[$email]['zuletzt'] = date('Y-m-d H:i');
  });
}
