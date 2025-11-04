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

```php
// Helper-Klasse verwenden (KLXM\MetaInfoLangFields Namespace)
use KLXM\MetaInfoLangFields\MetainfoLangHelper;

// Wert für aktuelle Sprache
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

### Beispiel: Fallback-Logik
```php
// Mit Fallback auf Standardsprache
use KLXM\MetaInfoLangFields\MetainfoLangHelper;

function getLocalizedValue($jsonData, $clangId = null) {
    $clangId = $clangId ?: rex_clang::getCurrentId();
    
    // Gewünschte Sprache versuchen
    $value = MetainfoLangHelper::getValueForLanguage($jsonData, $clangId);
    
    // Fallback auf Standardsprache
    if (empty($value)) {
        $value = MetainfoLangHelper::getValueForLanguage($jsonData, rex_clang::getStartId());
    }
    
    return $value;
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

## Changelog

### Version 1.0.0
- Initiale Version mit Repeater- und Alle-Sprachen-Modi
- Bootstrap Collapse Integration  
- Vollständige REDAXO-Integration
- Helper-Klasse für Frontend-Ausgabe