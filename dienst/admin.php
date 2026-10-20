<?php
/* Verwaltung des Umwandlungsdienstes: Konten, Tarife, Zählung, Protokoll.
   Aufruf im Browser: https://DEINE-DOMAIN.de/blattsingen-server/admin.php  (Passwort: ADMIN_PASSWORT in config.php) */
require __DIR__ . '/config.php';
require __DIR__ . '/konto.php';
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; style-src 'unsafe-inline'; script-src 'none'");
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
if (!empty($_SERVER['HTTPS'])) ini_set('session.cookie_secure', '1');
session_name('blattsingen_admin');
session_start();

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function weiter($msg = '', $q = '') { $_SESSION['msg'] = $msg; header('Location: admin.php' . $q); exit; }
$schwach = strlen(ADMIN_PASSWORT) < 10 || ADMIN_PASSWORT === 'HIER_EIN_ADMIN_PASSWORT';

// ---- Anmeldung ----
if (isset($_GET['abmelden'])) { session_destroy(); header('Location: admin.php'); exit; }
if (empty($_SESSION['admin'])) {
  $err = '';
  if (!$schwach && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $sf = $GLOBALS['DATA'] . '/admin-sperre.json';
    $s = is_file($sf) ? (json_decode(file_get_contents($sf), true) ?: []) : [];
    if (($s['bis'] ?? 0) > time()) $err = 'Zu viele Fehlversuche – bitte 15 Minuten warten.';
    elseif (hash_equals(ADMIN_PASSWORT, (string)($_POST['pw'] ?? ''))) { session_regenerate_id(true); $_SESSION['admin'] = 1; @unlink($sf); header('Location: admin.php'); exit; }
    else { $s['n'] = ($s['n'] ?? 0) + 1; if ($s['n'] >= 5) $s = ['n' => 0, 'bis' => time() + 900]; file_put_contents($sf, json_encode($s)); sleep(1); $err = 'Passwort falsch.'; }
  }
  ?><!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Blattsingen-Verwaltung</title>
  <style>body{font:16px system-ui,sans-serif;max-width:420px;margin:12vh auto;padding:0 16px;color:#222}input,button{font:inherit;padding:.6em;width:100%;box-sizing:border-box;margin:.3em 0}button{background:#5b3d8a;color:#fff;border:0;border-radius:6px}.err{color:#b00020}</style>
  <h1>Blattsingen-Verwaltung</h1>
  <?php if ($schwach): ?><p class="err">Bitte zuerst in <b>config.php</b> ein eigenes <code>ADMIN_PASSWORT</code> (mindestens 10 Zeichen) eintragen.</p>
  <?php else: ?><form method="post"><input type="password" name="pw" placeholder="Admin-Passwort" autofocus required><button>Anmelden</button></form><p class="err"><?= h($err) ?></p><?php endif; ?>
  </html><?php exit;
}

// ---- Aktionen ----
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  if (!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) weiter('Sitzung abgelaufen, bitte noch einmal.');
  $act = $_POST['act'] ?? ''; $email = mail_norm($_POST['email'] ?? '');
  if ($act === 'neu') {
    $pw = (string)($_POST['pw'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) weiter('E-Mail ungültig.');
    if ($pw === '') $pw = substr(strtr(base64_encode(random_bytes(9)), '+/=', 'xyz'), 0, 10);
    if (strlen($pw) < 8) weiter('Passwort zu kurz (mindestens 8 Zeichen).');
    $t = $_POST['tarif'] ?? TARIF_NEU; if (!isset(TARIFE[$t])) $t = TARIF_NEU;
    $ok = konten_tx(function (&$K) use ($email, $pw, $t) { if (isset($K[$email])) return false; $K[$email] = konto_neu($email, $_POST['name'] ?? '', $pw, $t); $K[$email]['tarif_bis'] = (string)($_POST['bis'] ?? ''); return true; });
    if (!$ok) weiter('Diese E-Mail hat schon ein Konto.');
    protokoll($email, '', 'konto angelegt (admin)', $_POST['name'] ?? '');
    weiter("Konto angelegt. Zugangsdaten für die Person:  E-Mail: $email  ·  Passwort: $pw");
  }
  if ($act === 'speichern') {
    $t = $_POST['tarif'] ?? TARIF_NEU; if (!isset(TARIFE[$t])) $t = TARIF_NEU;
    $bis = (string)($_POST['bis'] ?? ''); if ($bis !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $bis)) weiter('Datum bitte als JJJJ-MM-TT.');
    $lim = trim((string)($_POST['limit_eigen'] ?? ''));
    $pw = (string)($_POST['pw'] ?? '');
    if ($pw !== '' && strlen($pw) < 8) weiter('Neues Passwort zu kurz (mindestens 8 Zeichen).');
    konten_tx(function (&$K) use ($email, $t, $bis, $lim, $pw) {
      if (!isset($K[$email])) return;
      $k = &$K[$email];
      $k['name'] = trim((string)($_POST['name'] ?? $k['name'])); $k['tarif'] = $t; $k['tarif_bis'] = $bis;
      $k['limit_eigen'] = $lim === '' ? null : max(0, (int)$lim);
      $k['gesperrt'] = !empty($_POST['gesperrt']); $k['notiz'] = mb_substr((string)($_POST['notiz'] ?? ''), 0, 300);
      if ($pw !== '') { $k['pw'] = password_hash($pw, PASSWORD_DEFAULT); $k['tv'] = (int)($k['tv'] ?? 1) + 1; $k['sperre_bis'] = 0; $k['fehlversuche'] = 0; }
    });
    weiter('Gespeichert' . ($pw !== '' ? " – neues Passwort: $pw" : '') . '.', '?k=' . urlencode($email));
  }
  if ($act === 'loeschen' && ($_POST['sicher'] ?? '') === 'ja') {
    konten_tx(function (&$K) use ($email) { unset($K[$email]); });
    protokoll($email, '', 'konto gelöscht (admin)');
    weiter('Konto gelöscht.');
  }
  weiter();
}

// ---- Export ----
if (isset($_GET['export'])) {
  header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="blattsingen-konten-' . date('Y-m-d') . '.csv"');
  $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
  $mon = date('Y-m');
  fputcsv($o, ['Name', 'E-Mail', 'Tarif', 'Tarif bis', 'Limit/Monat', "Umwandlungen $mon", 'Umwandlungen gesamt', 'Fehlgeschlagen', 'Angelegt', 'Zuletzt', 'Gesperrt', 'Notiz'], ';');
  foreach (konten_lesen() as $k) fputcsv($o, [$k['name'], $k['email'], TARIFE[tarif_von($k)]['name'] ?? '', $k['tarif_bis'] ?? '', limit_von($k) ?: 'unbegrenzt', $k['monate'][$mon] ?? 0, $k['gesamt'] ?? 0, $k['fehlgeschlagen'] ?? 0, $k['erstellt'] ?? '', $k['zuletzt'] ?? '', !empty($k['gesperrt']) ? 'ja' : '', $k['notiz'] ?? ''], ';');
  exit;
}
if (isset($_GET['protokoll'])) {
  header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="blattsingen-protokoll.csv"');
  $f = $GLOBALS['DATA'] . '/protokoll.csv'; echo "\xEF\xBB\xBF"; if (is_file($f)) readfile($f); exit;
}

// ---- Anzeige ----
$K = konten_lesen(); $mon = date('Y-m'); $msg = $_SESSION['msg'] ?? ''; unset($_SESSION['msg']);
$q = mb_strtolower(trim((string)($_GET['q'] ?? '')));
uasort($K, fn($a, $b) => strcmp($b['zuletzt'] ?? '', $a['zuletzt'] ?? '') ?: strcmp($a['email'], $b['email']));
$summe = 0; $aktiv = 0; $jeTarif = [];
foreach ($K as $k) { $n = $k['monate'][$mon] ?? 0; $summe += $n; if ($n) $aktiv++; $t = tarif_von($k); $jeTarif[$t] = ($jeTarif[$t] ?? 0) + 1; }
$sel = isset($_GET['k']) ? ($K[mail_norm($_GET['k'])] ?? null) : null;
$log = [];
$lf = $GLOBALS['DATA'] . '/protokoll.csv';
if (is_file($lf)) { $lines = file($lf, FILE_IGNORE_NEW_LINES); $log = array_reverse(array_slice($lines, max(1, count($lines) - 40))); }
?><!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Blattsingen-Verwaltung</title>
<style>
body{font:15px system-ui,sans-serif;margin:0 auto;max-width:1100px;padding:16px;color:#222}
h1{font-size:1.4em;margin:.2em 0}h2{font-size:1.1em;margin:1.6em 0 .5em}
table{border-collapse:collapse;width:100%}th,td{text-align:left;padding:.4em .5em;border-bottom:1px solid #ddd;vertical-align:top}th{background:#f4f1f8;font-weight:600}
.num{text-align:right}.msg{background:#eaf6ea;border:1px solid #9c9;padding:.6em;border-radius:6px;white-space:pre-wrap}
.tiles{display:flex;gap:12px;flex-wrap:wrap}.tile{background:#f4f1f8;border-radius:8px;padding:.6em 1em}.tile b{display:block;font-size:1.5em}
form.inl{display:flex;flex-wrap:wrap;gap:8px;align-items:end}label{display:flex;flex-direction:column;font-size:.85em;gap:2px}
input,select,button{font:inherit;padding:.35em .5em}button{background:#5b3d8a;color:#fff;border:0;border-radius:6px;padding:.45em .9em;cursor:pointer}
button.rot{background:#b00020}.box{border:1px solid #ddd;border-radius:8px;padding:12px;margin:10px 0}.voll{color:#b00020;font-weight:600}.grau{color:#777}
a{color:#5b3d8a}.wrap{overflow-x:auto}code{background:#f4f4f4;padding:0 .3em}
</style>
<p style="float:right"><a href="?export=1">Konten als CSV</a> · <a href="?protokoll=1">Protokoll als CSV</a> · <a href="?abmelden=1">Abmelden</a></p>
<h1>Blattsingen – Umwandlungsdienst</h1>
<?php if ($msg): ?><p class="msg"><?= h($msg) ?></p><?php endif; ?>
<?php if (ZUGANG !== 'konto'): ?><p class="voll">Hinweis: In config.php steht <code>ZUGANG = '<?= h(ZUGANG) ?>'</code>. Konten und Passwörter werden erst mit <code>'konto'</code> verlangt.</p><?php endif; ?>
<div class="tiles">
  <div class="tile"><b><?= count($K) ?></b>Konten</div>
  <div class="tile"><b><?= $aktiv ?></b>aktiv im <?= h(date('m/Y')) ?></div>
  <div class="tile"><b><?= $summe ?></b>Umwandlungen im <?= h(date('m/Y')) ?></div>
  <?php foreach (TARIFE as $t => $d): ?><div class="tile"><b><?= (int)($jeTarif[$t] ?? 0) ?></b><?= h($d['name']) ?> (<?= $d['limit'] ?: '∞' ?>/Monat)</div><?php endforeach; ?>
</div>

<?php if ($sel): $t = tarif_von($sel); ?>
<div class="box"><h2 style="margin-top:0">Konto bearbeiten: <?= h($sel['email']) ?></h2>
<form method="post" class="inl"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="act" value="speichern"><input type="hidden" name="email" value="<?= h($sel['email']) ?>">
  <label>Name<input name="name" value="<?= h($sel['name']) ?>"></label>
  <label>Tarif<select name="tarif"><?php foreach (TARIFE as $k => $d): ?><option value="<?= h($k) ?>" <?= ($sel['tarif'] ?? '') === $k ? 'selected' : '' ?>><?= h($d['name']) ?></option><?php endforeach; ?></select></label>
  <label>Tarif gültig bis (leer = unbefristet)<input type="date" name="bis" value="<?= h($sel['tarif_bis'] ?? '') ?>"></label>
  <label>Eigenes Limit/Monat (leer = laut Tarif, 0 = unbegrenzt)<input name="limit_eigen" size="6" value="<?= h($sel['limit_eigen'] ?? '') ?>"></label>
  <label>Neues Passwort (leer = unverändert)<input name="pw" autocomplete="new-password"></label>
  <label>Notiz (z. B. „bezahlt 12/2026“)<input name="notiz" size="30" value="<?= h($sel['notiz'] ?? '') ?>"></label>
  <label><span><input type="checkbox" name="gesperrt" value="1" <?= !empty($sel['gesperrt']) ? 'checked' : '' ?>> gesperrt</span></label>
  <button>Speichern</button>
</form>
<p class="grau">Gilt jetzt: <?= h(TARIFE[$t]['name']) ?>, <?= limit_von($sel) ?: 'unbegrenzt' ?> pro Monat · angelegt <?= h($sel['erstellt'] ?? '') ?> · gesamt <?= (int)($sel['gesamt'] ?? 0) ?>, davon <?= (int)($sel['fehlgeschlagen'] ?? 0) ?> fehlgeschlagen (nicht berechnet)<br>
Je Monat: <?php krsort($sel['monate']); foreach (array_slice($sel['monate'], 0, 12, true) as $m => $n) echo h($m) . ': ' . (int)$n . ' &nbsp; '; ?></p>
<form method="post" class="inl"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="act" value="loeschen"><input type="hidden" name="email" value="<?= h($sel['email']) ?>">
  <label><span><input type="checkbox" name="sicher" value="ja" required> wirklich löschen</span></label><button class="rot">Konto löschen</button></form>
<p><a href="admin.php">← zur Übersicht</a></p></div>
<?php endif; ?>

<h2>Konten</h2>
<form method="get" class="inl"><input name="q" value="<?= h($q) ?>" placeholder="Name oder E-Mail suchen"><button>Suchen</button></form>
<div class="wrap"><table>
<tr><th>Name</th><th>E-Mail</th><th>Tarif</th><th class="num"><?= h(date('m/Y')) ?></th><th class="num">gesamt</th><th>zuletzt</th><th></th></tr>
<?php foreach ($K as $k): if ($q && strpos(mb_strtolower($k['name'] . ' ' . $k['email']), $q) === false) continue;
  $lim = limit_von($k); $n = $k['monate'][$mon] ?? 0; $t = tarif_von($k); ?>
<tr><td><?= h($k['name']) ?><?= !empty($k['gesperrt']) ? ' <span class="voll">gesperrt</span>' : '' ?></td><td><?= h($k['email']) ?></td>
<td><?= h(TARIFE[$t]['name']) ?><?= ($t === ($k['tarif'] ?? '') && !empty($k['tarif_bis'])) ? ' <span class="grau">bis ' . h($k['tarif_bis']) . '</span>' : '' ?><?= ($t !== ($k['tarif'] ?? $t)) ? ' <span class="voll">(' . h(TARIFE[$k['tarif']]['name'] ?? '') . ' abgelaufen)</span>' : '' ?></td>
<td class="num <?= ($lim && $n >= $lim) ? 'voll' : '' ?>"><?= (int)$n ?> / <?= $lim ?: '∞' ?></td><td class="num"><?= (int)($k['gesamt'] ?? 0) ?></td><td><?= h($k['zuletzt'] ?? '') ?></td>
<td><a href="?k=<?= h(urlencode($k['email'])) ?>">bearbeiten</a></td></tr>
<?php endforeach; ?>
</table></div>

<h2>Neues Konto anlegen</h2>
<form method="post" class="inl"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="act" value="neu">
  <label>Name<input name="name" required></label><label>E-Mail<input type="email" name="email" required></label>
  <label>Passwort (leer = wird erzeugt)<input name="pw" autocomplete="new-password"></label>
  <label>Tarif<select name="tarif"><?php foreach (TARIFE as $k => $d): ?><option value="<?= h($k) ?>" <?= $k === TARIF_NEU ? 'selected' : '' ?>><?= h($d['name']) ?></option><?php endforeach; ?></select></label>
  <label>gültig bis (optional)<input type="date" name="bis"></label>
  <button>Anlegen</button></form>
<p class="grau">Selbst-Registrierung in der App: <?= SELBST_REGISTRIEREN ? 'an' . (EINLADUNGSCODE !== '' ? ' (mit Einladungscode)' : ' (ohne Code – jeder mit der App)') : 'aus' ?> · Einstellungen in config.php</p>

<h2>Letzte Ereignisse</h2>
<div class="wrap"><table><tr><th>Zeit</th><th>E-Mail</th><th>Ereignis</th><th>Titel</th></tr>
<?php foreach ($log as $l): $c = str_getcsv($l, ';'); if (count($c) < 5) continue; ?><tr><td><?= h($c[0]) ?></td><td><?= h($c[1]) ?></td><td><?= h($c[3]) ?></td><td><?= h($c[4]) ?></td></tr><?php endforeach; ?>
</table></div>
</html>
