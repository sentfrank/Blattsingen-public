<?php
/* Projekte mit anderen Chormitgliedern teilen (von api.php eingebunden).
   Die App des Besitzers lädt eine Kopie der Stücke hoch (Noten, PDF, Gesangsspuren); wer den Link hat,
   kann sie ohne eigene Umwandlung in seine App übernehmen. Erneutes Teilen aktualisiert denselben Link.
   Ablage: daten/teilen/<link>/info.json, <id>.json, <id>.pdf, <id>.ton<k>.mp3 */
if (!defined('GH_REPO')) exit;

function teilen_max_mb() { return defined('TEILEN_MB') ? (int)TEILEN_MB : 400; }
function teilen_dir($t) { return $GLOBALS['DATA'] . '/teilen/' . $t; }
function teilen_t_ok($t) { return is_string($t) && preg_match('/^[a-f0-9]{20}$/', $t); }
function teilen_info($t) { $f = teilen_dir($t) . '/info.json'; return is_file($f) ? (json_decode(file_get_contents($f), true) ?: null) : null; }
function teilen_save($t, $i) { file_put_contents(teilen_dir($t) . '/info.json', json_encode($i, JSON_UNESCAPED_UNICODE)); }
function teilen_owner($k) { return substr(hash_hmac('sha256', 'teilen|' . $k['email'], SERVER_KEY), 0, 24); }
function teilen_groesse($d) { $n = 0; foreach (glob("$d/*") ?: [] as $f) $n += filesize($f); return $n; }

if (strpos($a, 'teilen_') === 0) {
  $t = $_GET['t'] ?? post('t');
  // --- öffentlich (nur mit Link): Liste und Dateien abholen ---
  if (in_array($a, ['teilen_liste', 'teilen_hole', 'teilen_pdf', 'teilen_ton'], true)) {
    if (!teilen_t_ok($t) || !($i = teilen_info($t)) || empty($i['fertig'])) out(404, ['fehler' => 'Dieser geteilte Link gilt nicht mehr.']);
    $d = teilen_dir($t);
    if ($a === 'teilen_liste') out(200, ['projekt' => $i['projekt'], 'von' => $i['von'] ?? '', 'u' => $i['u'], 'stuecke' => array_values($i['stuecke'])]);
    $id = $_GET['id'] ?? '';
    if (!preg_match('/^[A-Za-z0-9_-]{1,48}$/', $id) || !isset($i['stuecke'][$id])) out(404, ['fehler' => 'Stück nicht gefunden']);
    if ($a === 'teilen_hole') { header('Content-Type: application/json; charset=utf-8'); readfile("$d/$id.json"); exit; }
    if ($a === 'teilen_pdf') { if (!is_file("$d/$id.pdf")) out(404, ['fehler' => 'keine PDF']); header('Content-Type: application/pdf'); header('Content-Length: ' . filesize("$d/$id.pdf")); readfile("$d/$id.pdf"); exit; }
    $k = (int)($_GET['k'] ?? -1);
    if (!is_file("$d/$id.ton$k.mp3")) out(404, ['fehler' => 'keine Spur']);
    header('Content-Type: audio/mpeg'); header('Content-Length: ' . filesize("$d/$id.ton$k.mp3")); readfile("$d/$id.ton$k.mp3"); exit;
  }
  // --- nur Besitzer (angemeldet) ---
  nur_post();
  if (ZUGANG !== 'konto') out(400, ['fehler' => 'Teilen gibt es nur mit Benutzerkonto.']);
  $k = konto_aus_token(); $own = teilen_owner($k);
  if ($a === 'teilen_neu') {   // neuen Link anlegen oder vorhandenen zum Aktualisieren öffnen
    $proj = mb_substr(trim(post('projekt')), 0, 80);
    if ($proj === '') out(400, ['fehler' => 'Projektname fehlt']);
    if ($t !== '' && teilen_t_ok($t) && ($i = teilen_info($t)) && $i['owner'] === $own) {
      $i['projekt'] = $proj; $i['neu'] = []; teilen_save($t, $i); out(200, ['t' => $t]);
    }
    $t = bin2hex(random_bytes(10)); @mkdir(teilen_dir($t), 0700, true);
    teilen_save($t, ['owner' => $own, 'projekt' => $proj, 'von' => mb_substr(trim(post('von')), 0, 60), 'u' => 0, 'fertig' => false, 'stuecke' => [], 'neu' => []]);
    out(200, ['t' => $t]);
  }
  if (!teilen_t_ok($t) || !($i = teilen_info($t)) || $i['owner'] !== $own) out(404, ['fehler' => 'Geteilter Link nicht gefunden']);
  $d = teilen_dir($t);
  if ($a === 'teilen_stueck') {   // ein Stück hochladen (meta + PDF + Gesangsspuren)
    $id = post('id'); $meta = post('meta');
    if (!preg_match('/^[A-Za-z0-9_-]{1,48}$/', $id) || json_decode($meta) === null) out(400, ['fehler' => 'Unvollständige Daten']);
    $ph = preg_replace('/[^a-z0-9-]/', '', post('ph'));
    $have = $i['stuecke'][$id] ?? null;
    file_put_contents("$d/$id.json", $meta);
    if (isset($_FILES['pdf']) && is_uploaded_file($_FILES['pdf']['tmp_name'])) move_uploaded_file($_FILES['pdf']['tmp_name'], "$d/$id.pdf");
    elseif ($ph === '') @unlink("$d/$id.pdf");
    elseif (!$have || ($have['ph'] ?? '') !== $ph || !is_file("$d/$id.pdf")) out(428, ['fehler' => 'PDF fehlt', 'pdf' => true]);
    $ton = [];
    foreach (glob("$d/$id.ton*.mp3") ?: [] as $f) @unlink($f);   // Spuren kommen immer vollständig mit
    foreach ($_FILES as $n => $f) if (preg_match('/^ton(\d{1,2})$/', $n, $m) && is_uploaded_file($f['tmp_name'])) { move_uploaded_file($f['tmp_name'], "$d/$id.ton{$m[1]}.mp3"); $ton[] = (int)$m[1]; }
    if (teilen_groesse($d) > teilen_max_mb() * 1048576) out(413, ['fehler' => 'Das geteilte Projekt ist zu groß (höchstens ' . teilen_max_mb() . ' MB).']);
    $i = teilen_info($t);
    $i['neu'][$id] = ['id' => $id, 'title' => mb_substr((string)(json_decode($meta, true)['title'] ?? ''), 0, 120), 'u' => (int)post('u'), 'ph' => $ph, 'ton' => array_values(array_unique($ton))];
    teilen_save($t, $i);
    out(200, ['ok' => true]);
  }
  if ($a === 'teilen_behalte') {   // unverändertes Stück: nichts neu senden
    $id = post('id');
    if (!isset($i['stuecke'][$id])) out(404, ['fehler' => 'nicht vorhanden']);
    $i['neu'][$id] = $i['stuecke'][$id]; teilen_save($t, $i); out(200, ['ok' => true]);
  }
  if ($a === 'teilen_hat') out(200, ['stuecke' => (object)$i['stuecke']]);   // was schon oben liegt (PDF/Spuren nicht doppelt senden)
  if ($a === 'teilen_fertig') {   // Reihenfolge festlegen, Übriges entfernen
    $ids = json_decode(post('ids'), true) ?: [];
    $st = [];
    foreach ($ids as $id) if (isset($i['neu'][$id])) $st[$id] = $i['neu'][$id];
    foreach (glob("$d/*") ?: [] as $f) { $b = basename($f); if ($b === 'info.json') continue; $id = preg_replace('/\..*$/', '', $b); if (!isset($st[$id])) @unlink($f); }
    $i['stuecke'] = $st; $i['neu'] = []; $i['fertig'] = true; $i['u'] = time(); teilen_save($t, $i);
    out(200, ['ok' => true, 'n' => count($st)]);
  }
  if ($a === 'teilen_beenden') {
    foreach (glob("$d/*") ?: [] as $f) @unlink($f); @rmdir($d);
    out(200, ['ok' => true]);
  }
  out(400, ['fehler' => 'Unbekannte Aktion']);
}
