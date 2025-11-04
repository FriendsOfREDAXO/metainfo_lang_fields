<?php

/**
 * Helper-Klasse für mehrsprachige Metainfo-Felder
 * 
 * @package metainfo_lang_fields
 */
class MetainfoLangHelper
{
    /**
     * Alle aktiven Sprachen abrufen
     */
    public static function getActiveLanguages(): array
    {
        return rex_clang::getAll();
    }

    /**
     * JSON-Daten für Sprachfeld validieren und normalisieren
     */
    public static function normalizeLanguageData($data): array
    {
        if (is_string($data) && !empty($data)) {
            // HTML-Entities dekodieren falls nötig
            $cleanData = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
            
            $decodedData = json_decode($cleanData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decodedData;
            } else {
                return [];
            }
        }

        if (!is_array($data)) {
            return [];
        }

        $normalized = [];
        $languages = self::getActiveLanguages();

        foreach ($data as $item) {
            if (!is_array($item) || !isset($item['clang_id']) || !isset($languages[$item['clang_id']])) {
                continue;
            }

            $clangId = (int) $item['clang_id'];
            
            if (!isset($normalized[$clangId])) {
                $normalized[$clangId] = [
                    'clang_id' => $clangId,
                    'value' => $item['value'] ?? ''
                ];
            }
        }

        return array_values($normalized);
    }

    /**
     * Wert für bestimmte Sprache aus JSON-Daten extrahieren
     */
    public static function getValueForLanguage($data, int $clangId): string
    {
        $normalized = self::normalizeLanguageData($data);
        
        foreach ($normalized as $item) {
            if ($item['clang_id'] === $clangId) {
                return (string) $item['value'];
            }
        }

        return '';
    }

    /**
     * Überprüfen ob für Sprache eine Übersetzung existiert
     */
    public static function hasTranslationForLanguage($data, int $clangId): bool
    {
        $value = self::getValueForLanguage($data, $clangId);
        return !empty(trim($value));
    }

    /**
     * Verfügbare Sprachen für neue Übersetzungen
     */
    public static function getAvailableLanguages($existingData): array
    {
        $normalized = self::normalizeLanguageData($existingData);
        $usedLanguages = !empty($normalized) ? array_column($normalized, 'clang_id') : [];
        $allLanguages = self::getActiveLanguages();
        
        $available = [];
        foreach ($allLanguages as $lang) {
            if (!in_array($lang->getId(), $usedLanguages, true)) {
                $available[] = $lang;
            }
        }
        
        return $available;
    }

    /**
     * HTML für Sprach-Select generieren
     */
    public static function getLanguageSelectHtml(string $name, int $selectedId = null): string
    {
        $languages = self::getActiveLanguages();
        $html = '<select name="' . rex_escape($name) . '" class="form-control lang-select">';
        $html .= '<option value="">Sprache wählen...</option>';
        
        foreach ($languages as $lang) {
            $selected = $selectedId === $lang->getId() ? ' selected' : '';
            $html .= '<option value="' . $lang->getId() . '"' . $selected . '>';
            $html .= rex_escape($lang->getName() . ' (' . $lang->getCode() . ')');
            $html .= '</option>';
        }
        
        $html .= '</select>';
        return $html;
    }
}