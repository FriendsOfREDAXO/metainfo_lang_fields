# Metainfo Lang Fields

Ein REDAXO Add-on für mehrsprachige Metainfo-Felder mit zwei verschiedenen Benutzeroberflächen.

## Features

### Feldtypen
- **Repeater-Modus**: `lang_text` / `lang_textarea` - Sprachen dynamisch hinzufügen/entfernen
- **Alle Sprachen Modus**: `lang_text_all` / `lang_textarea_all` - Alle Sprachfelder mit Bootstrap Collapse

### Funktionen
- 🌍 Mehrsprachige Text- und Textarea-Felder für Metainfo
- 📝 JSON-basierte Speicherung der Sprachdaten  
- 🎛️ Zwei verschiedene Benutzeroberflächen je nach Bedarf
- 🔧 Nahtlose Integration in alle REDAXO-Bereiche (Struktur, Medienpool, Content)
- 📱 Responsive Design
- 🚀 Praktische Helper-Methoden für Frontend-Ausgabe (Artikel, Medien, Kategorien)
- 🔄 Automatische Fallback-Mechanismen auf Standardsprache

## Installation

1. Add-on über den REDAXO-Installer oder manuell installieren
2. Add-on aktivieren
3. In der Metainfo-Verwaltung stehen die neuen Feldtypen zur Verfügung

## Verwendung

### Feldtypen erstellen

**Repeater-Modus** (dynamisch):
- Feldtyp: `lang_text` oder `lang_textarea`
- Sprachen können zur Laufzeit hinzugefügt/entfernt werden
- Ideal für selektive Übersetzungen

**Alle Sprachen Modus** (fest):
- Feldtyp: `lang_text_all` oder `lang_textarea_all` 
- Alle Sprachen sind als Felder vorhanden
- Erste Sprache sichtbar, weitere per Globus-Button einblendbar

### CKE5 Integration 🚀

Das Add-on unterstützt CKE5 im **"Alle Sprachen Modus"** (`lang_textarea_all`). Verwende einfach die CKE5 CSS-Klassen im "Attribute" Feld:

**Einfache CKE5 Integration**:
```
class="form-control cke5-editor"
```

**Mit spezifischem CKE5 Profil**:
```
class="form-control cke5-editor" data-profile="full"
```

**Beispiel-Feld für mehrsprachige CKE5-Inhalte**:
- Feldtyp: `lang_textarea_all` ⚠️ **Nur "Alle Sprachen" Modus!**
- Name: `art_content_rich`
- Attribute: `class="form-control cke5-editor" data-profile="default"`
- ✅ Ergebnis: Mehrsprachige Rich-Text-Felder mit CKE5 Editor

**Ergebnis-HTML** (automatisch generiert):
```html
<textarea class="cke5-editor lang-field-input" 
          data-clang-id="1" 
          rows="6" 
          placeholder="Deutsch Text..." 
          data-profile="default">Inhalt...</textarea>
```

> ⚠️ **Wichtig**: CKE5 funktioniert nur zuverlässig mit `lang_textarea_all` (Alle Sprachen Modus). Im Repeater-Modus wird für dynamisch (per JS) hinzugefügte Sprachen kein CKE5-Editor initialisiert, auch wenn die Attribute korrekt übernommen werden (siehe unten) — Grund ist, dass der Editor beim Hinzufügen einer neuen Zeile nicht erneut aufgerufen wird.

> 💡 **Tipp**: Die ursprünglichen CSS-Klassen und Attribute (auch wertlose wie `readonly` oder `disabled`) werden an alle initial gerenderten und alle per JS dynamisch hinzugefügten Textareas/Input-Felder weitergegeben.

### Daten im Frontend abrufen

#### Einfache Verwendung (empfohlen)

```php
use FriendsOfRedaxo\MetaInfoLangFields\MetainfoLangHelper;

// 📄 ARTIKEL-WERTE
$articleTitle = MetainfoLangHelper::getArticleValue($article, 'art_title_lang');
$englishTitle = MetainfoLangHelper::getArticleValue(123, 'art_title_lang', 2); // Artikel-ID + Englisch
$strictTitle = MetainfoLangHelper::getArticleValue($article, 'art_title_lang', null, false); // Ohne Fallback

// 🖼️ MEDIUM-WERTE  
$mediaTitle = MetainfoLangHelper::getMediaValue($media, 'med_title_lang');
$mediaDesc = MetainfoLangHelper::getMediaValue('image.jpg', 'med_description_lang', 2); // Dateiname + Englisch

// 📁 KATEGORIE-WERTE
$categoryTitle = MetainfoLangHelper::getCategoryValue($category, 'cat_title_lang');
$categoryDesc = MetainfoLangHelper::getCategoryValue(456, 'cat_description_lang', 3); // Kategorie-ID + Französisch
```

#### Erweiterte Verwendung

```php
// Helper-Klasse verwenden (FriendsOfRedaxo\MetaInfoLangFields Namespace)
use FriendsOfRedaxo\MetaInfoLangFields\MetainfoLangHelper;

// Wert für aktuelle Sprache (Low-Level)
$value = MetainfoLangHelper::getValueForLanguage(
    $article->getValue('art_title_lang'), 
    rex_clang::getCurrentId()
);

// Prüfen ob Übersetzung existiert
if (MetainfoLangHelper::hasTranslationForLanguage($article->getValue('art_title_lang'), 2)) {
    echo 'Englische Übersetzung vorhanden';
}

// Alle Übersetzungen abrufen
$allTranslations = MetainfoLangHelper::normalizeLanguageData($article->getValue('art_title_lang'));
foreach ($allTranslations as $translation) {
    $langName = rex_clang::get($translation['clang_id'])->getName();
    echo $langName . ': ' . $translation['value'] . '<br>';
}
```

### Praktische Beispiele

#### Template-Verwendung
```php
use FriendsOfRedaxo\MetaInfoLangFields\MetainfoLangHelper;

// Artikel-Titel mit automatischem Fallback
$title = MetainfoLangHelper::getArticleValue($this, 'art_title_lang');
if (empty($title)) {
    $title = $this->getName(); // Standard REDAXO-Titel als Fallback
}

// Medienpool-Integration
$media = rex_media::get('hero-image.jpg');
$altText = MetainfoLangHelper::getMediaValue($media, 'med_alt_lang');
$caption = MetainfoLangHelper::getMediaValue($media, 'med_caption_lang');

echo '<img src="' . $media->getUrl() . '" alt="' . rex_escape($altText) . '">';
echo '<figcaption>' . rex_escape($caption) . '</figcaption>';
```

#### Navigations-Beispiel
```php
use FriendsOfRedaxo\MetaInfoLangFields\MetainfoLangHelper;

// Mehrsprachige Navigation
$navigation = rex_navigation::factory();
$navItems = $navigation->get(1, 2); // Kategorie 1, Tiefe 2

foreach ($navItems as $item) {
    $categoryTitle = MetainfoLangHelper::getCategoryValue($item['id'], 'cat_nav_title_lang');
    
    // Fallback auf Standard-Namen wenn kein mehrsprachiger Titel
    if (empty($categoryTitle)) {
        $categoryTitle = $item['name'];
    }
    
    echo '<a href="' . $item['url'] . '">' . rex_escape($categoryTitle) . '</a>';
}
```

#### Sprachspezifische Inhalte ohne Fallback
```php
use FriendsOfRedaxo\MetaInfoLangFields\MetainfoLangHelper;

// Nur Deutsche Inhalte anzeigen (kein Fallback)
$germanContent = MetainfoLangHelper::getArticleValue($article, 'art_content_lang', 1, false);

if (!empty($germanContent)) {
    echo '<div class="german-only">' . $germanContent . '</div>';
} else {
    echo '<div class="no-translation">Noch nicht übersetzt</div>';
}
```

## Systemvoraussetzungen

- **REDAXO** >= 5.15
- **PHP** >= 8.1  
- **Metainfo Add-on** >= 2.11

## Kompatibilität

Das Add-on funktioniert in allen REDAXO-Bereichen:
- ✅ Struktur (Artikel/Kategorien bearbeiten)
- ✅ Medienpool (Media-Details) 
- ✅ Content-Bereiche
- ✅ Metainfo-Verwaltung

## Technische Details

- **Datenformat**: JSON mit `clang_id` und `value` Objekten
- **Frontend**: Bootstrap 3 + Font Awesome 6 + jQuery
- **Backend**: REDAXO Extension Points (`METAINFO_CUSTOM_FIELD`)
- **Helper-Klasse**: `FriendsOfRedaxo\MetaInfoLangFields\MetainfoLangHelper` für Datenverarbeitung

## Support

Bei Fragen oder Problemen erstellen Sie gerne ein Issue im Repository.

## API-Referenz

### Helper-Methoden Übersicht

| Methode | Parameter | Beschreibung |
|---------|-----------|--------------|
| `getArticleValue($article, $fieldName, $clangId, $useFallback)` | Artikel-Objekt/ID, Feldname, Sprach-ID (optional), Fallback (optional) | Mehrsprachigen Artikel-Wert abrufen |
| `getMediaValue($media, $fieldName, $clangId, $useFallback)` | Medium-Objekt/Dateiname, Feldname, Sprach-ID (optional), Fallback (optional) | Mehrsprachigen Medium-Wert abrufen |
| `getCategoryValue($category, $fieldName, $clangId, $useFallback)` | Kategorie-Objekt/ID, Feldname, Sprach-ID (optional), Fallback (optional) | Mehrsprachigen Kategorie-Wert abrufen |
| `getValueForLanguage($data, $clangId)` | JSON-Daten, Sprach-ID | Low-Level: Wert für bestimmte Sprache |
| `hasTranslationForLanguage($data, $clangId)` | JSON-Daten, Sprach-ID | Prüft ob Übersetzung existiert |
| `normalizeLanguageData($data)` | JSON-Daten | Normalisiert und validiert Sprachdaten |
| `getActiveLanguages()` | – | Alle konfigurierten Sprachen, **inklusive** offline geschalteter (Bestandsverhalten) |
| `getOnlineLanguages()` | – | Nur online geschaltete Sprachen |
| `getAvailableLanguages($existingData)` | JSON-Daten | Sprachen, für die noch keine Übersetzung existiert (für den "Neue Sprache hinzufügen"-Dialog) |

**Parameter-Details:**
- `$clangId = null` → Verwendet aktuelle Sprache (`rex_clang::getCurrentId()`)
- `$useFallback = true` → Bei leerem Wert wird Standardsprache verwendet
- `$useFallback = false` → Strikt nur gewünschte Sprache, kein Fallback

## Author

**Friends Of REDAXO**

* http://www.redaxo.org
* https://github.com/FriendsOfREDAXO

## Credits

**Project Lead**

<a href="https://github.com/skerbis">Thomas Skerbis</a>

## Changelog

### Version 1.0.9
- 🐛 **Bugfix**: Ein gespeicherter Übersetzungswert `"0"` wurde beim Lesen (`getArticleValue()`/`getMediaValue()`/`getCategoryValue()`/`hasTranslationForLanguage()`) fälschlich als "keine Übersetzung" behandelt und durch den Fallback-Wert ersetzt (PHPs `empty("0") === true`-Falle)
- 🐛 **Bugfix**: Per JS dynamisch hinzugefügte Sprachen im Repeater-Modus (`lang_text`/`lang_textarea`) übernahmen die im Metainfo-Feld konfigurierten CSS-Klassen und Attribute nicht — nur initial gerenderte Sprachen waren betroffen
- 🐛 **Bugfix**: `uninstall.php` entfernte nur die Feldtypen `lang_text`/`lang_textarea` aus der Metainfo-Verwaltung, nicht aber `lang_text_all`/`lang_textarea_all` — verwaiste Einträge nach Deinstallation
- 🐛 **Bugfix**: Attribute ohne Wert (`readonly`, `disabled`, …) aus dem Metainfo-Attribute-Feld wurden von einer eigenen Regex ignoriert; die Attribut-Erkennung nutzt jetzt dieselbe Logik (`rex_string::split()`) wie das Metainfo-Addon selbst
- 🌍 Vollständige i18n-Unterstützung: alle Backend-Texte (Buttons, Platzhalter, Meldungen) laufen jetzt über `rex_i18n`/Sprachdateien statt fest auf Deutsch verdrahtet zu sein
- 🔧 Zwei redundante `OUTPUT_FILTER`-Handler für die Mediapool-Anzeige (Detail- und Listenansicht) nutzen jetzt eine gemeinsame Kernfunktion statt doppelt gepflegter Regex-Logik
- 🔧 `getActiveLanguages()` bleibt aus Kompatibilitätsgründen unverändert (liefert weiterhin auch offline Sprachen); neue Methode `getOnlineLanguages()` für nur online geschaltete Sprachen ergänzt
- 🔧 PHP-8.1-Deprecation behoben: implizit nullable Parameter (`int $x = null`) durch `?int $x = null` ersetzt

### Version 1.0.8
- 🐛 **Bugfix**: Sprachfelder blieben in Kontexten außerhalb einer festen Seiten-Allowlist (z.B. eigene `rex-api-call`-Endpunkte anderer Add-ons, die Metainfo-Formulare rendern) leer, da Feldhandler und Assets nur auf bekannten Backend-Seiten registriert wurden. Registrierung erfolgt jetzt unconditional bei jedem Backend-Request
- 🐛 **Bugfix**: Mehrfaches Einbinden von `boot.php` im selben Prozess (z.B. bei kombinierten Page- + `rex-api-call`-Requests wie REDAXOs Session-Status-Ping) führte zu einer Exception beim erneuten Registrieren derselben Assets; jetzt gegen doppeltes Booten abgesichert

### Version 1.0.7
- 🐛 **Bugfix**: Werte mit Pipe-Symbol (`|`) werden korrekt gespeichert und angezeigt (#13)
- 🔧 Extension Point `METAINFO_CUSTOM_FIELD` mit `EARLY` Priorität registriert für zuverlässige Datenbehandlung

### Version 1.0.1
- ✨ Neue Helper-Methoden für Artikel, Medien und Kategorien
- 🔄 Automatische Fallback-Mechanismen
- 📖 Erweiterte Dokumentation mit praktischen Beispielen
- 🏗️ FriendsOfRedaxo\MetaInfoLangFields Namespace-Organisation

### Version 1.0.0
- Initiale Version mit Repeater- und Alle-Sprachen-Modi
- Bootstrap Collapse Integration  
- Vollständige REDAXO-Integration
- Helper-Klasse für Frontend-Ausgabe
