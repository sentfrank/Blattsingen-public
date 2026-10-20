# Blattsingen-App lokal auf dem eigenen Rechner (Version 42)

So wie am Anfang: Die App läuft ohne Internet-Adresse direkt von deinem Rechner.

## Starten
- **Mac:** Doppelklick auf „Blattsingen starten (Mac).command“. Beim ersten Mal: Rechtsklick › „Öffnen“
  (macOS fragt einmal nach, weil die Datei aus dem Internet kommt). Der Browser öffnet `http://localhost:8765`.
- **Windows:** Doppelklick auf „Blattsingen starten (Windows).bat“. Ohne Python startet ein kleiner Webserver über PowerShell.
- **Linux:** „Blattsingen starten (Linux).sh“.
- Fenster mit dem laufenden Server offen lassen, solange du übst; Schließen beendet die App.

## Was geht, was nicht
- Trainer, Mikrofon (Live-Mitsingen, Stimmgabel), Tastatur, Meine Noten, Übungsblätter: alles wie online.
  Über `http://localhost` darf der Browser das Mikrofon benutzen.
- Notenumwandlung (PDF → Stimmen): mit deinem eigenen GitHub-Zugang („Automatische Umwandlung einrichten“)
  oder, wenn du eine `server.json` mit der Adresse deines Dienstes (Cloudflare/Strato) dazulegst, über den Dienst.
- Gespeicherte Stücke liegen im Browser dieses Rechners unter der Adresse `localhost:8765` – immer denselben
  Browser und dasselbe Startprogramm nehmen.
- Nur `index.html` doppelklicken (ohne Startprogramm) geht für den Trainer, aber Mikrofon und Notenablage
  funktionieren dann je nach Browser nicht zuverlässig.
