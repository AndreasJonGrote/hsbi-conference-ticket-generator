# HSBI Conference Ticket Generator

Ein WordPress-Plugin für die Erstellung personalisierter Konferenz-Tickets mit umfassenden Datenschutz-Features.

## 🎯 Funktionen

### Ticket-Generator
- **4-Schritt-Prozess**: Zeichnen → Vorschau → Bestätigung → Erfolg
- **Interaktives Canvas**: Freihand-Zeichnung mit verschiedenen Tools
- **Responsive Design**: Funktioniert auf Desktop und Mobile
- **Auto-Save**: Formulardaten werden automatisch gespeichert

### Design-Features
- **Grid-Auflösung**: Anpassbare Raster-Größe
- **Farbauswahl**: Vollständige Farbpalette
- **Undo/Redo**: Rückgängig/Wiederholen-Funktionen
- **Zufalls-Design**: Automatische Design-Generierung
- **Overlay-Bild**: Post-Photographic Images Overlay

### Ticket-Erstellung
- **Personalisiert**: Name und Organisation auf dem Ticket
- **Zwei Dateien**: Pattern + vollständiges Ticket (PNG)
- **Eindeutige IDs**: Jedes Ticket erhält eine UID
- **Opt-in-System**: E-Mail-Bestätigung erforderlich

## 🔒 Datenschutz-Features

### DSGVO-konforme Datenverarbeitung
- **Opt-in-Pflicht**: Nur bestätigte Anmeldungen werden verarbeitet
- **E-Mail-Bestätigung**: Doppelte Bestätigung über E-Mail-Link
- **Anonymisierung**: Nicht-Opt-in-Daten werden in der Admin-Ansicht anonymisiert
- **Export-Filter**: Nur Opt-in-Daten werden exportiert

### Anonymisierungs-Techniken
- **Name-Anonymisierung**: Zufällige Buchstaben a-z mit erhaltener Groß-/Kleinschreibung
- **E-Mail-Anonymisierung**: Struktur erhalten (@ und . bleiben), zufällige Buchstaben
- **Blur-Effekt**: Anonymisierte Daten werden in der Admin-Ansicht unscharf dargestellt
- **Konsistente Anonymisierung**: Gleiche Eingabe = gleiche anonymisierte Ausgabe

### Datenbank-Sicherheit
- **Eigene Settings-Tabelle**: Alle Einstellungen in separater Tabelle
- **Verschlüsselte Speicherung**: Sichere Datenbank-Integration
- **Berechtigungsprüfung**: Nur Administratoren haben Zugriff
- **Nonce-Validierung**: CSRF-Schutz für alle Aktionen

## 📧 E-Mail-System

### Opt-in-Workflow
1. **Initial E-Mail**: Enthält Bestätigungslink
2. **Opt-in-Link**: Führt zur Bestätigungsseite
3. **Bestätigungs-E-Mail**: Mit Ticket als Anhang
4. **Danke-Seite**: Bestätigung der Anmeldung

### E-Mail-Templates
- **Konfigurierbar**: Alle Texte über Admin-Interface anpassbar
- **Platzhalter**: {name}, {optin_link} werden automatisch ersetzt
- **WYSIWYG-Editor**: Für Danke-Seite mit HTML-Unterstützung
- **Mehrsprachig**: Englische Standard-Texte

## 🛠 Admin-Interface

### Übersichtsseiten
- **Ticket-Liste**: Alle erstellten Tickets mit Filter-Optionen
- **Opt-in-Filter**: "Alle" vs. "Nur Opt-in" Ansicht
- **Bild-Vorschau**: Pattern und Ticket-Bilder
- **Lösch-Funktion**: Einzelne Tickets entfernen

### Export-Funktionen
- **CSV-Export**: Nur Opt-in-Daten (datenschutz-konform)
- **Bilder-Export**: Nur Opt-in-Bilder als ZIP
- **Kombinierter Export**: CSV + Bilder zusammen
- **Dateinamen**: Klar als "optin"-Daten gekennzeichnet

### Einstellungen
- **E-Mail-Templates**: Alle E-Mail-Texte konfigurierbar
- **Danke-Seite**: WYSIWYG-Editor für HTML-Content
- **Vorlagen**: Standard-Templates zum Einfügen
- **Speicherung**: In eigener Settings-Tabelle

## 🔧 Technische Details

### WordPress-Integration
- **Shortcode**: `[hsbi_ticket_generator]` für Frontend
- **Admin-Menü**: Vollständige Backend-Integration
- **AJAX-Endpoints**: Für Ticket-Erstellung und -Verwaltung
- **Hooks**: WordPress-Standard für Aktivierung/Deaktivierung

### Datenbank-Struktur
```sql
-- Tickets-Tabelle
wp_hsbi_tickets (id, uid, name, email, organization, created_at, pattern_file, ticket_file, optin)

-- Settings-Tabelle  
wp_hsbi_settings (id, setting_key, setting_value, created_at, updated_at)
```

### Sicherheits-Features
- **Honeypot**: Bot-Schutz durch verstecktes Feld
- **Nonce-Validierung**: CSRF-Schutz
- **Berechtigungsprüfung**: current_user_can('manage_options')
- **Input-Sanitization**: Alle Eingaben werden bereinigt

## 📱 Frontend-Features

### Canvas-Editor
- **Freihand-Zeichnung**: Mit Maus/Touch
- **Farbauswahl**: Vollständige Farbpalette
- **Grid-System**: Anpassbare Raster-Größe
- **Undo/Redo**: Vollständige Historie
- **Responsive**: Mobile-optimiert

### Formular-Management
- **Auto-Save**: Session-basierte Speicherung
- **Validierung**: Client- und Server-seitig
- **Fehlerbehandlung**: Benutzerfreundliche Meldungen
- **Progress-Indicator**: 4-Schritt-Anzeige

## 🚀 Installation

1. Plugin in WordPress-Verzeichnis hochladen
2. Plugin aktivieren
3. Datenbank-Tabellen werden automatisch erstellt
4. Shortcode `[hsbi_ticket_generator]` auf gewünschter Seite einfügen
5. Admin-Menü für Konfiguration nutzen

## ⚙️ Konfiguration

### E-Mail-Einstellungen
- **Opt-in E-Mail**: Text für Bestätigungslink-E-Mail
- **Danke-Seite**: HTML-Content für Bestätigungsseite  
- **Bestätigungs-E-Mail**: Text für Ticket-Versand-E-Mail

### Datenschutz-Einstellungen
- **Anonymisierung**: Automatisch für nicht-Opt-in-Daten
- **Export-Filter**: Nur bestätigte Daten werden exportiert
- **Blur-Effekt**: Visuelle Kennzeichnung anonymisierter Daten

## 🔍 Verwendung

### Für Benutzer
1. Seite mit Shortcode besuchen
2. Canvas zeichnen und Formular ausfüllen
3. E-Mail-Bestätigung abwarten
4. Bestätigungslink klicken
5. Ticket per E-Mail erhalten

### Für Administratoren
1. Admin-Menü "HSBI Tickets" öffnen
2. Tickets verwalten und exportieren
3. E-Mail-Templates konfigurieren
4. Datenschutz-Einstellungen überwachen

## 📊 Datenschutz-Compliance

- **DSGVO-konform**: Vollständige Opt-in-Pflicht
- **Datenminimierung**: Nur notwendige Daten werden gespeichert
- **Anonymisierung**: Nicht-Opt-in-Daten werden geschützt
- **Export-Kontrolle**: Nur bestätigte Daten werden exportiert
- **Transparenz**: Klare Kennzeichnung aller Datenverarbeitung

## 🔐 Benutzerrechte und Berechtigungen

### Administrator (manage_options)
- **Vollzugriff**: Alle Funktionen verfügbar
- **Export-Funktionen**: CSV, Bilder, kombinierter Export
- **Einstellungen**: E-Mail-Templates konfigurieren
- **Ticket-Verwaltung**: Ansehen, löschen, exportieren

### Editor (edit_posts)
- **Ticket-Übersicht**: Alle Tickets einsehen
- **Einstellungen**: E-Mail-Templates anpassen
- **Shortcode-Info**: Verwendungsanleitung
- **Ticket ansehen**: Modal mit Ticket-Details
- **Kein Export**: Keine Download-Funktionen

### Autor und niedriger
- **Kein Zugriff**: Plugin-Admin-Bereich nicht verfügbar
- **Frontend**: Shortcode funktioniert normal

### Berechtigungsmatrix

| Funktion | Administrator | Editor | Autor |
|----------|---------------|--------|-------|
| **Ticket-Übersicht** | ✅ | ✅ | ❌ |
| **Ticket ansehen** | ✅ | ✅ | ❌ |
| **E-Mail-Einstellungen** | ✅ | ✅ | ❌ |
| **Shortcode-Info** | ✅ | ✅ | ❌ |
| **CSV-Download** | ✅ | ❌ | ❌ |
| **Bilder-Export** | ✅ | ❌ | ❌ |
| **Kombinierter Export** | ✅ | ❌ | ❌ |

---

**Entwickelt für die Postphotographic Images Conference an der HSBI**
