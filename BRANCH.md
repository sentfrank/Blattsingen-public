# Branch „main“ – Blattsingen-App auf GitHub Pages (Version 42)

Diese Fassung wird von GitHub Pages veröffentlicht (Settings › Pages › Branch: main).
Die Notenumwandlung läuft über den Dienst, dessen Adresse in `server.json` steht (z. B. der Cloudflare-Worker):

    {"url": "https://blattsingen.DEINNAME.workers.dev"}

`server.json` und `anmeldung.json` gehören nur in diesen Branch und werden beim Aktualisieren nicht überschrieben
(sie sind im Paket nicht enthalten).
