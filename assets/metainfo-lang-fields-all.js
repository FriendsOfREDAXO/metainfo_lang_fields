// JavaScript für "Alle Sprachen" Modus - Bootstrap Collapse
$(document).on('rex:ready', function() {
    console.log('ALL mode: Initializing with Bootstrap Collapse');
    
    var containers = $('.metainfo-lang-field-all');
    console.log('ALL mode: Found containers:', containers.length);
    
    if (containers.length === 0) {
        return;
    }
    
    // Für jeden Container Input Handler registrieren
    containers.each(function() {
        var container = $(this);
        
        // Input Handler für alle Eingabefelder
        container.find('.lang-field-input').on('input change', function() {
            updateHiddenFieldAll(container);
        });
        
        // Initial update
        updateHiddenFieldAll(container);
    });
    
    function updateHiddenFieldAll(container) {
        var data = [];
        
        container.find('.lang-field-input').each(function() {
            var input = $(this);
            var clangId = parseInt(input.data('clang-id'));
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
        console.log('Updated hidden field (all mode):', jsonString);
    }
});