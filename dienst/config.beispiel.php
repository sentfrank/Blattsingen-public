<?php
// ===== Einstellungen für den Noten-Umwandlungsdienst (nur diese Datei anpassen) =====

// GitHub: Benutzername/Repository des Umwandlungs-Repositorys
const GH_REPO  = 'DEINNAME/blattsingen-konverter';
// GitHub-Zugangsschlüssel (fine-grained, nur dieses Repository, Rechte: Actions = Read and write)
const GH_TOKEN = 'github_pat_HIER_EINTRAGEN';
// Geheimer Schlüssel für die Rückmeldung von GitHub. Beliebige lange Zeichenfolge,
// GENAU GLEICH als Secret "SERVER_KEY" im GitHub-Repository eintragen.
const SERVER_KEY = 'HIER_EINE_LANGE_ZUFAELLIGE_ZEICHENFOLGE';

// ----- Zugang zur Umwandlung -----
// 'konto' = Umwandeln nur mit Benutzerkonto (E-Mail + Passwort), Zählung pro Konto
// 'email' = wie bisher: E-Mail aus der App-Anmeldung genügt (ohne Passwort)
const ZUGANG = 'konto';
// Passwort für die Verwaltungsseite admin.php (mindestens 10 Zeichen, frei erfunden)
const ADMIN_PASSWORT = 'HIER_EIN_ADMIN_PASSWORT';
// Deine E-Mail (die deines App-Kontos): Die App zeigt dir dann den Knopf „Verwaltung“
const ADMIN_EMAIL = '';
// Dürfen sich Nutzer in der App selbst ein Konto anlegen? (false = nur du legst Konten in admin.php an)
const SELBST_REGISTRIEREN = true;
// Optional: Einladungscode, den man beim Selbst-Anlegen eingeben muss ('' = kein Code nötig)
// So können sich nur Leute registrieren, denen du den Code gibst (z. B. dein Chor).
const EINLADUNGSCODE = '';

// ----- Tarife (Umwandlungen pro Monat, 0 = unbegrenzt) -----
// Neue Konten bekommen TARIF_NEU. Einen anderen Tarif vergibst du in admin.php,
// wahlweise mit Ablaufdatum („Pro bis 31.12.2027“) – danach gilt wieder TARIF_NEU.
const TARIFE = [
  'frei'       => ['name' => 'Basis',      'limit' => 5],
  'pro'        => ['name' => 'Pro',        'limit' => 100],
  'unbegrenzt' => ['name' => 'Unbegrenzt', 'limit' => 0],
];
const TARIF_NEU = 'frei';
// Wird in der App gezeigt, wenn das Kontingent aufgebraucht ist (leer lassen = kein Hinweis).
// Später z. B. ein PayPal-/Stripe-Zahlungslink; bis dahin z. B. 'mailto:du@example.org?subject=Blattsingen%20Pro'
const PRO_LINK = '';
const PRO_TEXT = 'Mehr Umwandlungen mit Blattsingen Pro';
// Für eine spätere automatische Bezahlung (Zahlungsanbieter / Zapier / Make): geheimer Schlüssel
// für den Aufruf api.php?a=tarif. Leer = ausgeschaltet.
const ZAHLUNG_KEY = '';

// ----- Geräte-Abgleich -----
// Stücke, PDFs und Projekte werden je Konto auf dem Server gesichert und erscheinen auf jedem Gerät,
// auf dem man sich anmeldet (nur mit ZUGANG = 'konto'). false = ausgeschaltet.
const SYNC_AN = true;
// Speicherplatz je Konto in MB
const SYNC_MB = 300;

// ----- Sonstiges -----
// Von welcher Adresse die App den Dienst benutzen darf ('*' = von überall)
const ALLOW_ORIGIN = '*';
// Nur für ZUGANG = 'email': Umwandlungen pro E-Mail-Adresse und Monat (0 = unbegrenzt)
const LIMIT_PER_MONTH = 20;
// Gesperrte E-Mail-Adressen (klein geschrieben), z. B. ['jemand@example.org']
const BLOCKED = [];
// Größte PDF in MB
const MAX_MB = 30;

// Nur für die Strato-Fassung ohne SFTP (aktualisieren.php): geheimer Schlüssel, GENAU GLEICH als Secret
// "STRATO_DEPLOY_KEY" im App-Repository eintragen. Leer lassen, wenn du per SFTP hochlädst.
const DEPLOY_KEY = '';
