<?php

namespace KLXM\MetaInfoLangFields;

/**
 * Helper-Klasse für mehrsprachige Metainfo-Felder
 * 
 * @author Thomas Skerbis
 * @package metainfo_lang_fields
 */
class MetainfoLangHelper
{
    /**
     * Alle aktiven Sprachen abrufen
     */
    public static function getActiveLanguages(): array
    {
        return \rex_clang::getAll();
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
        $html = '<select name="' . \rex_escape($name) . '" class="form-control lang-select">';
        $html .= '<option value="">Sprache wählen...</option>';
        
        foreach ($languages as $lang) {
            $selected = $selectedId === $lang->getId() ? ' selected' : '';
            $html .= '<option value="' . $lang->getId() . '"' . $selected . '>';
            $html .= \rex_escape($lang->getName() . ' (' . $lang->getCode() . ')');
            $html .= '</option>';
        }
        
        $html .= '</select>';
        return $html;
    }

    /**
     * Mehrsprachigen Wert für ein Medium abrufen
     * 
     * @param \rex_media|string $media Medium-Objekt oder Dateiname
     * @param string $fieldName Name des Metainfo-Felds (z.B. 'med_title_lang')
     * @param int|null $clangId Sprach-ID (null = aktuelle Sprache)
     * @param bool $useFallback Bei true: Fallback auf Standardsprache wenn leer
     * @return string Übersetzter Wert oder leerer String
     */
    public static function getMediaValue($media, string $fieldName, int $clangId = null, bool $useFallback = true): string
    {
        // Medium-Objekt validieren
        if (is_string($media)) {
            $media = \rex_media::get($media);
        }
        
        if (!$media instanceof \rex_media) {
            return '';
        }

        // Sprach-ID bestimmen
        $clangId = $clangId ?: \rex_clang::getCurrentId();
        
        // Feldwert abrufen
        $fieldValue = $media->getValue($fieldName);
        if (empty($fieldValue)) {
            return '';
        }

        // Wert für gewünschte Sprache
        $value = self::getValueForLanguage($fieldValue, $clangId);
        
        // Fallback auf Standardsprache
        if (empty($value) && $useFallback && $clangId !== \rex_clang::getStartId()) {
            $value = self::getValueForLanguage($fieldValue, \rex_clang::getStartId());
        }

        return $value;
    }

    /**
     * Mehrsprachigen Wert für einen Artikel abrufen
     * 
     * @param \rex_article|int $article Artikel-Objekt oder Artikel-ID  
     * @param string $fieldName Name des Metainfo-Felds (z.B. 'art_title_lang')
     * @param int|null $clangId Sprach-ID (null = aktuelle Sprache)
     * @param bool $useFallback Bei true: Fallback auf Standardsprache wenn leer
     * @return string Übersetzter Wert oder leerer String
     */
    public static function getArticleValue($article, string $fieldName, int $clangId = null, bool $useFallback = true): string
    {
        // Artikel-Objekt validieren
        if (is_int($article)) {
            $article = \rex_article::get($article);
        }
        
        if (!$article instanceof \rex_article) {
            return '';
        }

        // Sprach-ID bestimmen
        $clangId = $clangId ?: \rex_clang::getCurrentId();
        
        // Feldwert abrufen
        $fieldValue = $article->getValue($fieldName);
        if (empty($fieldValue)) {
            return '';
        }

        // Wert für gewünschte Sprache
        $value = self::getValueForLanguage($fieldValue, $clangId);
        
        // Fallback auf Standardsprache
        if (empty($value) && $useFallback && $clangId !== \rex_clang::getStartId()) {
            $value = self::getValueForLanguage($fieldValue, \rex_clang::getStartId());
        }

        return $value;
    }

    /**
     * Mehrsprachigen Wert für eine Kategorie abrufen
     * 
     * @param \rex_category|int $category Kategorie-Objekt oder Kategorie-ID
     * @param string $fieldName Name des Metainfo-Felds (z.B. 'cat_title_lang') 
     * @param int|null $clangId Sprach-ID (null = aktuelle Sprache)
     * @param bool $useFallback Bei true: Fallback auf Standardsprache wenn leer
     * @return string Übersetzter Wert oder leerer String
     */
    public static function getCategoryValue($category, string $fieldName, int $clangId = null, bool $useFallback = true): string
    {
        // Kategorie-Objekt validieren
        if (is_int($category)) {
            $category = \rex_category::get($category);
        }
        
        if (!$category instanceof \rex_category) {
            return '';
        }

        // Sprach-ID bestimmen
        $clangId = $clangId ?: \rex_clang::getCurrentId();
        
        // Feldwert abrufen
        $fieldValue = $category->getValue($fieldName);
        if (empty($fieldValue)) {
            return '';
        }

        // Wert für gewünschte Sprache
        $value = self::getValueForLanguage($fieldValue, $clangId);
        
        // Fallback auf Standardsprache
        if (empty($value) && $useFallback && $clangId !== \rex_clang::getStartId()) {
            $value = self::getValueForLanguage($fieldValue, \rex_clang::getStartId());
        }

        return $value;
    }
}