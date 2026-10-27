# Branch „strato“ – Blattsingen-App auf www.w-esel.de/blattsingen (Version S1.2.0, Build 47)

Enthält die App **und** den Umwandlungsdienst in PHP (Ordner `dienst/`). Beides läuft auf deinem Strato-Webspace
unter derselben Domain – keine zweite Domain nötig:

- App: `https://www.w-esel.de/blattsingen/`
- Dienst: `https://www.w-esel.de/blattsingen/dienst/api.php`
- Verwaltung im Browser: `https://www.w-esel.de/blattsingen/dienst/admin.php` (oder in der App: Knopf „Verwaltung“)

## Einmalig einrichten

1. **Erstes Hochladen** (eine der beiden Arten):
   - *Automatisch:* Im App-Repository › Settings › Secrets and variables › Actions vier Secrets anlegen:
     `STRATO_HOST` und `STRATO_USER` aus dem Strato-Kundenlogin › „Ihr Paket“ › „Datenbanken und Webspace“ › „SFTP & SSH“
     (Server sieht aus wie `50xxxxxx03.ssh.w2.strato.hosting`, Benutzer wie `su15xxx9`; dort kannst du auch einen
     eigenen SFTP-Zugang mit Passwort anlegen), `STRATO_PASS` (dessen Passwort) und `STRATO_PATH`: der Ordner, auf den
     www.w-esel.de zeigt, plus `/blattsingen` – z. B. `/blattsingen`, wenn die Domain auf das Hauptverzeichnis zeigt
     (nachzusehen unter „Domains“ › Domain › „Umleitung/Ziel“).
     Ab dann lädt jeder Commit in diesen Branch die Dateien selbst hoch (Actions › „Auf Strato veröffentlichen“).
   - *Von Hand:* Den Inhalt dieses Pakets per SFTP (FileZilla) in den Ordner `blattsingen` deines Webspace laden.
2. **Dienst einstellen:** Auf dem Server `dienst/config.beispiel.php` kopieren nach `dienst/config.php` und darin eintragen:
   `GH_REPO`, `GH_TOKEN` (GitHub-Schlüssel, nur „Actions: Read and write“ für blattsingen-konverter), `SERVER_KEY`
   (**derselbe** wie beim Cloudflare-Worker und als Secret im Repository blattsingen-konverter), `ADMIN_PASSWORT`,
   `ADMIN_EMAIL`. `config.php` bleibt nur auf dem Server und wird beim automatischen Hochladen nie überschrieben.
3. **Test:** `https://www.w-esel.de/blattsingen/dienst/api.php?a=ping` muss `{"ok":true,…}` zeigen.
4. **Konverter-Repository:** Die aktuelle `server.yml` (mit Eingabe „dienst“) muss in blattsingen-konverter liegen.
   Dann melden die Umwandlungen ihr Ergebnis jeweils an den Dienst zurück, der sie gestartet hat – Cloudflare und
   Strato können gleichzeitig laufen.

## Lieber den Cloudflare-Dienst nutzen?
In `server.json` die Worker-Adresse eintragen, z. B. `{"url": "https://blattsingen.DEINNAME.workers.dev"}`.
Den Ordner `dienst/` brauchst du dann nicht.

## Hinweis
Die App speichert Noten pro Adresse im Browser. Stücke, die du in der GitHub-Pages-Fassung gespeichert hast,
erscheinen nicht automatisch in der Strato-Fassung (andere Adresse) – dort neu hinzufügen.
