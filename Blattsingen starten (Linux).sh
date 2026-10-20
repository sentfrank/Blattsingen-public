#!/bin/bash
cd "$(dirname "$0")"; PORT=8765
( sleep 1; xdg-open "http://localhost:$PORT/" >/dev/null 2>&1 ) &
python3 -m http.server $PORT --bind 127.0.0.1
