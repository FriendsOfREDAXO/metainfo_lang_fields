// JavaScript für "Alle Sprachen" Modus - Bootstrap Collapse
$(document).on('rex:ready', function() {
    console.log('ALL mode: Initializing with Bootstrap Collapse');
    
    var containers = $('.metainfo-lang-field-all');
    console.log('ALL mode: Found containers:', containers.length);
    
    if (containers.length === 0) {
        return;
    }
    
    // CKE5 Editor Instanzen verwalten
    var cke5Editors = {};
    
    // Für jeden Container Input Handler registrieren
    containers.each(function() {
        var container = $(this);
        var fieldName = container.data('field-name');
        
        // Standard Input Handler für alle Eingabefelder (für normale Textfelder)
        container.find('.lang-field-input').on('input change', function() {
            updateHiddenFieldAll(container);
        });
        
        // CKE5 Editor Handler
        setupCKE5Handlers(container, fieldName);
        
        // Initial update
        updateHiddenFieldAll(container);
    });
    
    function setupCKE5Handlers(container, fieldName) {
        console.log('Setting up CKE5 handlers for container:', fieldName);
        
        // Einfacher aber effektiver Ansatz: Form Submit abfangen
        var form = container.closest('form');
        if (form.length > 0 && !form.data('cke5-handler-attached')) {
            form.data('cke5-handler-attached', true);
            
            form.on('submit', function() {
                console.log('Form submit detected - syncing CKE5 data');
                syncAllCKE5Data();
            });
        }
        
        // Regelmäßige Synchronisation alle 3 Sekunden
        if (!window.cke5SyncInterval) {
            window.cke5SyncInterval = setInterval(function() {
                syncAllCKE5Data();
            }, 3000);
        }
        
        // Blur-Events auf CKE5 Editoren abfangen
        setTimeout(function() {
            container.find('.ck-editor__editable').on('blur', function() {
                console.log('CKE5 editor blur detected');
                setTimeout(function() {
                    syncAllCKE5Data();
                }, 100);
            });
        }, 1000);
    }
    
    function syncAllCKE5Data() {
        $('.metainfo-lang-field-all').each(function() {
            var container = $(this);
            syncCKE5DataToTextareas(container);
            updateHiddenFieldAll(container);
        });
    }
    
    function syncCKE5DataToTextareas(container) {
        container.find('.lang-field-input.cke5-editor').each(function() {
            var $textarea = $(this);
            var textarea = this;
            
            // Finde das entsprechende CKE5 Element (nächstes .ck-editor Element)
            var $ckeEditor = $textarea.siblings('.ck-editor').first();
            
            if ($ckeEditor.length === 0) {
                // Alternative: nächstes Element nach dem Textarea
                $ckeEditor = $textarea.next('.ck-editor');
            }
            
            if ($ckeEditor.length > 0) {
                var $editableContent = $ckeEditor.find('.ck-editor__editable');
                
                if ($editableContent.length > 0) {
                    // Hole den HTML Inhalt
                    var htmlContent = $editableContent.html();
                    
                    // Bereinige den Inhalt (entferne data-placeholder wenn leer)
                    if (htmlContent && htmlContent.indexOf('<p data-placeholder') === 0 && htmlContent.indexOf('</p>') === htmlContent.length - 4) {
                        var tempDiv = $('<div>').html(htmlContent);
                        var textContent = tempDiv.find('p').text();
                        if (!textContent.trim()) {
                            htmlContent = '';
                        }
                    }
                    
                    // Nur aktualisieren wenn sich der Inhalt geändert hat
                    if (htmlContent !== textarea.value) {
                        console.log('Syncing CKE5 content for clang:', $textarea.data('clang-id'), htmlContent.substring(0, 50));
                        textarea.value = htmlContent || '';
                        $textarea.trigger('change');
                    }
                }
            }
        });
    }
    
    function updateHiddenFieldAll(container) {
        var data = [];
        var fieldName = container.data('field-name');
        
        console.log('=== UPDATE HIDDEN FIELD DEBUG ===');
        console.log('Container field name:', fieldName);
        
        container.find('.lang-field-input').each(function() {
            var input = $(this);
            var clangId = parseInt(input.data('clang-id'));
            var value = input.val() || '';
            var isCKE5 = input.hasClass('cke5-editor');
            
            console.log('Field - clang:', clangId, 'isCKE5:', isCKE5, 'value length:', value.length, 'preview:', value.substring(0, 50));
            
            // Für CKE5: Wert kann auch im textarea selbst stehen nach der Synchronisierung
            if (value.trim()) {
                data.push({
                    clang_id: clangId,
                    value: value.trim()
                });
            }
        });
        
        var jsonString = JSON.stringify(data);
        container.find('input[type="hidden"]').val(jsonString);
        
        console.log('Final JSON:', jsonString);
        console.log('Hidden field updated for:', fieldName);
        console.log('=== END DEBUG ===');
    }
    
    // Form Submit Handler - stelle sicher dass CKE5 Daten gespeichert werden
    $('form').on('submit', function() {
        console.log('FORM SUBMIT - Final sync of all CKE5 data');
        syncAllCKE5Data();
    });
    
    // Debug: Globaler Sync-Button für Tests (nur im Debug-Modus)
    if (window.location.search.indexOf('debug=1') !== -1) {
        $('body').append('<button type="button" id="debug-sync-cke5" style="position:fixed;top:10px;right:10px;z-index:9999;background:red;color:white;padding:10px;">SYNC CKE5</button>');
        $('#debug-sync-cke5').on('click', function() {
            console.log('Manual CKE5 sync triggered');
            syncAllCKE5Data();
            alert('CKE5 data synced - check console for details');
        });
    }
});