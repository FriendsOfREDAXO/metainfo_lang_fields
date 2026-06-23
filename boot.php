<?php

/**
 * Metainfo Lang Fields Add-on
 * 
 * @package metainfo_lang_fields
 */

$addon = rex_addon::get('metainfo_lang_fields');

if (rex::isBackend()) {
    // Fix für Issue #13: Werte mit Pipe-Symbol (|) werden korrekt zusammengefügt
    // REDAXO-Core zerlegt Werte an jedem |, daher müssen wir sie vorher wieder zusammenfügen
    // Handler mit EARLY-Priorität registrieren, damit er VOR dem Feldtyp-Handler läuft
    rex_extension::register(
        'METAINFO_CUSTOM_FIELD',
        static function (rex_extension_point $ep) {
            $subject = $ep->getSubject();
            
            // Nur für unterstützte lang_* Feldtypen
            $langTypes = ['lang_text', 'lang_textarea', 'lang_text_all', 'lang_textarea_all'];
            
            if (
                isset($subject['type'], $subject['values'])
                && in_array($subject['type'], $langTypes, true)
                && is_array($subject['values'])
                && count($subject['values']) > 1
            ) {
                // Zersplitterte Values wieder zusammenfügen mit Pipe-Symbol
                // Da JSON nie mit | beginnt/endet, ist das verlustfrei
                $joined = implode('|', $subject['values']);
                $subject['values'] = [$joined];
                
                // Auch rawvalues aktualisieren wenn vorhanden
                if (isset($subject['rawvalues']) && is_array($subject['rawvalues'])) {
                    $subject['rawvalues'] = [$joined];
                }
            }
            
            return $subject;
        },
        rex_extension::EARLY
    );
    
    // Assets bei allen relevanten Seiten laden
    $currentPage = rex_request('page', 'string');
    
    // Prüfen ob es eine Seite ist die Metainfo-Felder verwenden könnte
    $metainfoPages = [
        'metainfo/articles',
        'metainfo/categories', 
        'metainfo/media',
        'metainfo/clangs',
        'structure',                // Struktur (Artikel/Kategorien)
        'structure/edit',           // Artikel bearbeiten
        'structure/category',       // Kategorie bearbeiten
        'mediapool',               // Medienpool allgemein
        'mediapool/media',         // Media Detail
        'mediapool/upload',        // Media Upload
        'content',                 // Content-Seiten
        'content/edit'             // Content bearbeiten
    ];
    
    // Auch bei Seiten die mit structure/ oder mediapool/ beginnen
    $loadAssets = in_array($currentPage, $metainfoPages) || 
                  str_starts_with($currentPage, 'structure/') || 
                  str_starts_with($currentPage, 'mediapool/') ||
                  str_starts_with($currentPage, 'content/');
    
    if ($loadAssets) {
        rex_view::addCssFile($addon->getAssetsUrl('metainfo-lang-fields.css'));
        rex_view::addJsFile($addon->getAssetsUrl('metainfo-lang-fields.js'));
        rex_view::addJsFile($addon->getAssetsUrl('metainfo-lang-fields-all.js'));
        rex_extension::register('METAINFO_CUSTOM_FIELD', 'metainfo_lang_fields_custom_field');
    }

    // Hook in OUTPUT_FILTER um die Beschreibungen zu formatieren (Detailansicht)
    rex_extension::register('OUTPUT_FILTER', function(rex_extension_point $ep) {
        $content = $ep->getSubject();
        
        // Null-Check für Content
        if (!$content) {
            return $content;
        }
        
        // Nur auf MediaPool Seiten
        $currentPage = rex_be_controller::getCurrentPage();
        if (!$currentPage || strpos($currentPage, 'mediapool') === false) {
            return $content;
        }
        
        // Verschiedene Patterns für escaped/unescaped JSON
        $patterns = [
            '/<p>\[(\{&quot;clang_id&quot;[^<]+)\]<\/p>/', // HTML-escaped
            '/<p>\[(\{"clang_id"[^<]+)\]<\/p>/', // Nicht escaped
            '/<p>(\[.*?clang_id.*?\])<\/p>/', // Allgemeiner
            '/<p>([^<]*clang_id[^<]*)<\/p>/' // Noch allgemeiner
        ];
        
        foreach ($patterns as $pattern) {
            $matches = [];
            if (preg_match_all($pattern, $content, $matches)) {
                // Verwende das erste funktionierende Pattern
                $content = preg_replace_callback($pattern, function($match) {
                    $jsonString = $match[1] ?? '';
                    
                    // Null/Empty-Check
                    if (!$jsonString) {
                        return $match[0];
                    }
                    
                    // HTML-Entities dekodieren falls nötig
                    if (strpos($jsonString, '&quot;') !== false) {
                        $jsonString = html_entity_decode($jsonString);
                    }
                    
                    // Sicherstellen dass es mit [ beginnt
                    if (!str_starts_with($jsonString, '[')) {
                        $jsonString = '[' . $jsonString . ']';
                    }
                    
                    try {
                        $langData = json_decode($jsonString, true);
                        if (is_array($langData)) {
                            $currentLang = rex_clang::getCurrentId();
                            
                            // Suche aktuelle Sprache
                            foreach ($langData as $entry) {
                                if (isset($entry['clang_id']) && isset($entry['value']) && 
                                    $entry['clang_id'] == $currentLang && !empty($entry['value'])) {
                                    $clang = rex_clang::get($entry['clang_id']);
                                    $langCode = $clang ? strtoupper($clang->getCode()) : 'L' . $entry['clang_id'];
                                    return '<p><strong>' . $langCode . ':</strong> ' . htmlspecialchars($entry['value']) . '</p>';
                                }
                            }
                            
                            // Fallback: erste verfügbare Sprache
                            foreach ($langData as $entry) {
                                if (isset($entry['clang_id']) && isset($entry['value']) && !empty($entry['value'])) {
                                    $clang = rex_clang::get($entry['clang_id']);
                                    $langCode = $clang ? strtoupper($clang->getCode()) : 'L' . $entry['clang_id'];
                                    return '<p><strong>' . $langCode . ':</strong> ' . htmlspecialchars($entry['value']) . '</p>';
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // Bei Fehlern das Original zurückgeben
                    }
                    return $match[0];
                }, $content);
                
                break; // Verwende nur das erste funktionierende Pattern
            }
        }
        
        return $content;
    });

    // Register extension point specifically for MediaPool media list rendering
    rex_extension::register('PAGES_PREPARED', function() {
        if (rex::isBackend() && rex_be_controller::getCurrentPage() === 'mediapool/media') {
            // Hook into MediaPool output rendering
            rex_extension::register('OUTPUT_FILTER', function(rex_extension_point $ep) {
                $content = $ep->getSubject();
                
                // Only process if we have table content and clang_id patterns
                if (strpos($content, '<table') !== false && strpos($content, 'clang_id') !== false) {
                    // Pattern for JSON strings in the content
                    $patterns = [
                        '/\[{"clang_id"[^\]]*}\]/',
                        '/\[{&quot;clang_id&quot;[^\]]*}\]/',
                    ];
                    
                    foreach ($patterns as $pattern) {
                        if (preg_match_all($pattern, $content, $matches)) {
                            $content = preg_replace_callback($pattern, function($match) {
                                $jsonString = $match[0];
                                
                                // HTML-Entities dekodieren
                                $jsonString = html_entity_decode($jsonString);
                                
                                try {
                                    $langData = json_decode($jsonString, true);
                                    
                                    if (is_array($langData)) {
                                        $currentLang = rex_clang::getCurrentId();
                                        
                                        // Suche aktuelle Sprache
                                        foreach ($langData as $entry) {
                                            if (isset($entry['clang_id']) && isset($entry['value']) && 
                                                $entry['clang_id'] == $currentLang && !empty($entry['value'])) {
                                                $clang = rex_clang::get($entry['clang_id']);
                                                $langCode = $clang ? strtoupper($clang->getCode()) : 'L' . $entry['clang_id'];
                                                return '<strong>' . $langCode . ':</strong> ' . htmlspecialchars($entry['value']);
                                            }
                                        }
                                        
                                        // Fallback: erste verfügbare Sprache
                                        foreach ($langData as $entry) {
                                            if (isset($entry['clang_id']) && isset($entry['value']) && !empty($entry['value'])) {
                                                $clang = rex_clang::get($entry['clang_id']);
                                                $langCode = $clang ? strtoupper($clang->getCode()) : 'L' . $entry['clang_id'];
                                                return '<strong>' . $langCode . ':</strong> ' . htmlspecialchars($entry['value']);
                                            }
                                        }
                                        
                                        // Keine Werte gefunden - leere JSON-Struktur
                                        return '<em>Keine Beschreibung</em>';
                                    }
                                } catch (Exception $e) {
                                    // Bei JSON-Fehlern Original beibehalten
                                }
                                
                                return $match[0];
                            }, $content);
                            
                            break; // Stop after first successful pattern
                        }
                    }
                }
                
                return $content;
            }, rex_extension::LATE); // Use LATE priority to ensure it runs after other processing
        }
    });
}





/**
 * Handler für METAINFO_CUSTOM_FIELD Extension Point
 */
function metainfo_lang_fields_custom_field(rex_extension_point $ep)
{
    $subject = $ep->getSubject();
    
    // Prüfen ob es ein unterstützter Feldtyp ist
    if (!isset($subject['type']) || !in_array($subject['type'], ['lang_text', 'lang_textarea', 'lang_text_all', 'lang_textarea_all'])) {
        return $subject;
    }
    
    $type = $subject['type'];
    $fieldName = str_replace('rex-metainfo-', '', $subject[3]);
    $fieldValue = $subject['values'][0] ?? '';
    $fieldId = $subject[3];
    $fieldLabel = $subject[4];
    
    // Attribute aus dem SQL-Objekt extrahieren 
    $fieldAttributes = '';
    $fieldClass = 'form-control';
    $additionalAttributes = [];
    
    if (isset($subject['sql']) && $subject['sql'] instanceof rex_sql) {
        $attributes = $subject['sql']->getValue('attributes');
        if (!empty($attributes)) {
            $fieldAttributes = $attributes;
            
            // CSS-Klassen aus Attributen extrahieren
            if (preg_match('/class="([^"]*)"/', $attributes, $matches)) {
                $fieldClass = $matches[1];
            } elseif (preg_match("/class='([^']*)'/", $attributes, $matches)) {
                $fieldClass = $matches[1];
            }
            
            // Alle anderen Attribute (data-*, id, etc.) extrahieren
            // Entferne class-Attribute und behalte den Rest
            $remainingAttributes = preg_replace('/class=("[^"]*"|\'[^\']*\')/', '', $attributes);
            $remainingAttributes = trim($remainingAttributes);
            
            if (!empty($remainingAttributes)) {
                // Attribute in Array parsen für bessere Handhabung
                preg_match_all('/(\w+(?:-\w+)*)=("[^"]*"|\'[^\']*\')/', $remainingAttributes, $attrMatches, PREG_SET_ORDER);
                foreach ($attrMatches as $match) {
                    $attrName = $match[1];
                    $attrValue = trim($match[2], '"\'');
                    $additionalAttributes[$attrName] = $attrValue;
                }
            }
        }
    }
    
    // Fragment für die Ausgabe verwenden
    $fragment = new rex_fragment();
    $fragment->setVar('fieldName', $fieldName);
    $fragment->setVar('fieldValue', $fieldValue);
    $fragment->setVar('fieldId', $fieldId);
    $fragment->setVar('fieldLabel', $fieldLabel);
    $fragment->setVar('fieldAttributes', $fieldAttributes);
    $fragment->setVar('fieldClass', $fieldClass);
    $fragment->setVar('additionalAttributes', $additionalAttributes);
    
    // Feldtyp bestimmen und entsprechendes Fragment wählen
    if (str_contains($type, '_all')) {
        // Alle Sprachen Modus
        $fragment->setVar('fieldType', str_replace(['lang_', '_all'], '', $type)); // 'text' oder 'textarea'
        $fragmentFile = 'metainfo_lang_field_all.php';
    } else {
        // Repeater Modus
        $fragment->setVar('fieldType', str_replace('lang_', '', $type)); // 'text' oder 'textarea'
        $fragmentFile = 'metainfo_lang_field.php';
    }
    
    $html = $fragment->parse($fragmentFile);
    $subject[0] = $html;
    return $subject;
}

