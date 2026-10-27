<?php
/* Stücke und Projekte je Konto auf dem Server sichern, damit sie auf jedem Gerät erscheinen (von api.php eingebunden).
   Ablage: daten/nutzer/<kennung>/index.json (Übersicht), <id>.json (Stück ohne PDF), <id>.pdf
   Regel bei gleichzeitigen Änderungen: der neuere Stand (Zeitstempel u der App) gewinnt. */
if (!defined('GH_REPO')) exit;

function sync_max_mb() { return defined('SYNC_MB') ? (int)SYNC_MB : 300; }
function sync_dir($email) {
  $d = $GLOBALS['DATA'] . '/nutzer/' . substr(hash_hmac('sha256', 'sync|' . $email, SERVER_KEY), 0, 24);
  if (!is_dir($d)) @mkdir($d, 0700, true);
  return $d;
}
function sync_id_ok($id) { return is_string($id) && preg_match('/^[A-Za-z0-9_-]{1,48}$/', $id); }
/** Übersicht gesperrt lesen/ändern/schreiben */
function sync_tx($dir, callable $fn) {
  $fp = fopen("$dir/index.json", 'c+'); flock($fp, LOCK_EX);
  $ix = json_decode(stream_get_contents($fp) ?: '{}', true) ?: [];
  if (!isset($ix['stuecke'])) $ix['stuecke'] = [];
  $res = $fn($ix);
  ftruncate($fp, 0); rewind($fp); fwrite($fp, json_encode($ix, JSON_UNESCAPED_UNICODE)); fflush($fp);
  flock($fp, LOCK_UN); fclose($fp);
  return $res;
}
function sync_belegt($ix) { $n = 0; foreach ($ix['stuecke'] as $s) $n += (int)($s['gr'] ?? 0); return $n; }

if (strpos($a, 'sync_') === 0) {
  nur_post();
  if (ZUGANG !== 'konto') out(400, ['fehler' => 'Geräte-Abgleich gibt es nur mit Benutzerkonto.']);
  if (defined('SYNC_AN') && !SYNC_AN) out(403, ['fehler' => 'Der Geräte-Abgleich ist ausgeschaltet.']);
  $k = konto_aus_token(); $dir = sync_dir($k['email']);
  $max = sync_max_mb() * 1048576;

  if ($a === 'sync_liste') {
    $ix = sync_tx($dir, function (&$ix) { return $ix; });
    out(200, ['stuecke' => (object)$ix['stuecke'], 'projekte' => $ix['projekte'] ?? null, 'belegt' => sync_belegt($ix), 'max' => $max]);
  }

  $id = post('id');
  if (in_array($a, ['sync_hole', 'sync_pdf', 'sync_sende', 'sync_loesche'], true) && !sync_id_ok($id)) out(400, ['fehler' => 'Ungültige Stück-Kennung']);

  if ($a === 'sync_hole') {
    if (!is_file("$dir/$id.json")) out(404, ['fehler' => 'Stück nicht gefunden']);
    header('Content-Type: application/json; charset=utf-8'); readfile("$dir/$id.json"); exit;
  }
  if ($a === 'sync_pdf') {
    if (!is_file("$dir/$id.pdf")) out(404, ['fehler' => 'PDF nicht gefunden']);
    header('Content-Type: application/pdf'); header('Content-Length: ' . filesize("$dir/$id.pdf")); readfile("$dir/$id.pdf"); exit;
  }
  if ($a === 'sync_sende') {
    $u = (int)post('u'); $meta = post('meta'); $ph = preg_replace('/[^a-f0-9]/', '', post('ph'));
    if ($u <= 0 || $meta === '' || json_decode($meta) === null) out(400, ['fehler' => 'Unvollständige Daten']);
    $hasPdf = isset($_FILES['pdf']) && is_uploaded_file($_FILES['pdf']['tmp_name']);
    $res = sync_tx($dir, function (&$ix) use ($dir, $id, $u, $meta, $ph, $hasPdf, $max) {
      $alt = $ix['stuecke'][$id] ?? null;
      if ($alt && (int)$alt['u'] > $u) return ['code' => 409, 'u' => (int)$alt['u']];   // Server ist neuer
      if ($ph !== '' && !$hasPdf && (!$alt || ($alt['ph'] ?? '') !== $ph || !is_file("$dir/$id.pdf"))) return ['code' => 428];   // PDF fehlt
      $gr = strlen($meta) + ($ph === '' ? 0 : ($hasPdf ? filesize($_FILES['pdf']['tmp_name']) : (int)@filesize("$dir/$id.pdf")));
      if (sync_belegt($ix) - (int)($alt['gr'] ?? 0) + $gr > $max) return ['code' => 413];
      file_put_contents("$dir/$id.json.neu", $meta); rename("$dir/$id.json.neu", "$dir/$id.json");
      if ($hasPdf) move_uploaded_file($_FILES['pdf']['tmp_name'], "$dir/$id.pdf");
      if ($ph === '') @unlink("$dir/$id.pdf");
      $ix['stuecke'][$id] = ['u' => $u, 'ph' => $ph, 'gr' => $gr];
      return ['code' => 200, 'belegt' => sync_belegt($ix)];
    });
    if ($res['code'] === 409) out(409, ['fehler' => 'Auf dem Server liegt ein neuerer Stand.', 'u' => $res['u']]);
    if ($res['code'] === 428) out(428, ['fehler' => 'PDF fehlt', 'pdf' => true]);
    if ($res['code'] === 413) out(413, ['fehler' => 'Dein Speicherplatz auf dem Server ist voll (' . sync_max_mb() . ' MB). Lösche alte Stücke oder frag den Betreiber.']);
    out(200, ['ok' => true, 'belegt' => $res['belegt'], 'max' => $max]);
  }
  if ($a === 'sync_loesche') {
    $u = (int)post('u');
    sync_tx($dir, function (&$ix) use ($dir, $id, $u) {
      $alt = $ix['stuecke'][$id] ?? null;
      if ($alt && (int)$alt['u'] > $u) return;
      @unlink("$dir/$id.json"); @unlink("$dir/$id.pdf");
      $ix['stuecke'][$id] = ['u' => $u, 'del' => 1, 'gr' => 0];
    });
    out(200, ['ok' => true]);
  }
  if ($a === 'sync_projekte') {
    $u = (int)post('u'); $l = json_decode(post('liste'), true);
    if (!is_array($l)) out(400, ['fehler' => 'Liste fehlt']);
    $l = array_values(array_slice(array_filter(array_map(function ($x) { return mb_substr(trim((string)$x), 0, 80); }, $l), 'strlen'), 0, 500));
    $r = sync_tx($dir, function (&$ix) use ($u, $l) {
      if (isset($ix['projekte']) && (int)$ix['projekte']['u'] > $u) return $ix['projekte'];
      $ix['projekte'] = ['u' => $u, 'liste' => $l]; return $ix['projekte'];
    });
    out(200, ['projekte' => $r]);
  }
  out(400, ['fehler' => 'Unbekannte Aktion']);
}
