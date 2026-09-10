<?php

/**
 * Metainfo Lang Fields Add-on
 * 
 * @package metainfo_lang_fields
 */

$addon = rex_addon::get('metainfo_lang_fields');

// boot.php kann innerhalb desselben Prozesses mehrfach eingebunden werden
// (z.B. bei kombinierten page+rex-api-call-Requests wie REDAXOs Session-Status-
// Ping) -- rex_view::addCssFile() wirft bei doppelter Registrierung derselben
// Datei eine Exception, daher hier einmalig absichern.
if (rex::isBackend() && !defined('METAINFO_LANG_FIELDS_BOOTED')) {
    define('METAINFO_LANG_FIELDS_BOOTED', true);
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
    
    // Assets und Render-Handler unconditional laden: die Seiten-Erkennung per
    // rex_request('page') deckte nur eine feste Liste bekannter Seiten ab und
    // griff nicht bei anderen Aufrufkontexten, die Metainfo-Formulare rendern
    // koennen (z.B. eigene rex-api-call-Endpunkte anderer Addons). Registrierung
    // selbst kostet nichts, solange das Extension Point nicht tatsaechlich
    // gefeuert wird -- die kleine CSS/JS-Datei bei jedem Backend-Request
    // mitzuladen ist der einfachere, robuste Kompromiss.
    rex_view::addCssFile($addon->getAssetsUrl('metainfo-lang-fields.css'));
    rex_view::addJsFile($addon->getAssetsUrl('metainfo-lang-fields.js'));
    rex_view::addJsFile($addon->getAssetsUrl('metainfo-lang-fields-all.js'));

    // UI-Strings fürs Repeater-JS (alert()-Meldungen) sprachabhängig bereitstellen,
    // analog zu core's eigenem rex.i18n-Muster (siehe core/fragments/core/top.php).
    rex_view::setJsProperty('metainfoLangFields', [
        'languageAlreadyAdded' => rex_i18n::msg('metainfo_lang_fields_language_already_added'),
        'pleaseEnterText' => rex_i18n::msg('metainfo_lang_fields_please_enter_text'),
        'pleaseSelectLanguage' => rex_i18n::msg('metainfo_lang_fields_please_select_language'),
    ]);
    rex_extension::register('METAINFO_CUSTOM_FIELD', 'metainfo_lang_fields_custom_field');

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
                    $rendered = metainfo_lang_fields_render_lang_json($match[1] ?? '', null);
                    return $rendered !== null ? '<p>' . $rendered . '</p>' : $match[0];
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
                                $rendered = metainfo_lang_fields_render_lang_json($match[0], '<em>Keine Beschreibung</em>');
                                return $rendered ?? $match[0];
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
 * Dekodiert einen (ggf. HTML-escaped) mehrsprachigen JSON-Wert und rendert
 * ihn als lesbaren "<strong>SPRACHKÜRZEL:</strong> Wert"-Text für die aktuelle
 * Sprache, mit Fallback auf die erste verfügbare Übersetzung.
 *
 * Gemeinsame Kernlogik der beiden OUTPUT_FILTER-Handler (Mediapool-Detail- und
 * -Listenansicht), die sich nur in Matching-Pattern und Leerwert-Verhalten
 * unterscheiden.
 *
 * @param string $jsonString Der (ggf. noch HTML-escaped) JSON-Ausschnitt
 * @param string|null $emptyFallback Rückgabewert, wenn kein Sprachwert gefunden wurde
 *                                   (null = Original beibehalten, Aufrufer entscheidet)
 * @return string|null Gerenderter Text (ohne umschließendes Tag), oder null falls kein
 *                      auswertbares JSON vorliegt
 */
function metainfo_lang_fields_render_lang_json(string $jsonString, ?string $emptyFallback): ?string
{
    if ('' === $jsonString) {
        return null;
    }

    // HTML-Entities dekodieren falls nötig
    if (strpos($jsonString, '&quot;') !== false) {
        $jsonString = html_entity_decode($jsonString);
    }

    // Sicherstellen dass es mit [ beginnt
    if (!str_starts_with($jsonString, '[')) {
        $jsonString = '[' . $jsonString . ']';
    }

    $langData = json_decode($jsonString, true);
    if (!is_array($langData)) {
        return null;
    }

    $currentLang = rex_clang::getCurrentId();
    $renderEntry = static function (array $entry): string {
        $clang = rex_clang::get((int) $entry['clang_id']);
        $langCode = $clang ? strtoupper($clang->getCode()) : 'L' . $entry['clang_id'];
        return '<strong>' . $langCode . ':</strong> ' . htmlspecialchars((string) $entry['value']);
    };

    // Suche aktuelle Sprache
    foreach ($langData as $entry) {
        if (isset($entry['clang_id'], $entry['value']) && (int) $entry['clang_id'] === $currentLang && '' !== trim((string) $entry['value'])) {
            return $renderEntry($entry);
        }
    }

    // Fallback: erste verfügbare Sprache
    foreach ($langData as $entry) {
        if (isset($entry['clang_id'], $entry['value']) && '' !== trim((string) $entry['value'])) {
            return $renderEntry($entry);
        }
    }

    return $emptyFallback;
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
        $attributes = (string) $subject['sql']->getValue('attributes');
        if (!empty($attributes)) {
            $fieldAttributes = $attributes;

            // Attribute wie metainfo-Core selbst parsen (rex_string::split), damit auch
            // wertlose Attribute (readonly, disabled, data-foo) korrekt erkannt werden -
            // eine eigene Regex hätte nur key="value"-Paare erfasst.
            $attrArray = rex_string::split($attributes);

            // rex_string::split liefert wertlose Attribute als int-indizierte Einträge;
            // in [name => ''] umwandeln, analog zu metainfo/lib/handler/handler.php.
            foreach ($attrArray as $key => $value) {
                if (is_int($key)) {
                    unset($attrArray[$key]);
                    $attrArray[$value] = '';
                }
            }

            if (isset($attrArray['class'])) {
                $fieldClass = $attrArray['class'];
                unset($attrArray['class']);
            }

            $additionalAttributes = $attrArray;
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

