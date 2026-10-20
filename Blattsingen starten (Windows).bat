@echo off
rem Startet die Blattsingen-App auf diesem PC: http://localhost:8765
cd /d "%~dp0"
set PORT=8765
where py >nul 2>nul && (start "" http://localhost:%PORT%/ & py -m http.server %PORT% --bind 127.0.0.1 & goto :eof)
where python >nul 2>nul && (start "" http://localhost:%PORT%/ & python -m http.server %PORT% --bind 127.0.0.1 & goto :eof)
rem Ohne Python: kleiner Webserver mit PowerShell
start "" http://localhost:%PORT%/
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0start-windows.ps1" -Port %PORT%
