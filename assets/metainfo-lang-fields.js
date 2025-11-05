$(document).on('rex:ready', function() {
    console.log('metainfo-lang-fields.js loaded');
    
    // Nur für Repeater-Modus, nicht für ALL-Modus
    if ($('.meta_lang_field_all').length > 0) {
        console.log('ALL mode detected, skipping repeater JS');
        return;
    }
    
    // Übersetzung hinzufügen
    $(document).on('click', '.add-translation', function() {
        var container = $(this).closest('.meta_lang_field');
        var select = container.find('select[name="new_lang_select"]');
        var selectedClangId = select.val();
        var selectedLangName = select.find('option:selected').text();
        
        console.log('=== ADD TRANSLATION DEBUG ===');
        console.log('Container found:', container.length);
        console.log('Select element found:', select.length);
        console.log('Select HTML:', select[0] ? select[0].outerHTML : 'not found');
        console.log('All options:', select.find('option').length);
        console.log('Selected option index:', select[0] ? select[0].selectedIndex : 'no select');
        console.log('Selected value raw:', selectedClangId);
        console.log('Selected value type:', typeof selectedClangId);
        console.log('Selected text:', selectedLangName);
        console.log('==============================');
        
        // Zusätzliche Prüfung: Gibt es überhaupt Optionen außer der ersten?
        var availableOptions = select.find('option[value!=""]');
        console.log('Available options (not empty):', availableOptions.length);
        
        if (availableOptions.length === 0) {
            alert('Alle Sprachen wurden bereits hinzugefügt.');
            return;
        }
        
        if (!selectedClangId || selectedClangId === '') {
            alert('Bitte wählen Sie eine Sprache aus der Liste aus.');
            return;
        }
        
        // Prüfen ob diese Sprache bereits existiert
        if (container.find('.meta_lang_translation_item[data-clang-id="' + selectedClangId + '"]').length > 0) {
            alert('Diese Sprache wurde bereits hinzugefügt.');
            return;
        }
        
        var newValueInput = container.find('.meta_lang_new_translation_input, .meta_lang_new_translation_textarea');
        var newValue = newValueInput.val();
        
        console.log('=== TEXT INPUT DEBUG ===');
        console.log('Input element found:', newValueInput.length);
        console.log('Input HTML:', newValueInput[0] ? newValueInput[0].outerHTML : 'not found');
        console.log('Raw value:', "'" + newValue + "'");
        console.log('Value length:', newValue ? newValue.length : 0);
        console.log('Trimmed value:', "'" + (newValue ? newValue.trim() : '') + "'");
        console.log('Trimmed length:', newValue ? newValue.trim().length : 0);
        console.log('========================');
        
        if (!newValue || !newValue.trim()) {
            alert('Bitte geben Sie einen Text ein.');
            return;
        }
        
        // Neue Übersetzung erstellen
        var fieldType = container.find('.meta_lang_new_translation_textarea').length > 0 ? 'textarea' : 'text';
        var inputHtml = '';
        
        if (fieldType === 'textarea') {
            inputHtml = '<textarea class="form-control meta_lang_textarea" rows="4" cols="50">' + 
                       escapeHtml(newValue) + '</textarea>';
        } else {
            inputHtml = '<input type="text" class="form-control meta_lang_input" value="' + 
                       escapeHtml(newValue) + '" placeholder="' + escapeHtml(selectedLangName) + ' Text..." />';
        }
        
        var newItem = $('<div class="meta_lang_translation_item" data-clang-id="' + selectedClangId + '" ' +
                       'style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">' +
                       '<div class="row">' +
                       '<div class="col-sm-3">' +
                       '<label class="control-label" style="margin-top: 7px;">' +
                       '<i class="fa fa-flag" style="margin-right: 5px;"></i>' +
                       escapeHtml(selectedLangName) +
                       '</label>' +
                       '</div>' +
                       '<div class="col-sm-8">' + inputHtml + '</div>' +
                       '<div class="col-sm-1">' +
                       '<button type="button" class="btn btn-danger btn-sm remove-translation" ' +
                       'title="Übersetzung entfernen" style="margin-top: 2px;">✗</button>' +
                       '</div>' +
                       '</div>' +
                       '</div>');
        
        container.find('.meta_lang_translations').append(newItem);
        
        // Ausgewählte Option aus Select entfernen
        select.find('option[value="' + selectedClangId + '"]').remove();
        
        // Eingabefeld zurücksetzen
        container.find('.meta_lang_new_translation_input, .meta_lang_new_translation_textarea').val('');
        
        // Select auf erste verfügbare Option setzen (nicht die leere Option)
        var firstAvailableOption = select.find('option[value!=""]:first');
        if (firstAvailableOption.length > 0) {
            select.val(firstAvailableOption.val());
        } else {
            select.val('');
        }
        
        // Wenn keine Sprachen mehr verfügbar, Add-Sektion ausblenden
        if (select.find('option').length === 0) {
            container.find('.meta_lang_add_translation_section').hide();
        }
        
        updateHiddenField(container);
    });
    
    // Übersetzung entfernen
    $(document).on('click', '.remove-translation', function() {
        var item = $(this).closest('.meta_lang_translation_item');
        var container = $(this).closest('.meta_lang_field');
        var clangId = item.data('clang-id');
        var langName = item.find('label').text().trim();
        
        // Option zurück zum Select hinzufügen
        var select = container.find('select[name="new_lang_select"]');
        select.append('<option value="' + clangId + '">' + langName + '</option>');
        
        // Add-Sektion wieder einblenden falls versteckt
        container.find('.meta_lang_add_translation_section').show();
        
        // Item entfernen
        item.remove();
        
        updateHiddenField(container);
    });
    
    // Input-Änderungen verfolgen
    $(document).on('input change', '.meta_lang_input, .meta_lang_textarea', function() {
        var container = $(this).closest('.meta_lang_field');
        updateHiddenField(container);
    });
    
    // Verstecktes Feld bei Seitenladung aktualisieren
    $('.meta_lang_field').each(function() {
        updateHiddenField($(this));
    });
    
    function updateHiddenField(container) {
        var data = [];
        
        container.find('.meta_lang_translation_item').each(function() {
            var item = $(this);
            var clangId = parseInt(item.data('clang-id'));
            var input = item.find('.meta_lang_input, .meta_lang_textarea');
            var value = input.val() || '';
            
            if (value.trim()) {
                data.push({
                    clang_id: clangId,
                    value: value.trim()
                });
            }
        });
        
        var jsonString = JSON.stringify(data);
        container.find('input[type="hidden"]').val(jsonString);
        console.log('Updated hidden field:', jsonString);
    }
    
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});