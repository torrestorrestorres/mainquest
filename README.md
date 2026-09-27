# MainQuest

Website ohne Framework: HTML/CSS/Vanilla-JS (Web Components) im Frontend, PHP + SQLite im Backend.

## Lokal starten

Voraussetzung: PHP 8.2+ mit `pdo_sqlite` und `gd` (`winget install PHP.PHP.8.3`).

```bash
cp app/config.example.php app/config.php   # einmalig
php app/cli/seed.php                        # optional: Demo-Events anlegen
php -S localhost:8000 -t public router.php
```

→ http://localhost:8000

**Am Handy testen:** Server mit `php -S 0.0.0.0:8000 -t public router.php` starten und am Handy (gleiches WLAN) `http://<IP-des-PCs>:8000` öffnen.

## Admin

Unter `/admin/` werden Events gepflegt (Texte, Datum, Titelbild, Galerie). Bilder werden beim Upload automatisch gedreht, auf max. 2000 px verkleinert und als WebP gespeichert.

**Erster Admin:** In `app/config.php` einen langen zufälligen `setup_token` eintragen und `/admin/setup.php?token=<setup_token>` öffnen. Das funktioniert nur, solange es noch keinen Benutzer gibt.

**Weitere Personen:** Admin → Benutzer → einladen. Der Einladungslink geht per Mail raus (falls `mail.enabled`) und wird zusätzlich zum Kopieren angezeigt. Lokal landen Mails in `data/mail.log`.

| Rolle | Darf |
|---|---|
| Editor | Events anlegen, bearbeiten, veröffentlichen, löschen |
| Admin | zusätzlich Benutzer einladen, Rollen ändern, löschen |

Entwürfe sind nur für eingeloggte Benutzer sichtbar (Vorschau).

## Aufbau

```
public/            Web-Verzeichnis (Domain zeigt hierhin)
  index.php        Startseite
  events.php       /events
  event.php        /events/<slug>
  admin/           Admin-Bereich (Login, Events, Benutzer)
  design.css       Variablen (Farben, Größen, Abstände …)
  styles.css       Layout & Komponenten-Styles
  components.js    Web Components (<mq-…>), Navigation (NAV_LINKS)
  effects.js       Seitenweite Effekte (Sticky Nav)
  .htaccess        Routing + Sicherheit auf dem Server
app/               PHP-Code, von außen nicht erreichbar
  bootstrap.php    Einstieg für jede Seite
  config.php       Einstellungen pro Umgebung (nicht in Git!)
  migrations/      Datenbank-Änderungen, laufen automatisch einmal
  views/           Wiederverwendbare Seitenteile
data/              SQLite-Datenbank (nicht in Git)
router.php         Routing für den lokalen PHP-Server
```

**Datenbank ändern:** neue Datei `app/migrations/00X_name.sql` anlegen. Sie läuft beim nächsten Seitenaufruf automatisch – lokal wie auf dem Server. Bestehende Migrationen nie ändern.

## Deploy

Push auf `develop` → Testseite, Push auf `main` → Live-Seite (`.github/workflows/deploy.yml`).

Einmalige Einrichtung, sobald das Hosting steht:

1. Beim Hoster: Subdomain `test.…` anlegen, Dokument-Verzeichnis auf `<ordner>/public` setzen, Passwortschutz aktivieren.
2. Per FTP einmalig `app/config.php` auf dem Server anlegen (`env`, `debug => false`, `base_url`, `setup_token`, `mail` anpassen). Beim Hoster `upload_max_filesize` auf mind. 25M setzen.
3. GitHub → Settings → Secrets and variables → Actions:
   - Secrets: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_DIR_TEST`, `FTP_DIR_LIVE` (mit `/` am Ende)
   - Variable: `DEPLOY_ENABLED` = `true`

Datenbank, Uploads und `config.php` auf dem Server werden vom Deploy nie überschrieben.
