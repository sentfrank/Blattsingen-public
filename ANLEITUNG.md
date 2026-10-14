# Blattsingen-App aufs iPhone bringen

Die App ist eine Web-App: Sie läuft in Safari, lässt sich auf den Home-Bildschirm legen und darf dort das Mikrofon nutzen. Dafür muss sie einmal im Internet unter einer https-Adresse liegen. Am einfachsten geht das kostenlos mit GitHub Pages.

## Update einspielen (wenn die App schon online ist)

1. Im GitHub-Repository „Add file“ → „Upload files“.
2. Alle Dateien aus dieser ZIP hineinziehen und die alten überschreiben, dann „Commit changes“.
3. Ein bis zwei Minuten warten. Am iPhone die App komplett schließen (vom unteren Rand hochwischen, App wegwischen) und neu öffnen. Bei Bedarf zweimal.

## 1. Online stellen (einmalig, ca. 10 Minuten, am Computer)

1. Auf github.com ein kostenloses Konto anlegen (falls noch keins da ist).
2. Oben rechts „+“ → „New repository“. Name z. B. `blattsingen`, Sichtbarkeit „Public“, „Create repository“.
3. Auf der leeren Repository-Seite „uploading an existing file“ anklicken.
4. Alle Dateien aus dieser ZIP hineinziehen (index.html, manifest.webmanifest, sw.js und die drei PNG-Bilder), dann „Commit changes“.
5. „Settings“ → links „Pages“ → bei „Branch“ `main` und `/ (root)` wählen → „Save“.
6. Nach ein bis zwei Minuten steht oben die Adresse, etwa `https://DEINNAME.github.io/blattsingen/`.

## 2. Aufs iPhone holen

1. Die Adresse in **Safari** öffnen (nicht in einer anderen App).
2. Teilen-Symbol → „Zum Home-Bildschirm“ → „Hinzufügen“.
3. Die App über das neue Symbol starten. Beim ersten „Live starten“ fragt das iPhone nach dem Mikrofon: „Erlauben“.

Danach funktioniert die App auch ohne Internet, nur die Schriften sehen dann etwas einfacher aus.

## 3. Live mitsingen

- Im Tab „Tonleitern“ Stimmlage, Tonart und Richtung wählen.
- **Kopfhörer aufsetzen**, damit das Mikrofon nur deine Stimme hört.
- „Live starten“: Der Grundton erklingt, dann singst du die Tonleiter.
- Das Stimmgerät zeigt laufend, wie weit du vom Zielton weg bist. Jeder gehaltene Ton landet sofort als farbiger Punkt neben seiner Note.
- Zwischen gleich hohen Tönen kurz absetzen.

## Wenn das Mikrofon nicht startet

Die App zeigt unter „Live starten“ jetzt den genauen Grund an, mit einem technischen Code in Klammern. Die häufigsten Fälle:

- **NotAllowedError**: Einstellungen → Apps → Safari → Mikrofon auf „Fragen“ stellen. Außerdem in Safari auf der Seite links in der Adressleiste „Aa“ (oder das Menü-Symbol) → „Website-Einstellungen“ → Mikrofon „Fragen“. Danach App schließen und neu öffnen.
- **Erst im normalen Safari-Tab testen**: Klappt es dort, aber nicht über das Home-Bildschirm-Symbol, das Symbol löschen und neu hinzufügen.
- **NotReadableError**: Ein Telefonat, eine Sprachmemo oder eine andere App benutzt gerade das Mikrofon.
- **„nur über https“**: Die Datei wurde direkt geöffnet statt über die GitHub-Adresse.

## Hinweise

- Das Mikrofon wird nur während „Live mitsingen“ benutzt. Nichts wird aufgezeichnet oder hochgeladen, alles bleibt auf dem iPhone.
- Ein Repository mit „Public“ ist für jeden mit der Adresse sichtbar. Inhalte sind nur der Trainer, keine persönlichen Daten.
