<?php
/**
 * Fragment für mehrsprachige Metainfo-Felder - Alle Sprachen Modus
 * Alle Sprachfelder sind vorhanden, aber nur die erste ist sichtbar
 */

$fieldName = $this->fieldName ?? '';
$fieldValue = $this->fieldValue ?? '';
$fieldId = $this->fieldId ?? '';
$fieldLabel = $this->fieldLabel ?? '';
$fieldType = $this->fieldType ?? 'text';
$fieldAttributes = $this->fieldAttributes ?? '';
$fieldClass = $this->fieldClass ?? 'form-control';
$additionalAttributes = $this->additionalAttributes ?? [];

// Helper-Funktion um zusätzliche Attribute als String zu generieren
$generateAdditionalAttrs = function($additionalAttributes) {
    $attrString = '';
    foreach ($additionalAttributes as $name => $value) {
        $attrString .= ' ' . rex_escape($name) . '="' . rex_escape($value) . '"';
    }
    return $attrString;
};
$additionalAttrsString = $generateAdditionalAttrs($additionalAttributes);

// Sprachdaten parsen
$languageData = \KLXM\MetaInfoLangFields\MetainfoLangHelper::normalizeLanguageData($fieldValue);
$allLanguages = \KLXM\MetaInfoLangFields\MetainfoLangHelper::getActiveLanguages();

// Bestehende Übersetzungen in Array umwandeln für einfachen Zugriff
$translations = [];
if (is_array($languageData)) {
    foreach ($languageData as $item) {
        if (is_array($item) && isset($item['clang_id']) && isset($item['value'])) {
            $translations[$item['clang_id']] = $item['value'];
        }
    }
}
?>


<div class="metainfo-lang-field-all" data-field-name="<?= rex_escape($fieldName) ?>" style="background: rgba(255, 255, 255, 0.6); padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(0, 0, 0, 0.1);">
    <!-- Feld-Label -->
    <?php if (!empty($fieldLabel)): ?>
    <div style="display: block; margin-bottom: 10px; font-weight: bold;">
        <?php
        // Label extrahieren - zwischen > und <
        if (preg_match('/>([^<]+)</', $fieldLabel, $matches)) {
            echo rex_escape($matches[1]);
        } else {
            // Fallback: Versuche verschiedene Methoden
            $decoded = html_entity_decode($fieldLabel, ENT_QUOTES, 'UTF-8');
            if (preg_match('/>([^<]+)</', $decoded, $matches2)) {
                echo rex_escape($matches2[1]);
            } else {
                // Als letztes Resort: strip_tags verwenden
                $stripped = strip_tags($decoded);
                echo rex_escape($stripped ?: 'Unbekanntes Feld');
            }
        }
        ?>
    </div>
    <?php else: ?>
    <!-- DEBUG: Label ist leer! -->
    <?php endif; ?>
    
    <!-- Verstecktes Feld für JSON-Daten -->
    <input type="hidden" name="<?= rex_escape($fieldName) ?>" value="" />
    
    <!-- Erste Sprache (immer sichtbar) -->
    <?php $firstLang = reset($allLanguages); ?>
    <?php $firstClangId = $firstLang->getId(); ?>
    <?php $firstValue = $translations[$firstClangId] ?? ''; ?>
    
    <div class="lang-field-row" style="margin-bottom: 15px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa;">
        <div class="row">
            <div class="col-sm-3">
                <label class="control-label" style="margin-top: 7px;">
                    <i class="fa fa-flag" style="margin-right: 5px; color: #28a745;"></i>
                    <?= rex_escape($firstLang->getName() . ' (' . $firstLang->getCode() . ')') ?>
                    <span class="text-muted">(Hauptsprache)</span>
                </label>
            </div>
            <div class="col-sm-9">
                <div class="input-group">
                    <?php if ($fieldType === 'textarea'): ?>
                        <textarea class="<?= rex_escape($fieldClass) ?> lang-field-input" 
                                  data-clang-id="<?= $firstClangId ?>"
                                  rows="6" 
                                  placeholder="<?= rex_escape($firstLang->getName()) ?> Text..."<?= $additionalAttrsString ?>><?= rex_escape($firstValue) ?></textarea>
                    <?php else: ?>
                        <input type="text" 
                               class="<?= rex_escape($fieldClass) ?> lang-field-input" 
                               data-clang-id="<?= $firstClangId ?>"
                               value="<?= rex_escape($firstValue) ?>" 
                               placeholder="<?= rex_escape($firstLang->getName()) ?> Text..."<?= $additionalAttrsString ?> />
                    <?php endif; ?>
                    
                    <?php if (count($allLanguages) > 1): ?>
                    <div class="input-group-btn">
                        <button type="button" 
                                class="btn btn-default" 
                                data-toggle="collapse" 
                                data-target="#additional-languages-<?= rex_escape($fieldName) ?>" 
                                aria-expanded="false"
                                title="Weitere Sprachen (<?= count($allLanguages) - 1 ?>)">
                            <i class="fas fa-globe"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Weitere Sprachen (Bootstrap Collapse) -->
    <div class="collapse" id="additional-languages-<?= rex_escape($fieldName) ?>">
        <?php foreach (array_slice($allLanguages, 1) as $language): ?>
            <?php 
            $clangId = $language->getId();
            $langValue = $translations[$clangId] ?? '';
            ?>
            <div class="lang-field-row" style="margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #ffffff;">
                <div class="row">
                    <div class="col-sm-3">
                        <label class="control-label" style="margin-top: 7px;">
                            <i class="fa fa-flag" style="margin-right: 5px; color: #6c757d;"></i>
                            <?= rex_escape($language->getName() . ' (' . $language->getCode() . ')') ?>
                        </label>
                    </div>
                    <div class="col-sm-9">
                        <?php if ($fieldType === 'textarea'): ?>
                            <textarea class="<?= rex_escape($fieldClass) ?> lang-field-input" 
                                      data-clang-id="<?= $clangId ?>"
                                      rows="4" 
                                      placeholder="<?= rex_escape($language->getName()) ?> Text..."<?= $additionalAttrsString ?>><?= rex_escape($langValue) ?></textarea>
                        <?php else: ?>
                            <input type="text" 
                                   class="<?= rex_escape($fieldClass) ?> lang-field-input" 
                                   data-clang-id="<?= $clangId ?>"
                                   value="<?= rex_escape($langValue) ?>" 
                                   placeholder="<?= rex_escape($language->getName()) ?> Text..."<?= $additionalAttrsString ?> />
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>