# Blattsingen-App aufs iPhone bringen

Die App ist eine Web-App: Sie läuft in Safari, lässt sich auf den Home-Bildschirm legen und darf dort das Mikrofon nutzen. Dafür muss sie einmal im Internet unter einer https-Adresse liegen. Am einfachsten geht das kostenlos mit GitHub Pages.

## Update einspielen (wenn die App schon online ist)

1. Im GitHub-Repository „Add file“ → „Upload files“.
2. Alle Dateien aus dieser ZIP hineinziehen, **einschließlich der Ordner „lib“ und „uebung“**, und die alten überschreiben, dann „Commit changes“. Das geht nur am Computer: Ordner lassen sich am iPhone nicht hochladen.
   Prüfen: Im Repository muss danach ein Ordner `uebung` mit 31 Dateien stehen (ub01.pdf … ub10.json, index.json).
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

**Einsingen:** Der Bereich „Einsingen“ führt in gut 5 Minuten durch sieben Übungen: Atem, Lippenflattern, Summen, Vokalausgleich, Staccato, Legato-Oktave, Ton halten. Stimmlage wählen, „Ganzes Einsingen“ tippen und gleichzeitig mitsingen. Die App spielt jedes Muster in deiner Lage vor und rückt halbtonweise höher und wieder tiefer. Mit „Nur diese“ übst du eine einzelne Übung.

**Theorie:** Der Bereich „Theorie“ enthält die zehn Kapitel Musiktheorie zum Aufklappen und einen interaktiven Quintenzirkel. Tippe auf eine Tonart: Die App zeigt die Vorzeichen im Violin- und Bassschlüssel, die Moll-Parallele und die Regel, mit der du die Tonart aus den Vorzeichen bestimmst, und spielt auf Wunsch die Tonleiter.

**Trainer im Hochformat:** Die Übungen passen in eine Notenzeile, das Notenbild wird dafür passend verkleinert. Die Tabs stehen in zwei Reihen. Am Anfang jeder Tonleiter und Treffübung stehen die Vorzeichen der Tonart; innerhalb der Tonart braucht es dann keine Versetzungszeichen mehr.

**Tonleiter live:** Aufgebaut wie die Treffübung: „🎤 Live mitsingen“ steht direkt über der Tonleiter, die gesungenen Töne erscheinen als farbige Notenköpfe (grün, gelb, rot) in der Tonleiter selbst. Die bisherige Ansicht mit Cent-Punkten und Tonhöhenkurve öffnest du darunter mit „Detailansicht“. Weiter unten kannst du wie bisher eine Aufnahme prüfen.

**Rhythmus:** Stufe 1–6 (Viertel bis Sechzehntel, Synkopen, 6/8, Triolen). „Anhören“ spielt den Rhythmus vor, „Mitklopfen“ zählt einen Takt ein, dann tippst du auf die große Fläche (oder Leertaste). Jede Note wird grün, gelb oder rot, mit Abweichung in Millisekunden. Bei Bluetooth-Kopfhörern den „Kopfhörer-Ausgleich“ wählen.

**Notennamen:** Eine Note erscheint, du tippst den Namen (C D E F G A H; mit Vorzeichen zuerst ♭/♯ wählen). „60-Sekunden-Sprint“ zählt die richtigen Antworten und merkt sich deinen Rekord. Schlüssel: wie Stimmlage, Violin-, Tenor-, Bassschlüssel oder gemischt.

**Einsingen mit Noten:** Beim Abspielen zeigt die App die Noten der laufenden Übung mit Schlüssel und Tonart-Vorzeichen; der klingende Ton ist farbig markiert.

## 4. Meine Noten

- Abschnitt „Meine Noten“ → „Dateien auswählen“: die PDF eines Stücks und, wenn vorhanden, die passende **MusicXML-** (.musicxml, .xml, .mxl) oder **MIDI-Datei** (.mid). Beide können auf einmal ausgewählt werden.
- Die PDF wird angezeigt (Zoom mit − / +). Die Stimmen spielt die App aus der MusicXML- oder MIDI-Datei, nicht aus der PDF: Eine PDF ist nur ein Bild der Noten.
- „Stimmen ▾“ öffnet den Mixer: **Meine Stimme** wählen, dann „hervorheben“, „nur meine Stimme“ oder „ohne meine Stimme“ (zum Mitsingen). Pro Stimme gibt es Solo, Stumm und Lautstärke. Dazu Tempo, ab/bis Takt, Einzähler und Wiederholen.
- „Einsatzton“ spielt den nächsten Ton deiner Stimme ab der aktuellen Stelle.
- **Tempo** direkt in der Bedienleiste: − / + ändert in 5-%-Schritten, auch während der Wiedergabe; Tippen auf die Prozentzahl setzt auf 100 % zurück. **Metronom** klickt beim Abspielen jeden Schlag mit (Takt-Eins betont).
- Die Stücke bleiben auf dem iPhone gespeichert, auch offline. Wenn du die App vom Home-Bildschirm löschst, sind sie weg.
- Wiederholungen, 1./2. Klammer, D.C., D.S., Coda und Fine spielt die App aus, wenn sie in der Datei stehen (abschaltbar unter „Stimmen ▾“ › „Wiederholungen beachten“). Bei Wiederholungen steht ein Strich hinter der Taktnummer (Takt 5′). Die Erkennung aus der PDF findet Wiederholungszeichen und Klammern, aber keine D.C.- oder Coda-Anweisungen: Dann unter „Eigener Ablauf“ die Takte eintragen, z. B. „1-16, 1-8, 17-32“.

## Übungsblätter

Zehn Ausschnitte von je etwa 30 Sekunden aus gemeinfreien Chorwerken (Bach-Choräle, Palestrina, Monteverdi), nach Schwierigkeit geordnet. Antippen öffnet das Blatt wie ein Stück in „Meine Noten“: Noten, Stimmen einzeln abspielen, klingende Note markiert (deine Stimme aus dem Training, z. B. Bass). Die Blätter haben keinen Liedtext: auf „la“, Silben oder Notennamen singen.

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

**Klingende Note markieren:** Bei Stücken, die mit Version 17 oder neuer umgewandelt wurden, markiert die App beim Abspielen in der PDF genau die Note, die gerade klingt, mit einem Kreis um den Notenkopf. Markiert wird: die unter „Stimmen ▾“ gewählte **Meine Stimme**; ist dort „keine“ gewählt, die Stimme(n) mit **Solo**; sonst die Stimme, die zu deiner **Stimmlage im Training** passt (z. B. Bass). Welche Stimme gerade markiert wird, steht in der Statuszeile unter der Bedienleiste. Ältere Stücke einmal „erneut umwandeln“.

Gut zu wissen:
- Am besten klappt es mit sauber gesetzten Noten (Verlags- oder Notensatz-PDF). Bei Scans, Fotos, Handschrift oder sehr dichter Partitur entstehen mehr Fehler. Vergleiche das Ergebnis stichprobenartig.
- **Zwei Stimmen in einer Notenzeile** (z. B. Sopran/Alt oben, Tenor/Bass unten): Die App teilt die Zeile in zwei Stimmen. Oberer Ton bzw. Hals nach oben = obere Stimme; steht nur ein Ton da, singen ihn beide. Obere Zeile im Violinschlüssel = Sopran/Alt, untere im Bassschlüssel = Tenor/Bass.
- **Liedtext:** Die Umwandlung liest den Text unter den Noten (Texterkennung) und ordnet die Silben den Noten zu. Beim Abspielen läuft der Text deiner Stimme in der Bedienleiste mit, die gerade gesungene Silbe ist hervorgehoben. Mit „Stimmen ▾“ › „Text als Vokale singen“ klingen die Stimmen nach dem Vokal der Silbe (a, e, i, o, u) statt nach Orgel. Echte gesungene Wörter kann die App nicht erzeugen.
- Klavier- oder Orgelsatz unter den Chorstimmen (z. B. „Pianoforte“) erkennt die App an Akkorden und mehrstimmigen Zeilen: Die Zeilen heißen dann „Klavier rechts / links“, zählen nicht als Chorstimmen und sind zunächst stumm (im Mixer einschaltbar).
- Die App erkennt die Besetzung an der Schlüsselfolge (z. B. SATB, SSATB, SATBB, SSAATTBB, Doppelchor), benennt die Stimmen und setzt Tenöre im Violinschlüssel automatisch eine Oktave tiefer. Liegt eine Stimme trotzdem falsch, im Mixer pro Stimme „8va tiefer / höher“ wählen.
- Haltebögen: Die Erkennung liefert sie als Bögen. Die App wertet einen Bogen zwischen zwei gleich hohen, direkt aufeinanderfolgenden Tönen als Haltebogen und spielt einen durchgehenden Ton. Stücke, die vor Version 15 umgewandelt wurden, einmal „erneut umwandeln“.
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
