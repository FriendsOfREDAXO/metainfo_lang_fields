<?php

/**
 * Metainfo Lang Fields Add-on
 * 
 * @package metainfo_lang_fields
 */

$addon = rex_addon::get('metainfo_lang_fields');

if (rex::isBackend()) {
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
    

    

    

    
    // Fragment für die Ausgabe verwenden
    $fragment = new rex_fragment();
    $fragment->setVar('fieldName', $fieldName);
    $fragment->setVar('fieldValue', $fieldValue);
    $fragment->setVar('fieldId', $fieldId);
    $fragment->setVar('fieldLabel', $fieldLabel);
    
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
    
    $html = $fragment->parse($fragmentFile);    $subject[0] = $html;
    return $subject;
}

