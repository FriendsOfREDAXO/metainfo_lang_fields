// JavaScript für "Alle Sprachen" Modus - Bootstrap Collapse
//
// WICHTIG: Alle Event-Handler sind delegiert (document-Ebene mit
// Selektor-Filter), NICHT direkt an .meta_lang_field_all bzw. dessen
// Kind-Elemente gebunden. Grund: Addons wie mediaplace laden das native
// Metainfo-Formular (inkl. dieses Markups) per AJAX NACH dem initialen
// "rex:ready" nach (Klick auf "Metadaten bearbeiten" im Overlay) - eine
// einmalige Bindung zum rex:ready-Zeitpunkt haette solche nachtraeglich
// eingefuegten Container nie erreicht, das versteckte JSON-Feld (das
// tatsaechlich zum Server gesendet wird) waere dauerhaft leer geblieben,
// obwohl das sichtbare Textfeld normal funktionierte. Delegierte Handler
// greifen unabhaengig davon, wann/wie das Markup ins DOM kommt.
(function () {
    var cke5SyncIntervalStarted = false;

    function updateHiddenFieldAll(container) {
        var data = [];

        container.find('.meta_lang_field_input').each(function () {
            var input = $(this);
            var clangId = parseInt(input.data('clang-id'), 10);
            if (isNaN(clangId)) {
                return;
            }
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
    }

    function syncCKE5DataToTextareas(container) {
        container.find('.meta_lang_field_input.cke5-editor').each(function () {
            var $textarea = $(this);
            var textarea = this;

            var $ckeEditor = $textarea.siblings('.ck-editor').first();
            if ($ckeEditor.length === 0) {
                $ckeEditor = $textarea.next('.ck-editor');
            }
            if ($ckeEditor.length === 0) {
                return;
            }

            var $editableContent = $ckeEditor.find('.ck-editor__editable');
            if ($editableContent.length === 0) {
                return;
            }

            var htmlContent = $editableContent.html();

            if (htmlContent && htmlContent.indexOf('<p data-placeholder') === 0 && htmlContent.indexOf('</p>') === htmlContent.length - 4) {
                var tempDiv = $('<div>').html(htmlContent);
                var textContent = tempDiv.find('p').text();
                if (!textContent.trim()) {
                    htmlContent = '';
                }
            }

            if (htmlContent !== textarea.value) {
                textarea.value = htmlContent || '';
                $textarea.trigger('change');
            }
        });
    }

    function syncAllCKE5Data() {
        $('.meta_lang_field_all').each(function () {
            var container = $(this);
            syncCKE5DataToTextareas(container);
            updateHiddenFieldAll(container);
        });
    }

    // Alle sichtbaren .meta_lang_field_all-Container im Dokument syncen -
    // wird VOR jedem bekannten Speicher-Ausloeser aufgerufen (siehe unten),
    // damit das versteckte JSON-Feld auch dann aktuell ist, wenn (wie bei
    // mediaplace) gar kein natives <form>-submit-Event stattfindet, sondern
    // ein Button per Klick-Handler direkt FormData(formEl) aus dem DOM liest.
    function syncAllContainers() {
        $('.meta_lang_field_all').each(function () {
            updateHiddenFieldAll($(this));
        });
    }

    // Eingabe-Aenderungen: delegiert, greift auch fuer nachtraeglich per
    // AJAX eingefuegte Container.
    $(document).off('input.metainfoLangFieldsAll change.metainfoLangFieldsAll', '.meta_lang_field_all .meta_lang_field_input')
        .on('input.metainfoLangFieldsAll change.metainfoLangFieldsAll', '.meta_lang_field_all .meta_lang_field_input', function () {
            updateHiddenFieldAll($(this).closest('.meta_lang_field_all'));
        });

    // Natives <form>-submit (klassisches Metainfo-Formular ohne AJAX-Overlay).
    $(document).off('submit.metainfoLangFieldsAll').on('submit.metainfoLangFieldsAll', 'form', function () {
        if ($(this).find('.meta_lang_field_all').length > 0) {
            syncAllCKE5Data();
        }
    });

    // Zusaetzliches Sicherheitsnetz fuer AJAX-basierte Speicher-Buttons
    // (kein natives submit-Event, z.B. mediaplace-Metainfo-Canvas): jeder
    // Klick auf einen Button/Link, dessen Text/Klassen auf "Speichern"
    // hindeuten, synct vorher zur Sicherheit alle sichtbaren Container.
    // Rein additiv - loest KEIN eigenes Speichern aus, blockiert auch
    // nichts (kein preventDefault), sorgt nur dafuer dass das DOM zum
    // Zeitpunkt des eigentlichen Speicherns bereits aktuell ist.
    $(document).off('click.metainfoLangFieldsAllSafety').on('click.metainfoLangFieldsAllSafety', 'button, a', function () {
        if ($('.meta_lang_field_all').length > 0) {
            syncAllContainers();
        }
    });

    $(document).on('rex:ready', function () {
        var containers = $('.meta_lang_field_all');
        if (containers.length === 0) {
            return;
        }

        containers.each(function () {
            var container = $(this);
            var fieldName = container.data('field-name');
            setupCKE5Handlers(container, fieldName);
            updateHiddenFieldAll(container);
        });
    });

    function setupCKE5Handlers(container, fieldName) {
        if (!cke5SyncIntervalStarted && !window.cke5SyncInterval) {
            cke5SyncIntervalStarted = true;
            window.cke5SyncInterval = setInterval(function () {
                syncAllCKE5Data();
            }, 3000);
        }

        setTimeout(function () {
            container.find('.ck-editor__editable').off('blur.metainfoLangFieldsAll').on('blur.metainfoLangFieldsAll', function () {
                setTimeout(function () {
                    syncAllCKE5Data();
                }, 100);
            });
        }, 1000);
    }

    // Debug: Globaler Sync-Button für Tests (nur im Debug-Modus)
    if (window.location.search.indexOf('debug=1') !== -1) {
        $(document).on('rex:ready', function () {
            if ($('#debug-sync-cke5').length > 0) {
                return;
            }
            $('body').append('<button type="button" id="debug-sync-cke5" style="position:fixed;top:10px;right:10px;z-index:9999;background:red;color:white;padding:10px;">SYNC CKE5</button>');
            $('#debug-sync-cke5').on('click', function () {
                syncAllCKE5Data();
                alert('CKE5 data synced - check console for details');
            });
        });
    }
})();
