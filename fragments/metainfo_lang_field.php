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
$languageData = \FriendsOfRedaxo\MetaInfoLangFields\MetainfoLangHelper::normalizeLanguageData($fieldValue);
$availableLanguages = \FriendsOfRedaxo\MetaInfoLangFields\MetainfoLangHelper::getAvailableLanguages($languageData);
$allLanguages = \FriendsOfRedaxo\MetaInfoLangFields\MetainfoLangHelper::getActiveLanguages();
?>

<div class="meta_lang_field" data-field-name="<?= rex_escape($fieldName) ?>">
    
    
    <?php if (!empty($fieldLabel)): ?>
    <label class="control-label meta_lang_main_label">
        <?php
        // Label-Text aus HTML extrahieren
        $cleanLabel = $fieldLabel;
        if (is_string($cleanLabel)) {
            // Zuerst versuchen den Text zwischen den Label-Tags zu extrahieren
            if (preg_match('/<label[^>]*>([^<]*)<\/label>/', $cleanLabel, $matches)) {
                $cleanLabel = $matches[1];
            } else {
                // Fallback: Alle HTML-Tags entfernen
                $cleanLabel = strip_tags(html_entity_decode($cleanLabel, ENT_QUOTES, 'UTF-8'));
            }
        }
        echo rex_escape($cleanLabel);
        ?>
    </label>
    <?php endif; ?>
    
    <!-- Verstecktes Feld für JSON-Daten -->
    <input type="hidden" name="<?= rex_escape($fieldName) ?>" value="" />
    
    <!-- Existierende Übersetzungen -->
    <div class="meta_lang_translations">
        <?php foreach ($languageData as $index => $translation): ?>
            <?php 
            $clangId = $translation['clang_id'];
            $langValue = $translation['value'];
            $language = $allLanguages[$clangId] ?? null;
            ?>
            <?php if ($language): ?>
            <div class="meta_lang_translation_item" data-clang-id="<?= $clangId ?>" data-lang-name="<?= rex_escape($language->getName() . ' (' . $language->getCode() . ')') ?>">
                <div class="row">
                    <div class="col-sm-3">
                        <label class="control-label meta_lang_control_label">
                            <i class="fa fa-flag meta_lang_flag_icon"></i>
                            <?= rex_escape($language->getName() . ' (' . $language->getCode() . ')') ?>
                        </label>
                    </div>
                    <div class="col-sm-8">
                        <?php if ($fieldType === 'textarea'): ?>
                            <textarea class="<?= rex_escape($fieldClass) ?> meta_lang_textarea" 
                                      rows="4" cols="50"<?= $additionalAttrsString ?>><?= rex_escape($langValue) ?></textarea>
                        <?php else: ?>
                            <input type="text" 
                                   class="<?= rex_escape($fieldClass) ?> meta_lang_input" 
                                   value="<?= rex_escape($langValue) ?>" 
                                   placeholder="<?= rex_escape($language->getName()) ?> Text..."<?= $additionalAttrsString ?> />
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-1">
                        <button type="button" 
                                class="btn btn-danger btn-sm remove-translation meta_lang_button" 
                                title="Übersetzung entfernen">
                            ✗
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <!-- Neue Übersetzung hinzufügen -->
    <div class="meta_lang_add_translation_section"<?php if (empty($availableLanguages)): ?> style="display: none;"<?php endif; ?>>
        <div class="row">
                        <div class="col-sm-3">
                <label class="control-label meta_lang_control_label">Neue Sprache:</label>
            </div>
            <div class="col-sm-3">
                <select name="new_lang_select" class="form-control meta_lang_select">
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
                    <textarea class="<?= rex_escape($fieldClass) ?> meta_lang_new_translation_textarea" 
                              rows="4" cols="50" 
                              placeholder="Neue Übersetzung..."<?= $additionalAttrsString ?>></textarea>
                <?php else: ?>
                    <input type="text" 
                           class="<?= rex_escape($fieldClass) ?> meta_lang_new_translation_input" 
                           placeholder="Neue Übersetzung..."<?= $additionalAttrsString ?> />
                <?php endif; ?>
            </div>
            <div class="col-sm-1">
                <button type="button" 
                        class="btn btn-success btn-sm add-translation meta_lang_button" 
                        title="Übersetzung hinzufügen">
                    +
                </button>
            </div>
        </div>
    </div>
</div>