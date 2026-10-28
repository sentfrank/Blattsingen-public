<?php
/* Noten-Umwandlungsdienst für die Blattsingen-App
   App  → upload  : PDF annehmen, Umwandlung bei GitHub starten
   GitHub → pdf   : PDF abholen (nur mit Schlüssel)
   GitHub → status: Fortschritt und Ergebnis melden (nur mit Schlüssel)
   App  → status / file : Fortschritt und MusicXML abholen
   App  → satz    : Aufnahme annehmen (nur mit Konto), A-cappella-Satz bei GitHub starten (satz.yml);
   GitHub → audio / satzpdf: Aufnahme abholen, Notenbild abliefern
   Konten (ZUGANG = 'konto'): registrieren, login, konto, passwort, abmelden – Zählung je Konto und Monat,
   fehlgeschlagene Umwandlungen werden wieder gutgeschrieben. Verwaltung: admin.php
   PDFs werden nach der Umwandlung gelöscht, alle Auftragsdaten nach 3 Tagen. */
require __DIR__ . '/config.php';
require __DIR__ . '/konto.php';
$DATA = $GLOBALS['DATA'];

header('Access-Control-Allow-Origin: ' . ALLOW_ORIGIN);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

function out($code, $data) { http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function job_ok($j) { return is_string($j) && preg_match('/^[a-f0-9]{24}$/', $j); }
function sig($j) { return hash_hmac('sha256', $j, SERVER_KEY); }
function need_sig($j) { if (!hash_equals(sig($j), (string)($_GET['k'] ?? ''))) out(403, ['fehler' => 'Schlüssel falsch']); }
function cleanup($DATA) {
  foreach (glob("$DATA/*.*") as $f) {
    if (!preg_match('/^[a-f0-9]{24}\.(pdf|json|musicxml|owner|abgebrochen|audio|satzpdf)$/', basename($f))) continue;   // nur Auftragsdateien
    if (filemtime($f) < time() - 3 * 86400) @unlink($f);
  }
}
function gh_dispatch($job, $text = true, $wf = 'server.yml', $extra = []) {
  $url = (defined('GH_API') ? GH_API : 'https://api.github.com') . '/repos/' . GH_REPO . '/actions/workflows/' . $wf . '/dispatches';
  $self = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . strtok($_SERVER['REQUEST_URI'] ?? '/api.php', '?');
  $inputs = ['job' => $job, 'dienst' => $self]; if (!$text) $inputs['text'] = '0';   // dienst: Rückmeldungen an genau diesen Dienst
  $inputs = array_merge($inputs, $extra);
  $body = json_encode(['ref' => 'main', 'inputs' => $inputs]);
  $h = ['Authorization: Bearer ' . GH_TOKEN, 'Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2022-11-28', 'User-Agent: blattsingen-server', 'Content-Type: application/json'];
  if (function_exists('curl_init')) {
    $c = curl_init($url);
    curl_setopt_array($c, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $h, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $r = curl_exec($c); $code = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
  } else {
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => implode("\r\n", $h), 'content' => $body, 'ignore_errors' => true, 'timeout' => 20]]);
    $r = @file_get_contents($url, false, $ctx); $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int)$m[1];
  }
  return [$code, $r];
}

$a = $_GET['a'] ?? '';
$job = $_GET['job'] ?? '';

if ($a === 'ping') out(200, ['ok' => true, 'dienst' => 'blattsingen', 'zugang' => ZUGANG, 'registrieren' => SELBST_REGISTRIEREN,
  'code' => EINLADUNGSCODE !== '', 'limit' => ZUGANG === 'konto' ? (TARIFE[TARIF_NEU]['limit'] ?? 0) : LIMIT_PER_MONTH,
  'pro_link' => PRO_LINK, 'pro_text' => PRO_TEXT,
  'sync' => ZUGANG === 'konto' && (!defined('SYNC_AN') || SYNC_AN), 'sync_mb' => defined('SYNC_MB') ? (int)SYNC_MB : 300,
  'satz' => ZUGANG === 'konto' && (!defined('SATZ_AN') || SATZ_AN), 'satz_mb' => defined('SATZ_MB') ? (int)SATZ_MB : 40]);

function post($n) { return (string)($_POST[$n] ?? ''); }
function nur_post() { if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') out(405, ['fehler' => 'Nur POST']); }
function konto_aus_token() {
  $k = token_pruefen(post('tok'));
  if (!$k) out(401, ['fehler' => 'Bitte melde dich (wieder) an.', 'anmelden' => true]);
  if (!empty($k['gesperrt'])) out(403, ['fehler' => 'Dein Konto ist gesperrt. Bitte wende dich an den Betreiber.']);
  return $k;
}
require __DIR__ . '/sync.php';   // Stücke und Projekte geräteübergreifend (sync_*)

if ($a === 'registrieren') {
  nur_post();
  if (ZUGANG !== 'konto' || !SELBST_REGISTRIEREN) out(403, ['fehler' => 'Konten legt der Betreiber an. Bitte frag dort nach einem Zugang.']);
  $email = mail_norm(post('email')); $name = trim(post('name')); $pw = post('pw');
  if (EINLADUNGSCODE !== '' && !hash_equals(strtolower(EINLADUNGSCODE), strtolower(trim(post('code'))))) { usleep(800000); out(403, ['fehler' => 'Der Einladungscode stimmt nicht.']); }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(400, ['fehler' => 'Bitte eine gültige E-Mail-Adresse eingeben.']);
  if (mb_strlen($name) < 2) out(400, ['fehler' => 'Bitte deinen Namen eingeben.']);
  if (strlen($pw) < 8) out(400, ['fehler' => 'Das Passwort braucht mindestens 8 Zeichen.']);
  if (in_array($email, BLOCKED, true)) out(403, ['fehler' => 'Für diese E-Mail-Adresse ist die Umwandlung gesperrt.']);
  $k = konten_tx(function (&$K) use ($email, $name, $pw) {
    if (isset($K[$email])) return null;
    $K[$email] = konto_neu($email, $name, $pw); return $K[$email];
  });
  if (!$k) out(409, ['fehler' => 'Für diese E-Mail gibt es schon ein Konto. Bitte anmelden.']);
  protokoll($email, '', 'konto angelegt', $name);
  out(200, ['tok' => token_neu($email, $k['tv']), 'konto' => konto_info($k)]);
}

if ($a === 'login') {
  nur_post();
  $email = mail_norm(post('email')); $pw = post('pw');
  $r = konten_tx(function (&$K) use ($email, $pw) {
    if (!isset($K[$email])) return 'falsch';
    $k = &$K[$email];
    if (($k['sperre_bis'] ?? 0) > time()) return 'warten';
    if (!password_verify($pw, $k['pw'])) {
      $k['fehlversuche'] = (int)($k['fehlversuche'] ?? 0) + 1;
      if ($k['fehlversuche'] >= 5) { $k['sperre_bis'] = time() + 900; $k['fehlversuche'] = 0; }
      return 'falsch';
    }
    $k['fehlversuche'] = 0; $k['sperre_bis'] = 0;
    if (password_needs_rehash($k['pw'], PASSWORD_DEFAULT)) $k['pw'] = password_hash($pw, PASSWORD_DEFAULT);
    return $k;
  });
  if ($r === 'warten') out(429, ['fehler' => 'Zu viele Fehlversuche. Bitte in 15 Minuten noch einmal versuchen.']);
  if (!is_array($r)) { usleep(800000); out(403, ['fehler' => 'E-Mail oder Passwort stimmt nicht.']); }
  if (!empty($r['gesperrt'])) out(403, ['fehler' => 'Dein Konto ist gesperrt. Bitte wende dich an den Betreiber.']);
  out(200, ['tok' => token_neu($email, $r['tv']), 'konto' => konto_info($r)]);
}

if ($a === 'konto') { nur_post(); $k = konto_aus_token(); out(200, ['konto' => konto_info($k)]); }

if ($a === 'passwort') {
  nur_post(); $k = konto_aus_token(); $neu = post('neu');
  if (!password_verify(post('alt'), $k['pw'])) { usleep(800000); out(403, ['fehler' => 'Das bisherige Passwort stimmt nicht.']); }
  if (strlen($neu) < 8) out(400, ['fehler' => 'Das neue Passwort braucht mindestens 8 Zeichen.']);
  $email = $k['email'];
  $tv = konten_tx(function (&$K) use ($email, $neu) { $K[$email]['pw'] = password_hash($neu, PASSWORD_DEFAULT); $K[$email]['tv'] = (int)($K[$email]['tv'] ?? 1) + 1; return $K[$email]['tv']; });
  out(200, ['tok' => token_neu($email, $tv), 'ok' => true]);
}


// ---------- Verwaltung in der App (gleiche Schnittstelle wie der Cloudflare-Dienst) ----------
function admin_tok_neu() { $exp = time() + 12 * 3600; return 'adm.' . $exp . '.' . hash_hmac('sha256', 'admintok|' . $exp, SERVER_KEY . '|' . ADMIN_PASSWORT); }
function admin_tok_ok($t) {
  if (!preg_match('/^adm\.(\d+)\.([a-f0-9]{64})$/', (string)$t, $m) || (int)$m[1] < time()) return false;
  return hash_equals(hash_hmac('sha256', 'admintok|' . $m[1], SERVER_KEY . '|' . ADMIN_PASSWORT), $m[2]);
}
function admin_konten() {
  $mon = date('Y-m'); $out = [];
  foreach (konten_lesen() as $k) {
    $t = tarif_von($k);
    $out[] = ['name' => $k['name'], 'email' => $k['email'], 'tarif' => $k['tarif'] ?? TARIF_NEU, 'tarif_gilt' => $t, 'tarif_bis' => $k['tarif_bis'] ?? '',
      'limit_eigen' => $k['limit_eigen'] ?? null, 'limit' => limit_von($k), 'gesperrt' => !empty($k['gesperrt']), 'notiz' => $k['notiz'] ?? '',
      'monat' => (int)($k['monate'][$mon] ?? 0), 'gesamt' => (int)($k['gesamt'] ?? 0), 'fehlgeschlagen' => (int)($k['fehlgeschlagen'] ?? 0),
      'erstellt' => $k['erstellt'] ?? '', 'zuletzt' => $k['zuletzt'] ?? ''];
  }
  usort($out, fn($a, $b) => strcmp($b['zuletzt'], $a['zuletzt']) ?: strcmp($a['email'], $b['email']));
  return $out;
}
function admin_protokoll_lesen($max) {
  $f = $GLOBALS['DATA'] . '/protokoll.csv'; if (!is_file($f)) return [];
  $lines = file($f, FILE_IGNORE_NEW_LINES); $rows = [];
  foreach (array_slice($lines, 1) as $l) { $c = str_getcsv($l, ';'); if (count($c) >= 5) $rows[] = ['zeit' => $c[0], 'email' => $c[1], 'job' => $c[2], 'ereignis' => $c[3], 'titel' => $c[4]]; }
  return array_slice(array_reverse($rows), 0, $max);
}
function admin_statistik() {
  $mon = date('Y-m'); $seit = date('Y-m-d', strtotime('-29 days')); $tage = [];
  foreach (admin_protokoll_lesen(100000) as $p) {
    $t = substr($p['zeit'], 0, 10); if ($t < $seit) continue;
    if (!isset($tage[$t])) $tage[$t] = ['tag' => $t, 'start' => 0, 'ok' => 0, 'err' => 0, 'neu' => 0, 'nutzer' => []];
    $e = $p['ereignis'];
    if ($e === 'gestartet') { $tage[$t]['start']++; $tage[$t]['nutzer'][$p['email']] = 1; }
    elseif ($e === 'fertig') $tage[$t]['ok']++;
    elseif (strpos($e, 'fehler') === 0 || strpos($e, 'start fehlgeschlagen') === 0) $tage[$t]['err']++;
    elseif (strpos($e, 'konto angelegt') === 0) $tage[$t]['neu']++;
  }
  krsort($tage); $liste = [];
  foreach ($tage as $x) { $x['nutzer'] = count($x['nutzer']); $liste[] = $x; }
  $K = konten_lesen(); $aktiv = 0; $summe = 0; $top = [];
  foreach ($K as $k) { $n = (int)($k['monate'][$mon] ?? 0); if ($n) { $aktiv++; $summe += $n; $top[] = ['name' => $k['name'], 'email' => $k['email'], 'n' => $n]; } }
  usort($top, fn($a, $b) => $b['n'] <=> $a['n']);
  return ['monat' => $mon, 'konten' => count($K), 'aktiv' => $aktiv, 'umwandlungen_monat' => $summe, 'heute' => $tage[date('Y-m-d')]['start'] ?? 0, 'tage' => $liste, 'top' => array_slice($top, 0, 10)];
}
if (strpos($a, 'admin_') === 0) {
  nur_post();
  if (strlen(ADMIN_PASSWORT) < 10 || ADMIN_PASSWORT === 'HIER_EIN_ADMIN_PASSWORT') out(403, ['fehler' => 'In config.php ist noch kein ADMIN_PASSWORT (mindestens 10 Zeichen) eingetragen.']);
  if ($a === 'admin_login') {
    $sf = $DATA . '/admin-sperre.json'; $sp = is_file($sf) ? (json_decode(file_get_contents($sf), true) ?: []) : [];
    if (($sp['bis'] ?? 0) > time()) out(429, ['fehler' => 'Zu viele Fehlversuche – bitte 15 Minuten warten.']);
    if (!hash_equals(ADMIN_PASSWORT, post('pw'))) { $sp['n'] = ($sp['n'] ?? 0) + 1; if ($sp['n'] >= 5) $sp = ['n' => 0, 'bis' => time() + 900]; file_put_contents($sf, json_encode($sp)); sleep(1); out(403, ['fehler' => 'Admin-Passwort falsch.']); }
    @unlink($sf); out(200, ['atok' => admin_tok_neu()]);
  }
  if (!admin_tok_ok(post('atok'))) out(401, ['fehler' => 'Bitte als Admin anmelden.', 'admin_anmelden' => true]);
  if ($a === 'admin_daten') {
    out(200, ['tarife' => TARIFE, 'tarif_neu' => TARIF_NEU, 'einstellungen' => ['zugang' => ZUGANG, 'registrieren' => SELBST_REGISTRIEREN, 'code' => EINLADUNGSCODE !== ''],
      'konten' => admin_konten(), 'statistik' => admin_statistik(), 'cloudflare' => post('cf') ? ['strato' => true] : null]);
  }
  if ($a === 'admin_konverter') {   // Konverter-Repository auf den Stand der App bringen (tools/*.py, Workflows)
    // Dateien kommen base64-kodiert (Python-Quelltext im Klartext blockt die Firewall mancher Hoster mit 403)
    $files = json_decode(post('dateien'), true); $write = post('schreiben') === '1';
    if (post('dateien_b64') !== '') { $files = []; foreach ((json_decode(post('dateien_b64'), true) ?: []) as $k => $v) $files[$k] = base64_decode((string)$v); }
    if (!is_array($files) || !$files) out(400, ['fehler' => 'Keine Dateien übergeben.']);
    $api = (defined('GH_API') ? GH_API : 'https://api.github.com') . '/repos/' . GH_REPO . '/contents/';
    $gh = function ($method, $url, $body = null) {
      $h = ['Authorization: Bearer ' . GH_TOKEN, 'Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2022-11-28', 'User-Agent: blattsingen-server'];
      if ($body !== null) $h[] = 'Content-Type: application/json';
      $c = curl_init($url);
      curl_setopt_array($c, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $h, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
      if ($body !== null) curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($body));
      $r = curl_exec($c); $code = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
      return [$code, json_decode((string)$r, true)];
    };
    $res = []; $fehlerRecht = false;
    foreach ($files as $path => $inhalt) {
      if (!is_string($path) || !preg_match('#^(\.github/workflows/[a-z0-9_-]+\.yml|tools/[a-z0-9_]+\.py)$#', $path) || !is_string($inhalt)) { $res[] = ['datei' => (string)$path, 'stand' => 'abgelehnt']; continue; }
      [$code, $j] = $gh('GET', $api . $path . '?ref=main');
      $sha = null; $alt = null;
      if ($code === 200) { $sha = $j['sha'] ?? null; $alt = base64_decode(str_replace("\n", '', (string)($j['content'] ?? ''))); }
      elseif ($code !== 404) { $res[] = ['datei' => $path, 'stand' => 'Fehler beim Lesen (GitHub ' . $code . (isset($j['message']) ? ': ' . substr((string)$j['message'], 0, 120) : '') . ')']; if ($code === 403 || $code === 401) $fehlerRecht = true; continue; }
      if ($alt === $inhalt) { $res[] = ['datei' => $path, 'stand' => 'aktuell']; continue; }
      if (!$write) { $res[] = ['datei' => $path, 'stand' => $sha ? 'veraltet' : 'fehlt']; continue; }
      $body = ['message' => 'Konverter aktualisiert (Blattsingen-Verwaltung)', 'content' => base64_encode($inhalt), 'branch' => 'main'];
      if ($sha) $body['sha'] = $sha;
      [$code2, $j2] = $gh('PUT', $api . $path, $body);
      if ($code2 === 200 || $code2 === 201) $res[] = ['datei' => $path, 'stand' => 'aktualisiert'];
      else { $res[] = ['datei' => $path, 'stand' => 'Fehler beim Schreiben (GitHub ' . $code2 . (isset($j2['message']) ? ': ' . substr((string)$j2['message'], 0, 120) : '') . ')']; if (in_array($code2, [401, 403, 404], true)) $fehlerRecht = true; }
    }
    out(200, ['dateien' => $res, 'hinweis' => $fehlerRecht ? 'Der GitHub-Schlüssel (GH_TOKEN) braucht für das Aktualisieren zusätzlich die Rechte „Contents: Read and write“ und „Workflows: Read and write“ für ' . GH_REPO . '.' : '']);
  }
  if ($a === 'admin_protokoll') out(200, ['protokoll' => admin_protokoll_lesen(min(5000, max(1, (int)post('n') ?: 200)))]);
  if ($a === 'admin_konto') {
    $act = post('act'); $email = mail_norm(post('email'));
    $t = post('tarif'); if (!isset(TARIFE[$t])) $t = TARIF_NEU; $bis = post('bis');
    if ($bis !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $bis)) out(400, ['fehler' => 'Datum bitte als JJJJ-MM-TT.']);
    if ($act === 'neu') {
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(400, ['fehler' => 'E-Mail ungültig.']);
      $pw = post('pw'); if ($pw === '') $pw = substr(strtr(base64_encode(random_bytes(9)), '+/=', 'xyz'), 0, 10);
      if (strlen($pw) < 8) out(400, ['fehler' => 'Passwort zu kurz (mindestens 8 Zeichen).']);
      $ok = konten_tx(function (&$K) use ($email, $pw, $t, $bis) { if (isset($K[$email])) return false; $K[$email] = konto_neu($email, post('name'), $pw, $t); $K[$email]['tarif_bis'] = $bis; return true; });
      if (!$ok) out(409, ['fehler' => 'Diese E-Mail hat schon ein Konto.']);
      protokoll($email, '', 'konto angelegt (admin)', post('name'));
      out(200, ['ok' => true, 'meldung' => "Konto angelegt. Zugangsdaten: $email · Passwort: $pw", 'konten' => admin_konten()]);
    }
    if (!isset(konten_lesen()[$email])) out(404, ['fehler' => 'Konto nicht gefunden.']);
    if ($act === 'speichern') {
      $pw = post('pw'); $lim = trim(post('limit_eigen'));
      if ($pw !== '' && strlen($pw) < 8) out(400, ['fehler' => 'Neues Passwort zu kurz (mindestens 8 Zeichen).']);
      konten_tx(function (&$K) use ($email, $t, $bis, $lim, $pw) {
        $k = &$K[$email];
        $k['name'] = trim(post('name')); $k['tarif'] = $t; $k['tarif_bis'] = $bis; $k['limit_eigen'] = $lim === '' ? null : max(0, (int)$lim);
        $k['gesperrt'] = in_array(post('gesperrt'), ['1', 'true'], true); $k['notiz'] = mb_substr(post('notiz'), 0, 300);
        if ($pw !== '') { $k['pw'] = password_hash($pw, PASSWORD_DEFAULT); $k['tv'] = (int)($k['tv'] ?? 1) + 1; $k['sperre_bis'] = 0; $k['fehlversuche'] = 0; }
      });
      out(200, ['ok' => true, 'meldung' => 'Gespeichert' . ($pw !== '' ? " – neues Passwort: $pw" : '') . '.', 'konten' => admin_konten()]);
    }
    if ($act === 'loeschen') {
      konten_tx(function (&$K) use ($email) { unset($K[$email]); });
      protokoll($email, '', 'konto gelöscht (admin)');
      out(200, ['ok' => true, 'meldung' => 'Konto gelöscht.', 'konten' => admin_konten()]);
    }
  }
  out(400, ['fehler' => 'unbekannte Aktion']);
}

if ($a === 'tarif') {   // für spätere automatische Bezahlung (Zahlungsanbieter, Zapier, Make …)
  nur_post();
  if (ZAHLUNG_KEY === '' || !hash_equals(ZAHLUNG_KEY, post('key'))) { usleep(800000); out(403, ['fehler' => 'nicht erlaubt']); }
  $email = mail_norm(post('email')); $t = post('tarif'); $bis = post('bis');
  if (!isset(TARIFE[$t])) out(400, ['fehler' => 'Tarif unbekannt']);
  if ($bis !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $bis)) out(400, ['fehler' => 'bis bitte als JJJJ-MM-TT']);
  $ok = konten_tx(function (&$K) use ($email, $t, $bis) { if (!isset($K[$email])) return false; $K[$email]['tarif'] = $t; $K[$email]['tarif_bis'] = $bis; return true; });
  if (!$ok) out(404, ['fehler' => 'Konto nicht gefunden']);
  protokoll($email, '', 'tarif ' . $t . ($bis ? ' bis ' . $bis : ''), 'automatisch');
  out(200, ['ok' => true]);
}

function gh_fehler($code, $r, $email, $job, $titel, $wf) {
  $ghm = substr((string)(json_decode((string)$r, true)['message'] ?? ''), 0, 120);
  $tipp = [401 => 'GH_TOKEN in config.php ungültig oder abgelaufen',
           403 => 'GH_TOKEN hat keine Berechtigung „Actions: Read and write“ für ' . GH_REPO,
           404 => 'GH_REPO falsch, Token hat keinen Zugriff auf dieses Repository oder ' . $wf . ' fehlt im Branch main (Admin → Konverter aktualisieren)',
           422 => $wf . ' im Konverter-Repository ist veraltet (Admin → Konverter aktualisieren)'][$code] ?? '';
  protokoll($email, $job, 'start fehlgeschlagen (GitHub ' . $code . ($ghm ? ': ' . $ghm : '') . ')', $titel);
  out(502, ['fehler' => 'Der Auftrag konnte nicht gestartet werden (GitHub ' . $code . ($ghm ? ': ' . $ghm : '') . ').'
    . ($tipp ? ' Hinweis für den Betreiber: ' . $tipp . '.' : ' Bitte dem Betreiber Bescheid geben.')]);
}

if ($a === 'satz' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {   // Song → A-cappella-Satz (nur mit Konto)
  cleanup($DATA);
  if (ZUGANG !== 'konto' || (defined('SATZ_AN') && !SATZ_AN)) out(403, ['fehler' => 'Diese Funktion ist auf diesem Dienst nicht eingeschaltet.']);
  $kto = konto_aus_token(); $email = $kto['email'];
  if (in_array($email, BLOCKED, true)) out(403, ['fehler' => 'Für diese E-Mail-Adresse ist die Funktion gesperrt.']);
  $mb = defined('SATZ_MB') ? (int)SATZ_MB : 40;
  if (empty($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) out(400, ['fehler' => 'Die Aufnahme ist nicht angekommen (zu groß oder Verbindung unterbrochen).']);
  if ($_FILES['audio']['size'] > $mb * 1048576) out(413, ['fehler' => 'Die Aufnahme ist größer als ' . $mb . ' MB.']);
  if ($_FILES['audio']['size'] < 2000) out(400, ['fehler' => 'Die Aufnahme ist zu kurz.']);
  $head = file_get_contents($_FILES['audio']['tmp_name'], false, null, 0, 12);
  $ok = substr($head, 0, 4) === 'RIFF' || substr($head, 0, 3) === 'ID3' || (ord($head[0]) === 0xFF && (ord($head[1]) & 0xE0) === 0xE0)
     || substr($head, 4, 4) === 'ftyp' || substr($head, 0, 4) === 'OggS' || substr($head, 0, 4) === 'fLaC' || substr($head, 0, 4) === "\x1A\x45\xDF\xA3";
  if (!$ok) out(400, ['fehler' => 'Das Format wird nicht erkannt. Möglich sind MP3, M4A/AAC, WAV, OGG, FLAC oder WebM.']);
  $bes = strtoupper(post('besetzung')); if (!in_array($bes, ['SATB', 'SSAA', 'TTBB', 'SAB', 'SSA', 'TTB', 'SSATB', 'SATBB'], true)) $bes = 'SATB';
  $mel = preg_replace('/[^A-Z0-9]/', '', strtoupper(post('melodie'))); $mel = substr($mel, 0, 3);
  $stil = post('stil') === 'rhythmisch' ? 'rhythmisch' : 'gehalten';
  $quelle = in_array(post('quelle'), ['song', 'gesang'], true) ? post('quelle') : 'auto';
  $titel = trim((string)preg_replace('/[\x00-\x1F"`$\\\\]/u', '', post('titel')));
  $titel = function_exists('mb_substr') ? mb_substr($titel, 0, 80) : substr($titel, 0, 80);
  $mon = date('Y-m');
  [$ok, $n1, $lim] = konten_tx(function (&$K) use ($email, $mon) {
    $k = &$K[$email]; $lim = limit_von($k); $n = (int)($k['monate'][$mon] ?? 0);
    if ($lim > 0 && $n >= $lim) return [false, $n, $lim];
    $k['monate'][$mon] = $n + 1; $k['gesamt'] = (int)($k['gesamt'] ?? 0) + 1; $k['zuletzt'] = date('Y-m-d H:i');
    return [true, $n + 1, $lim];
  });
  if (!$ok) out(429, ['fehler' => 'Dein Kontingent von ' . $lim . ' Aufträgen in diesem Monat ist aufgebraucht.', 'kontingent' => true, 'pro_link' => PRO_LINK, 'pro_text' => PRO_TEXT]);
  $job = bin2hex(random_bytes(12));
  move_uploaded_file($_FILES['audio']['tmp_name'], "$DATA/$job.audio");
  file_put_contents("$DATA/$job.json", json_encode(['status' => 'läuft', 'schritt' => 'Wartet, bis die Analyse startet', 'zeit' => time(), 'art' => 'satz'], JSON_UNESCAPED_UNICODE));
  file_put_contents("$DATA/$job.owner", json_encode(['email' => $email, 'monat' => $mon, 'titel' => 'Satz: ' . $titel, 'konto' => true, 'art' => 'satz'], JSON_UNESCAPED_UNICODE));
  [$code, $r] = gh_dispatch($job, true, 'satz.yml', ['besetzung' => $bes, 'melodie' => $mel, 'stil' => $stil, 'quelle' => $quelle, 'titel' => $titel]);
  if ($code !== 204) {
    @unlink("$DATA/$job.audio"); @unlink("$DATA/$job.json"); @unlink("$DATA/$job.owner");
    zaehlen($email, -1, $mon);
    gh_fehler($code, $r, $email, $job, 'Satz: ' . $titel, 'satz.yml');
  }
  protokoll($email, $job, 'Satz gestartet (' . $bes . ')', 'Satz: ' . $titel);
  out(200, ['job' => $job, 'genutzt' => $n1, 'limit' => $lim, 'konto' => konto_info(konten_lesen()[$email])]);
}

if ($a === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  cleanup($DATA);
  $kto = null;
  if (ZUGANG === 'konto') { $kto = konto_aus_token(); $email = $kto['email']; }
  else {
    $email = mail_norm($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(403, ['fehler' => 'Bitte gib zuerst oben deinen Namen und deine E-Mail an, dann klappt die Umwandlung.', 'email_fehlt' => true]);
  }
  if ($email && in_array($email, BLOCKED, true)) out(403, ['fehler' => 'Für diese E-Mail-Adresse ist die Umwandlung gesperrt.']);
  if (empty($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) out(400, ['fehler' => 'Die PDF ist nicht angekommen (zu groß oder Verbindung unterbrochen).']);
  if ($_FILES['pdf']['size'] > MAX_MB * 1048576) out(413, ['fehler' => 'Die PDF ist größer als ' . MAX_MB . ' MB.']);
  $head = file_get_contents($_FILES['pdf']['tmp_name'], false, null, 0, 5);
  if ($head !== '%PDF-') out(400, ['fehler' => 'Das ist keine PDF-Datei.']);
  $mon = date('Y-m');
  if ($kto) {   // Kontingent je Konto: prüfen und zählen in einem Schritt
    $r = konten_tx(function (&$K) use ($email, $mon) {
      $k = &$K[$email]; $lim = limit_von($k); $n = (int)($k['monate'][$mon] ?? 0);
      if ($lim > 0 && $n >= $lim) return [false, $n, $lim];
      $k['monate'][$mon] = $n + 1; $k['gesamt'] = (int)($k['gesamt'] ?? 0) + 1; $k['zuletzt'] = date('Y-m-d H:i');
      return [true, $n + 1, $lim];
    });
    [$ok, $n1, $lim] = $r;
    if (!$ok) out(429, ['fehler' => 'Dein Kontingent von ' . $lim . ' Umwandlungen in diesem Monat ist aufgebraucht.', 'kontingent' => true, 'pro_link' => PRO_LINK, 'pro_text' => PRO_TEXT]);
  } else {      // alte Zählung je E-Mail
    $uf = "$DATA/nutzung.json"; $fp = fopen($uf, 'c+'); flock($fp, LOCK_EX);
    $use = json_decode(stream_get_contents($fp) ?: '{}', true) ?: [];
    $n = $use[$mon][$email] ?? 0; $lim = LIMIT_PER_MONTH;
    if ($lim > 0 && $n >= $lim) { flock($fp, LOCK_UN); fclose($fp); out(429, ['fehler' => 'Dein Kontingent von ' . $lim . ' Umwandlungen in diesem Monat ist aufgebraucht.']); }
    $use[$mon][$email] = $n + 1; $n1 = $n + 1;
    foreach (array_keys($use) as $m) if ($m < date('Y-m', strtotime('-3 months'))) unset($use[$m]);
    ftruncate($fp, 0); rewind($fp); fwrite($fp, json_encode($use)); fflush($fp); flock($fp, LOCK_UN); fclose($fp);
  }
  $job = bin2hex(random_bytes(12));
  move_uploaded_file($_FILES['pdf']['tmp_name'], "$DATA/$job.pdf");
  file_put_contents("$DATA/$job.json", json_encode(['status' => 'läuft', 'schritt' => 'Wartet, bis die Erkennung startet', 'zeit' => time()], JSON_UNESCAPED_UNICODE));
  $titel = (string)($_POST['titel'] ?? '');
  file_put_contents("$DATA/$job.owner", json_encode(['email' => $email, 'monat' => $mon, 'titel' => $titel, 'konto' => (bool)$kto], JSON_UNESCAPED_UNICODE));
  [$code, $r] = gh_dispatch($job, ($_POST['text'] ?? '1') !== '0');
  if ($code !== 204) {
    @unlink("$DATA/$job.pdf"); @unlink("$DATA/$job.json"); @unlink("$DATA/$job.owner");
    if ($kto) zaehlen($email, -1, $mon);
    $ghm = substr((string)(json_decode((string)$r, true)['message'] ?? ''), 0, 120);
    $tipp = [401 => 'GH_TOKEN in config.php ungültig oder abgelaufen',
             403 => 'GH_TOKEN hat keine Berechtigung „Actions: Read and write“ für ' . GH_REPO,
             404 => 'GH_REPO falsch, Token hat keinen Zugriff auf dieses Repository oder server.yml fehlt im Branch main',
             422 => 'server.yml im Konverter-Repository ist veraltet (Eingabe „dienst“ fehlt)'][$code] ?? '';
    protokoll($email, $job, 'start fehlgeschlagen (GitHub ' . $code . ($ghm ? ': ' . $ghm : '') . ')', $titel);
    out(502, ['fehler' => 'Die Umwandlung konnte nicht gestartet werden (GitHub ' . $code . ($ghm ? ': ' . $ghm : '') . ').'
      . ($tipp ? ' Hinweis für den Betreiber: ' . $tipp . '.' : ' Bitte dem Betreiber Bescheid geben.')]);
  }
  protokoll($email, $job, 'gestartet', $titel);
  $info = $kto ? konto_info(konten_lesen()[$email]) : null;
  out(200, ['job' => $job, 'genutzt' => $n1, 'limit' => $lim, 'konto' => $info]);
}

if (!job_ok($job)) out(400, ['fehler' => 'Auftrag unbekannt']);

if ($a === 'abbrechen' && $_SERVER['REQUEST_METHOD'] === 'POST') {   // App bricht eine Umwandlung ab
  $of = "$DATA/$job.owner"; $o = is_file($of) ? json_decode((string)file_get_contents($of), true) : null;
  if (!$o && !is_file("$DATA/$job.json")) out(404, ['fehler' => 'Auftrag nicht gefunden (abgelaufen?)']);
  if ($o && !empty($o['konto'])) { $k = token_pruefen($_POST['tok'] ?? ''); if (!$k || $k['email'] !== $o['email']) out(403, ['fehler' => 'Nur wer die Umwandlung gestartet hat, kann sie abbrechen.']); }
  touch("$DATA/$job.abgebrochen"); @unlink("$DATA/$job.pdf"); @unlink("$DATA/$job.audio");
  file_put_contents("$DATA/$job.json", json_encode(['status' => 'fehler', 'meldung' => 'Umwandlung abgebrochen.', 'abgebrochen' => true], JSON_UNESCAPED_UNICODE));
  if ($o) { @unlink($of); if (!empty($o['konto'])) zaehlen($o['email'], -1, $o['monat']); protokoll($o['email'], $job, 'abgebrochen', $o['titel'] ?? ''); }
  // laufenden GitHub-Lauf abbrechen (run-name enthält die Auftragsnummer)
  $api = (defined('GH_API') ? GH_API : 'https://api.github.com') . '/repos/' . GH_REPO;
  $h = ['Authorization: Bearer ' . GH_TOKEN, 'Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2022-11-28', 'User-Agent: blattsingen-server'];
  if (function_exists('curl_init')) foreach (['server.yml', 'satz.yml'] as $wf) {
    $c = curl_init("$api/actions/workflows/$wf/runs?per_page=30"); curl_setopt_array($c, [CURLOPT_HTTPHEADER => $h, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $runs = json_decode((string)curl_exec($c), true); curl_close($c);
    foreach (($runs['workflow_runs'] ?? []) as $r) {
      if (strpos((string)($r['display_title'] ?? ''), $job) !== false && ($r['status'] ?? '') !== 'completed') {
        $c = curl_init("$api/actions/runs/{$r['id']}/cancel"); curl_setopt_array($c, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => '', CURLOPT_HTTPHEADER => $h, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]); curl_exec($c); curl_close($c);
      }
    }
  }
  out(200, ['ok' => true]);
}
if (is_file("$DATA/$job.abgebrochen") && in_array($a, ['pdf', 'audio', 'report', 'ergebnis'], true) && !($a === 'satzpdf')) out(410, ['fehler' => 'abgebrochen']);

if ($a === 'pdf') {           // GitHub holt die PDF
  need_sig($job);
  if (!is_file("$DATA/$job.pdf")) out(404, ['fehler' => 'keine PDF']);
  header('Content-Type: application/pdf'); readfile("$DATA/$job.pdf"); exit;
}
if ($a === 'satzpdf') {       // Notenbild des Satzes: GitHub schickt es (POST mit Schlüssel), die App holt es ab
  if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    need_sig($job);
    $raw = file_get_contents('php://input');
    if (substr($raw, 0, 5) !== '%PDF-') out(400, ['fehler' => 'keine PDF']);
    file_put_contents("$DATA/$job.satzpdf", $raw); out(200, ['ok' => true]);
  }
  if (!is_file("$DATA/$job.satzpdf")) out(404, ['fehler' => 'kein Notenbild']);
  header('Content-Type: application/pdf'); readfile("$DATA/$job.satzpdf"); exit;
}
if ($a === 'audio') {         // GitHub holt die Aufnahme für den Satz
  need_sig($job);
  if (!is_file("$DATA/$job.audio")) out(404, ['fehler' => 'keine Aufnahme']);
  header('Content-Type: application/octet-stream'); readfile("$DATA/$job.audio"); exit;
}
if ($a === 'ergebnis' && $_SERVER['REQUEST_METHOD'] === 'POST') {   // GitHub schickt die MusicXML (eigene Sendung)
  need_sig($job);
  file_put_contents("$DATA/$job.musicxml", file_get_contents('php://input'));
  out(200, ['ok' => true]);
}
if ($a === 'report' && $_SERVER['REQUEST_METHOD'] === 'POST') {   // GitHub meldet Stand/Ergebnis
  need_sig($job);
  $d = json_decode(file_get_contents('php://input'), true);
  if (!is_array($d)) out(400, ['fehler' => 'kein JSON']);
  if (isset($d['musicxml'])) { file_put_contents("$DATA/$job.musicxml", $d['musicxml']); unset($d['musicxml']); }
  file_put_contents("$DATA/$job.json", json_encode($d, JSON_UNESCAPED_UNICODE));
  if (in_array($d['status'] ?? '', ['fertig', 'fehler'], true)) {
    @unlink("$DATA/$job.pdf"); @unlink("$DATA/$job.audio");
    $of = "$DATA/$job.owner";
    if (is_file($of) && ($o = json_decode((string)@file_get_contents($of), true))) {
      @unlink($of);   // nur einmal abrechnen
      if ($d['status'] === 'fehler' && !empty($o['konto'])) zaehlen($o['email'], -1, $o['monat']);   // Fehlschlag zählt nicht
      protokoll($o['email'], $job, $d['status'] === 'fertig' ? 'fertig' : 'fehler (gutgeschrieben)', $o['titel'] ?? '');
    }
  }
  out(200, ['ok' => true]);
}
if ($a === 'status') {
  if (!is_file("$DATA/$job.json")) out(404, ['fehler' => 'Auftrag nicht gefunden (abgelaufen?)']);
  header('Content-Type: application/json; charset=utf-8'); readfile("$DATA/$job.json"); exit;
}
if ($a === 'file') {
  if (!is_file("$DATA/$job.musicxml")) out(404, ['fehler' => 'noch kein Ergebnis']);
  header('Content-Type: application/vnd.recordare.musicxml+xml; charset=utf-8'); readfile("$DATA/$job.musicxml"); exit;
}
out(400, ['fehler' => 'unbekannte Aktion']);
