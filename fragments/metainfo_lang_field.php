<?php
/**
 * Fragment für mehrsprachige Metainfo-Felder - Repeater Style
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
$availableLanguages = \KLXM\MetaInfoLangFields\MetainfoLangHelper::getAvailableLanguages($languageData);
$allLanguages = \KLXM\MetaInfoLangFields\MetainfoLangHelper::getActiveLanguages();
?>

<div class="metainfo-lang-field" data-field-name="<?= rex_escape($fieldName) ?>">
    <!-- Feld-Label -->
    <?php if (!empty($fieldLabel)): ?>
    <div style="display: block; margin-bottom: 10px; font-weight: bold;">
        <?= $fieldLabel ?>
    </div>
    <?php endif; ?>
    
    <!-- Verstecktes Feld für JSON-Daten -->
    <input type="hidden" name="<?= rex_escape($fieldName) ?>" value="" />
    
    <!-- Existierende Übersetzungen -->
    <div class="lang-translations">
        <?php foreach ($languageData as $index => $translation): ?>
            <?php 
            $clangId = $translation['clang_id'];
            $langValue = $translation['value'];
            $language = $allLanguages[$clangId] ?? null;
            ?>
            <?php if ($language): ?>
            <div class="lang-translation-item" data-clang-id="<?= $clangId ?>" style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <div class="row">
                    <div class="col-sm-3">
                        <label class="control-label" style="margin-top: 7px;">
                            <i class="fa fa-flag" style="margin-right: 5px;"></i>
                            <?= rex_escape($language->getName() . ' (' . $language->getCode() . ')') ?>
                        </label>
                    </div>
                    <div class="col-sm-8">
                        <?php if ($fieldType === 'textarea'): ?>
                            <textarea class="<?= rex_escape($fieldClass) ?> lang-textarea" 
                                      rows="4" cols="50"<?= $additionalAttrsString ?>><?= rex_escape($langValue) ?></textarea>
                        <?php else: ?>
                            <input type="text" 
                                   class="<?= rex_escape($fieldClass) ?> lang-input" 
                                   value="<?= rex_escape($langValue) ?>" 
                                   placeholder="<?= rex_escape($language->getName()) ?> Text..."<?= $additionalAttrsString ?> />
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-1">
                        <button type="button" 
                                class="btn btn-danger btn-sm remove-translation" 
                                title="Übersetzung entfernen"
                                style="margin-top: 2px;">
                            ✗
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <!-- Neue Übersetzung hinzufügen -->
    <?php if (!empty($availableLanguages)): ?>
    <div class="add-translation-section" style="margin-top: 15px; padding: 10px; border: 2px dashed #ccc; border-radius: 4px; background: #f9f9f9;">
        <div class="row">
            <div class="col-sm-3">
                <label class="control-label" style="margin-top: 7px;">Neue Sprache:</label>
            </div>
            <div class="col-sm-3">
                <select name="new_lang_select" class="form-control lang-select">
                    <option value="">Sprache wählen...</option>
                    <?php foreach ($availableLanguages as $lang): ?>
                        <option value="<?= $lang->getId() ?>">
                            <?= rex_escape($lang->getName() . ' (' . $lang->getCode() . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-5">
                <?php if ($fieldType === 'textarea'): ?>
                    <textarea class="<?= rex_escape($fieldClass) ?> new-translation-textarea" 
                              rows="4" cols="50" 
                              placeholder="Neue Übersetzung..."<?= $additionalAttrsString ?>></textarea>
                <?php else: ?>
                    <input type="text" 
                           class="<?= rex_escape($fieldClass) ?> new-translation-input" 
                           placeholder="Neue Übersetzung..."<?= $additionalAttrsString ?> />
                <?php endif; ?>
            </div>
            <div class="col-sm-1">
                <button type="button" 
                        class="btn btn-success btn-sm add-translation" 
                        title="Übersetzung hinzufügen"
                        style="margin-top: 2px;">
                    +
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>