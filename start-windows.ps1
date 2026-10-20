param([int]$Port = 8765)
# Minimaler Webserver für die Blattsingen-App (nur auf diesem Rechner erreichbar)
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$types = @{ ".html"="text/html; charset=utf-8"; ".js"="text/javascript"; ".json"="application/json"; ".webmanifest"="application/manifest+json";
            ".png"="image/png"; ".svg"="image/svg+xml"; ".pdf"="application/pdf"; ".musicxml"="application/vnd.recordare.musicxml+xml"; ".css"="text/css"; ".txt"="text/plain" }
$l = New-Object System.Net.HttpListener; $l.Prefixes.Add("http://localhost:$Port/"); $l.Start()
Write-Host "Blattsingen läuft unter http://localhost:$Port/  -  zum Beenden dieses Fenster schließen."
while ($l.IsListening) {
  $c = $l.GetContext(); $p = [Uri]::UnescapeDataString($c.Request.Url.AbsolutePath.TrimStart('/')); if ($p -eq "") { $p = "index.html" }
  $f = Join-Path $root $p
  if ((Test-Path $f -PathType Leaf) -and ((Resolve-Path $f).Path.StartsWith($root))) {
    $b = [IO.File]::ReadAllBytes($f); $e = [IO.Path]::GetExtension($f).ToLower()
    $c.Response.ContentType = $(if ($types[$e]) { $types[$e] } else { "application/octet-stream" }); $c.Response.OutputStream.Write($b, 0, $b.Length)
  } else { $c.Response.StatusCode = 404 }
  $c.Response.Close()
}
