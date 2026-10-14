# Blattsingen-App aufs iPhone bringen

Die App ist eine Web-App: Sie läuft in Safari, lässt sich auf den Home-Bildschirm legen und darf dort das Mikrofon nutzen. Dafür muss sie einmal im Internet unter einer https-Adresse liegen. Am einfachsten geht das kostenlos mit GitHub Pages.

## Update einspielen (wenn die App schon online ist)

1. Im GitHub-Repository „Add file“ → „Upload files“.
2. Alle Dateien aus dieser ZIP hineinziehen, **einschließlich des Ordners „lib“**, und die alten überschreiben, dann „Commit changes“.
3. Ein bis zwei Minuten warten. Am iPhone die App komplett schließen (vom unteren Rand hochwischen, App wegwischen) und neu öffnen. Bei Bedarf zweimal.

## 1. Online stellen (einmalig, ca. 10 Minuten, am Computer)

1. Auf github.com ein kostenloses Konto anlegen (falls noch keins da ist).
2. Oben rechts „+“ → „New repository“. Name z. B. `blattsingen`, Sichtbarkeit „Public“, „Create repository“.
3. Auf der leeren Repository-Seite „uploading an existing file“ anklicken.
4. Alle Dateien aus dieser ZIP hineinziehen, **auch den Ordner „lib“** (am Computer den ganzen entpackten Inhalt markieren und ins Browserfenster ziehen), dann „Commit changes“.
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

**Treffübung live:** Im Tab „Treffübung“ unter dem Notenbild „🎤 Live mitsingen“ tippen. Nach dem Grundton singst du die Melodie vom Blatt. Jeder Ton erscheint sofort in den Noten: grün getroffen (bis ±20 Cent), gelb knapp daneben (bis ±45 Cent), rot daneben. Ein ganz anderer Ton steht als rote Note neben der Zielnote, darunter sein Name. „Neue Melodie und los“ startet gleich die nächste Runde.

## 4. Meine Noten

- Abschnitt „Meine Noten“ → „Dateien auswählen“: die PDF eines Stücks und, wenn vorhanden, die passende **MusicXML-** (.musicxml, .xml, .mxl) oder **MIDI-Datei** (.mid). Beide können auf einmal ausgewählt werden.
- Die PDF wird angezeigt (Zoom mit − / +). Die Stimmen spielt die App aus der MusicXML- oder MIDI-Datei, nicht aus der PDF: Eine PDF ist nur ein Bild der Noten.
- „Stimmen ▾“ öffnet den Mixer: **Meine Stimme** wählen, dann „hervorheben“, „nur meine Stimme“ oder „ohne meine Stimme“ (zum Mitsingen). Pro Stimme gibt es Solo, Stumm und Lautstärke. Dazu Tempo, ab/bis Takt, Einzähler und Wiederholen.
- „Einsatzton“ spielt den nächsten Ton deiner Stimme ab der aktuellen Stelle.
- Die Stücke bleiben auf dem iPhone gespeichert, auch offline. Wenn du die App vom Home-Bildschirm löschst, sind sie weg.
- Wiederholungszeichen und Sprünge (D.C., Coda) spielt die App nicht aus. Das Stück läuft einmal durch.

## 5. PDF automatisch in Stimmen umwandeln

Die App kann aus einer PDF selbst die Stimmen erkennen. Die Erkennung läuft kostenlos bei GitHub, in einem zweiten, **privaten** Repository, damit deine Noten nicht öffentlich werden. Einmalig einrichten (ca. 5 Minuten, am besten am Computer, geht aber auch am iPhone):

1. Auf github.com oben rechts „+“ → „New repository“. Name **blattsingen-konverter**, **Private**, Haken bei „Add a README file“, „Create repository“.
2. Zugangsschlüssel anlegen: github.com/settings/personal-access-tokens/new
   - Token name: z. B. „Blattsingen“, Expiration nach Wunsch (z. B. 1 Jahr)
   - Repository access: „Only select repositories“ → **blattsingen-konverter**
   - Permissions → Repository permissions: **Contents** = „Read and write“ und **Workflows** = „Read and write“
   - „Generate token“, den Schlüssel (beginnt mit `github_pat_`) kopieren
3. In der App unter „Meine Noten“ → „Automatische Umwandlung einrichten“: Benutzername und Schlüssel eintragen, „Verbinden und einrichten“. Die App legt alles Nötige im Repository selbst an.

Danach in einem Stück mit PDF auf **„PDF in Stimmen umwandeln“** tippen. Die erste Umwandlung dauert ca. 5–8 Minuten (GitHub installiert dabei die Erkennung), danach meist 1–3 Minuten. Die App darf in der Zeit geschlossen werden und holt das Ergebnis beim nächsten Öffnen ab.

**Mitlesen in den Noten:** Bei umgewandelten Stücken markiert die App beim Abspielen den aktuellen Takt direkt in der PDF, mit einem Strich, der im Takt mitläuft. Am Zeilenende blättert sie automatisch weiter (abschaltbar unter „Stimmen ▾“ › „Noten mitblättern“). Tippst du auf einen Takt in den Noten, startet die Wiedergabe dort, praktisch zum Üben einzelner Stellen. Stücke, die vor diesem Update umgewandelt wurden, einmal „erneut umwandeln“. Mit **„Nur Noten“** in der Bedienleiste blendet die App alles außer Bedienleiste und Noten aus. Unter der Bedienleiste steht, ob das Mitlesen aktiv ist, und falls nicht, warum.

Gut zu wissen:
- Am besten klappt es mit sauber gesetzten Noten (Verlags- oder Notensatz-PDF). Bei Scans, Fotos, Handschrift oder sehr dichter Partitur entstehen mehr Fehler. Vergleiche das Ergebnis stichprobenartig.
- Bei vier Systemen (S, A, T, B) setzt die App den Tenor automatisch eine Oktave tiefer und benennt die Stimmen. Liegt eine Stimme trotzdem falsch, im Mixer pro Stimme „8va tiefer / höher“ wählen.
- Haltebögen über den Taktstrich werden manchmal nicht erkannt. Der Ton wird dann neu angeschlagen statt gehalten.
- Schlüssel ablaufen lassen oder löschen: github.com/settings/personal-access-tokens. In der App dann „Verbindung entfernen“.
- Läuft etwas schief, steht der Grund im Repository unter „Actions“.

## Wenn das Mikrofon nicht startet

Die App zeigt unter „Live starten“ jetzt den genauen Grund an, mit einem technischen Code in Klammern. Die häufigsten Fälle:

- **NotAllowedError**: Einstellungen → Apps → Safari → Mikrofon auf „Fragen“ stellen. Außerdem in Safari auf der Seite links in der Adressleiste „Aa“ (oder das Menü-Symbol) → „Website-Einstellungen“ → Mikrofon „Fragen“. Danach App schließen und neu öffnen.
- **Erst im normalen Safari-Tab testen**: Klappt es dort, aber nicht über das Home-Bildschirm-Symbol, das Symbol löschen und neu hinzufügen.
- **NotReadableError**: Ein Telefonat, eine Sprachmemo oder eine andere App benutzt gerade das Mikrofon.
- **„nur über https“**: Die Datei wurde direkt geöffnet statt über die GitHub-Adresse.

## Hinweise

- Das Mikrofon wird nur während „Live mitsingen“ benutzt. Nichts wird aufgezeichnet oder hochgeladen, alles bleibt auf dem iPhone.
- Ein Repository mit „Public“ ist für jeden mit der Adresse sichtbar. Inhalte sind nur der Trainer, keine persönlichen Daten.
