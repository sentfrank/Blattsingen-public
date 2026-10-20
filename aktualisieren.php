<?php
/* Blattsingen auf Strato aktualisieren: holt den Branch „strato“ als ZIP von GitHub und entpackt ihn hierher.
   Aufruf durch GitHub Actions (Workflow „Auf Strato veröffentlichen“) mit dem Schlüssel DEPLOY_KEY aus dienst/config.php.
   Dateien, die nur auf dem Server liegen (dienst/config.php, dienst/daten/), werden nie angefasst. */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
@set_time_limit(300);

function ende($code, $text) { http_response_code($code); echo $text, "\n"; exit; }

$cfg = __DIR__ . '/dienst/config.php';
if (!is_file($cfg)) ende(500, 'FEHLER: dienst/config.php fehlt auf dem Server (bitte per Webspace-Explorer hochladen).');
require $cfg;
if (!defined('DEPLOY_KEY') || strlen(DEPLOY_KEY) < 16) ende(500, 'FEHLER: In dienst/config.php fehlt const DEPLOY_KEY = \'…\'; (mindestens 16 Zeichen).');

$key = $_SERVER['HTTP_X_KEY'] ?? ($_POST['key'] ?? '');
if (!is_string($key) || !hash_equals(DEPLOY_KEY, $key)) { sleep(2); ende(403, 'FEHLER: Schlüssel falsch (Secret STRATO_DEPLOY_KEY muss gleich DEPLOY_KEY in dienst/config.php sein).'); }
if (!class_exists('ZipArchive')) ende(500, 'FEHLER: PHP-Erweiterung zip fehlt auf dem Server.');

$repo = defined('DEPLOY_REPO') ? DEPLOY_REPO : 'sentfrank/Blattsingen-public';
$ref  = $_POST['ref'] ?? 'strato';
if (!preg_match('/^([0-9a-f]{40}|[A-Za-z0-9._\/-]{1,80})$/', $ref)) ende(400, 'FEHLER: ungültiger ref');
$url = defined('DEPLOY_ZIP_URL') ? DEPLOY_ZIP_URL : "https://codeload.github.com/$repo/zip/" . (strlen($ref) === 40 && ctype_xdigit($ref) ? $ref : "refs/heads/$ref");

// ZIP laden
$tmp = __DIR__ . '/.aktualisieren-' . bin2hex(random_bytes(4)) . '.zip';
$fp = fopen($tmp, 'wb');
$ch = curl_init($url);
$hdr = ['User-Agent: blattsingen-strato'];
if (defined('DEPLOY_GH_TOKEN') && DEPLOY_GH_TOKEN !== '') $hdr[] = 'Authorization: Bearer ' . DEPLOY_GH_TOKEN;
curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 180,
  CURLOPT_FAILONERROR => true, CURLOPT_HTTPHEADER => $hdr]);
$ok = curl_exec($ch); $err = curl_error($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch); fclose($fp);
if (!$ok) { @unlink($tmp); ende(502, "FEHLER: Download von GitHub fehlgeschlagen (HTTP $http) $err\n$url"); }

$zip = new ZipArchive();
if ($zip->open($tmp) !== true) { @unlink($tmp); ende(502, 'FEHLER: ZIP von GitHub ist beschädigt.'); }

$geschrieben = 0; $gleich = 0; $ausgelassen = 0; $fehler = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
  $name = $zip->getNameIndex($i);
  $rel = preg_replace('#^[^/]+/#', '', $name);          // obersten Ordner (Repo-Branch) abschneiden
  if ($rel === '' || substr($rel, -1) === '/') continue; // Ordner
  if (strpos($rel, '..') !== false || $rel[0] === '/' || strpos($rel, "\0") !== false) { $ausgelassen++; continue; }
  if (preg_match('#^(\.git|\.github/|BRANCH\.md$|dienst/config\.php$|dienst/daten/)#', $rel)) { $ausgelassen++; continue; }
  $inhalt = $zip->getFromIndex($i);
  if ($inhalt === false) { $fehler[] = $rel; continue; }
  $ziel = __DIR__ . '/' . $rel;
  if (is_file($ziel) && md5_file($ziel) === md5($inhalt)) { $gleich++; continue; }
  $ord = dirname($ziel);
  if (!is_dir($ord) && !@mkdir($ord, 0755, true)) { $fehler[] = $rel; continue; }
  $t = $ziel . '.neu-' . getmypid();
  if (@file_put_contents($t, $inhalt) === false || !@rename($t, $ziel)) { @unlink($t); $fehler[] = $rel; continue; }
  $geschrieben++;
}
$zip->close(); @unlink($tmp);

echo "Aktualisiert aus $repo ($ref): $geschrieben neu/geändert, $gleich unverändert, $ausgelassen ausgelassen.\n";
if ($fehler) ende(500, 'FEHLER beim Schreiben: ' . implode(', ', array_slice($fehler, 0, 20)));
echo "OK\n";
