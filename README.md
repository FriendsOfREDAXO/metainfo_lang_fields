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
- 🎨 Moderne Bootstrap-basierte UI mit Font Awesome Icons
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

> ⚠️ **Wichtig**: CKE5 funktioniert nur zuverlässig mit `lang_textarea_all` (Alle Sprachen Modus). Im Repeater-Modus können dynamisch hinzugefügte Felder nicht automatisch mit CKE5 initialisiert werden.

> 💡 **Tipp**: Die ursprünglichen CSS-Klassen und Attribute werden automatisch an alle generierten Textareas/Input-Felder weitergegeben!

### Daten im Frontend abrufen

#### Einfache Verwendung (empfohlen)

```php
use KLXM\MetaInfoLangFields\MetainfoLangHelper;

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
// Helper-Klasse verwenden (KLXM\MetaInfoLangFields Namespace)
use KLXM\MetaInfoLangFields\MetainfoLangHelper;

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
use KLXM\MetaInfoLangFields\MetainfoLangHelper;

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
use KLXM\MetaInfoLangFields\MetainfoLangHelper;

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
use KLXM\MetaInfoLangFields\MetainfoLangHelper;

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
- **Helper-Klasse**: `KLXM\MetaInfoLangFields\MetainfoLangHelper` für Datenverarbeitung

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

**Parameter-Details:**
- `$clangId = null` → Verwendet aktuelle Sprache (`rex_clang::getCurrentId()`)
- `$useFallback = true` → Bei leerem Wert wird Standardsprache verwendet
- `$useFallback = false` → Strikt nur gewünschte Sprache, kein Fallback

## Changelog

### Version 1.0.1
- ✨ Neue Helper-Methoden für Artikel, Medien und Kategorien
- 🔄 Automatische Fallback-Mechanismen
- 📖 Erweiterte Dokumentation mit praktischen Beispielen
- 🏗️ KLXM\MetaInfoLangFields Namespace-Organisation

### Version 1.0.0
- Initiale Version mit Repeater- und Alle-Sprachen-Modi
- Bootstrap Collapse Integration  
- Vollständige REDAXO-Integration
- Helper-Klasse für Frontend-Ausgabe