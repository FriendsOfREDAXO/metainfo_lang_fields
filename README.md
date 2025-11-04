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

### Daten im Frontend abrufen

```php
// Helper-Klasse verwenden
$helper = new MetainfoLangHelper();

// Wert für aktuelle Sprache
$value = $helper::getValueForLanguage(
    $article->getValue('art_title_lang'), 
    rex_clang::getCurrentId()
);

// Prüfen ob Übersetzung existiert
if ($helper::hasTranslationForLanguage($article->getValue('art_title_lang'), 2)) {
    echo 'Englische Übersetzung vorhanden';
}

// Alle Übersetzungen abrufen
$allTranslations = $helper::normalizeLanguageData($article->getValue('art_title_lang'));
foreach ($allTranslations as $translation) {
    $langName = rex_clang::get($translation['clang_id'])->getName();
    echo $langName . ': ' . $translation['value'] . '<br>';
}
```

### Beispiel: Fallback-Logik
```php
// Mit Fallback auf Standardsprache
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
- **Helper-Klasse**: `MetainfoLangHelper` für Datenverarbeitung

## Support

Bei Fragen oder Problemen erstellen Sie gerne ein Issue im Repository.

## Changelog

### Version 1.0.0
- Initiale Version mit Repeater- und Alle-Sprachen-Modi
- Bootstrap Collapse Integration  
- Vollständige REDAXO-Integration
- Helper-Klasse für Frontend-Ausgabe