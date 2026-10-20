#!/bin/bash
# Startet die Blattsingen-App auf diesem Mac: http://localhost:8765
cd "$(dirname "$0")"
PORT=8765
if ! command -v python3 >/dev/null 2>&1; then
  osascript -e 'display dialog "Für den Start wird Python 3 gebraucht. macOS bietet gleich an, die Befehlszeilen-Werkzeuge zu installieren – bitte zustimmen und danach dieses Programm noch einmal öffnen." buttons {"OK"}'
  xcode-select --install 2>/dev/null; exit 1
fi
( sleep 1; open "http://localhost:$PORT/" ) &
echo "Blattsingen läuft unter http://localhost:$PORT/  –  zum Beenden dieses Fenster schließen."
python3 -m http.server $PORT --bind 127.0.0.1
