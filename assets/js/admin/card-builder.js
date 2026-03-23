/**
 * MonkeyPay – Card Builder Module (v2)
 *
 * Enhanced drag-and-drop card template builder for Lark notifications:
 *   - Palette click / drag to add elements
 *   - Canvas reorder via drag-and-drop with drop indicator
 *   - Live preview with variable substitution
 *   - Load / serialize / apply / reset template
 *
 * Dependencies: jQuery, MonkeyPay global (MP)
 * @since 3.0.0
 */
(function ($) {
    'use strict';

    if (typeof window.MonkeyPay === 'undefined') return;

    const MP         = window.MonkeyPay;
    const showToast  = MP.showToast  || function () {};
    const openModal  = MP.openModal  || function () {};
    const closeModal = MP.closeModal || function () {};

    /* ───── DOM references ──────────────────────────────── */
    const $builderModal = $('#mp-card-builder-modal');
    if (!$builderModal.length) return;

    const $canvas      = $('#mp-cb-canvas');
    const $emptyHint   = $('#mp-cb-empty-hint');
    const $previewCard = $('#mp-cb-preview-card');
    const $headerTitle = $('#mp-cb-header-title');
    const $palette     = $('#mp-cb-palette');
    const defaultTpl      = window.mpDefaultTemplate || {};
    const defaultDebitTpl = window.mpDefaultDebitTemplate || {
        header_title: '💸 Chuyển tiền',
        header_color: 'red',
        elements: [
            { type: 'text', content: '**Số tiền: -{amount} VNĐ**' },
            { type: 'hr' },
            { type: 'fields', fields: [
                { label: 'Ngân hàng', value: '{bank_name}' },
                { label: 'Số TK', value: '{account_no}' },
                { label: 'TX', value: '{tx_id}' },
                { label: 'BDSD', value: '{bdsd_id}' },
                { label: 'Thời gian', value: '{matched_at}' },
            ]},
            { type: 'note', content: 'Nội dung CK: {payment_note}' },
        ]
    };

    // Current tab mode: 'credit' or 'debit'
    let currentMode = 'credit';

    // Sample variables for preview
    const sampleVars = {
        '{amount}': '2,000 ₫', '{amount_raw}': '2000', '{invoice_id}': 'HD260319001',
        '{note}': 'MPCEZZIP', '{bank_name}': 'MBBank', '{trans_id}': 'tx_abc123',
        '{time}': '19/03/2026 18:30', '{status}': 'Thành công',
        '{payment_note}': 'MPCEZZIP', '{account_no}': '0123456789',
        '{bank_description}': 'MPCEZZIP Chuyen tien', '{site_name}': 'MonkeySalon',
        '{matched_at}': '19/03/2026 18:30',
        '{tx_id}': 'tx_abc123', '{bdsd_id}': 'BDSD-114'
    };

    let canvasItems = []; // [{id, type, ...config}]
    let idCounter   = 0;

    // Drag state
    let dragSrcId       = null;
    let dragFromPalette = null;
    let $dropIndicator  = $('<div class="mp-cb-drop-indicator"></div>');

    /* ───── HTML escape helper ──────────────────────── */
    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /* ═══════════════════════════════════════════════════
     *  Palette interactions
     * ═══════════════════════════════════════════════════ */

    // Click → add element
    $palette.on('click', '.mp-cb-palette-item', function () {
        if ($(this).data('wasDragged')) {
            $(this).data('wasDragged', false);
            return;
        }
        addElement($(this).data('type'));
    });

    // Drag from palette
    $palette.on('mousedown', '.mp-cb-palette-item', function () {
        this.setAttribute('draggable', 'true');
    });
    $palette.on('dragstart', '.mp-cb-palette-item', function (e) {
        dragFromPalette = $(this).data('type');
        dragSrcId = null;
        $(this).addClass('is-dragging').data('wasDragged', true);
        e.originalEvent.dataTransfer.effectAllowed = 'copy';
        e.originalEvent.dataTransfer.setData('text/plain', 'palette:' + dragFromPalette);
    });
    $palette.on('dragend', '.mp-cb-palette-item', function () {
        $(this).removeClass('is-dragging');
        dragFromPalette = null;
        cleanupDragState();
    });

    /* ═══════════════════════════════════════════════════
     *  Element management
     * ═══════════════════════════════════════════════════ */

    function addElement(type, config, insertAtIndex) {
        var id = 'cbi_' + (++idCounter);
        var item = { id: id, type: type };

        switch (type) {
            case 'text':
                item.content = (config && config.content) || '**Số tiền:** {amount}';
                break;
            case 'fields':
                item.fields = (config && config.fields) || [
                    { label: 'Ngân hàng', value: '{bank_name}' },
                    { label: 'Mã GD', value: '{trans_id}' }
                ];
                break;
            case 'hr':
                break;
            case 'note':
                item.content = (config && config.content) || 'TX: {trans_id}';
                break;
            case 'url_button':
                item.text = (config && config.text) || 'Xem chi tiết';
                item.url  = (config && config.url)  || '';
                break;
        }

        if (typeof insertAtIndex === 'number' && insertAtIndex >= 0) {
            canvasItems.splice(insertAtIndex, 0, item);
        } else {
            canvasItems.push(item);
        }
        renderCanvas();
        renderPreview();
    }

    /* ═══════════════════════════════════════════════════
     *  Canvas rendering
     * ═══════════════════════════════════════════════════ */

    function renderCanvas() {
        $canvas.find('.mp-cb-item, .mp-cb-drop-indicator').remove();
        $emptyHint.toggle(canvasItems.length === 0);

        canvasItems.forEach(function (item) {
            $canvas.append(buildCanvasItem(item));
        });
    }

    function buildCanvasItem(item) {
        var $el = $('<div class="mp-cb-item" draggable="true" data-id="' + item.id + '"></div>');
        if (item.type === 'hr') $el.addClass('mp-cb-item--hr');

        // Drag handle
        $el.append('<span class="mp-cb-item__drag"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="18" x2="16" y2="18"/></svg></span>');

        // Type badge
        $el.append('<span class="mp-cb-item__type">' + item.type.replace('_', ' ') + '</span>');

        // Config area
        var $config = $('<div class="mp-cb-item__config"></div>');
        switch (item.type) {
            case 'text':
                $config.append('<input type="text" data-field="content" value="' + escHtml(item.content || '') + '" placeholder="Nội dung text...">');
                break;
            case 'fields':
                var $pair = $('<div class="mp-cb-fields-pair"></div>');
                (item.fields || []).forEach(function (f, i) {
                    $pair.append('<input type="text" data-field="fields.' + i + '.label" value="' + escHtml(f.label || '') + '" placeholder="Label">');
                    $pair.append('<input type="text" data-field="fields.' + i + '.value" value="' + escHtml(f.value || '') + '" placeholder="Value">');
                });
                $config.append($pair);
                break;
            case 'hr':
                $config.html('─── Divider ───');
                break;
            case 'note':
                $config.append('<input type="text" data-field="content" value="' + escHtml(item.content || '') + '" placeholder="Ghi chú...">');
                break;
            case 'url_button':
                $config.append('<input type="text" data-field="text" value="' + escHtml(item.text || '') + '" placeholder="Button text..." style="margin-bottom:4px;">');
                $config.append('<input type="text" data-field="url" value="' + escHtml(item.url || '') + '" placeholder="https://...">');
                break;
        }
        $el.append($config);

        // Remove button
        $el.append('<button type="button" class="mp-cb-item__remove" title="Xóa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>');

        return $el;
    }

    /* ───── Input change → update model + preview ──── */
    $canvas.on('input', '.mp-cb-item__config input, .mp-cb-item__config textarea', function () {
        var $item = $(this).closest('.mp-cb-item');
        var id    = $item.data('id');
        var field = $(this).data('field');
        var val   = $(this).val();
        var item  = canvasItems.find(function (i) { return i.id === id; });
        if (!item) return;

        if (field === 'content' || field === 'text' || field === 'url') {
            item[field] = val;
        } else if (field && field.indexOf('fields.') === 0) {
            var parts = field.split('.');
            var idx   = parseInt(parts[1], 10);
            var key   = parts[2];
            if (item.fields && item.fields[idx]) {
                item.fields[idx][key] = val;
            }
        }
        renderPreview();
    });

    /* ───── Remove item ────────────────────────────── */
    $canvas.on('click', '.mp-cb-item__remove', function () {
        var id = $(this).closest('.mp-cb-item').data('id');
        canvasItems = canvasItems.filter(function (i) { return i.id !== id; });
        renderCanvas();
        renderPreview();
    });

    /* ═══════════════════════════════════════════════════
     *  Enhanced Drag-and-Drop with Drop Indicator
     * ═══════════════════════════════════════════════════ */

    function cleanupDragState() {
        $dropIndicator.detach();
        $canvas.find('.mp-cb-item').removeClass('is-dragging is-drag-above is-drag-below');
        $canvas.removeClass('is-drag-over');
    }

    // Canvas items drag
    $canvas.on('dragstart', '.mp-cb-item', function (e) {
        if (dragFromPalette) return;
        dragSrcId = $(this).data('id');
        $(this).addClass('is-dragging');
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        e.originalEvent.dataTransfer.setData('text/plain', 'canvas:' + dragSrcId);
    });
    $canvas.on('dragend', '.mp-cb-item', function () {
        $(this).removeClass('is-dragging');
        dragSrcId = null;
        cleanupDragState();
    });

    // Drop indicator positioning
    function getDropPosition(e, $target) {
        var rect = $target[0].getBoundingClientRect();
        var midY = rect.top + rect.height / 2;
        return e.originalEvent.clientY < midY ? 'before' : 'after';
    }

    // Dragover on canvas items
    $canvas.on('dragover', '.mp-cb-item', function (e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = (dragFromPalette ? 'copy' : 'move');

        if (dragSrcId && $(this).data('id') === dragSrcId) return;

        $canvas.find('.mp-cb-item').removeClass('is-drag-above is-drag-below');
        $dropIndicator.detach();

        var pos = getDropPosition(e, $(this));
        if (pos === 'before') {
            $(this).before($dropIndicator);
        } else {
            $(this).after($dropIndicator);
        }
    });

    // Dragover / dragleave on canvas container
    $canvas[0].addEventListener('dragover', function (e) {
        e.preventDefault();
        $canvas.addClass('is-drag-over');
    });
    $canvas[0].addEventListener('dragleave', function (e) {
        if (!$canvas[0].contains(e.relatedTarget)) {
            $canvas.removeClass('is-drag-over');
            $dropIndicator.detach();
        }
    });

    // Drop handler
    $canvas[0].addEventListener('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();

        // Determine insert index from drop indicator position
        var insertIdx = canvasItems.length;
        var indicatorEl = $dropIndicator[0];

        if (indicatorEl && indicatorEl.parentNode === $canvas[0]) {
            var nextItem = $dropIndicator.next('.mp-cb-item');
            var prevItem = $dropIndicator.prev('.mp-cb-item');
            if (nextItem.length) {
                insertIdx = canvasItems.findIndex(function (i) { return i.id === nextItem.data('id'); });
            } else if (prevItem.length) {
                insertIdx = canvasItems.findIndex(function (i) { return i.id === prevItem.data('id'); }) + 1;
            }
        } else {
            var $target = $(e.target).closest('.mp-cb-item');
            if ($target.length) {
                var pos = getDropPosition(e, $target);
                var targetIdx = canvasItems.findIndex(function (i) { return i.id === $target.data('id'); });
                insertIdx = pos === 'before' ? targetIdx : targetIdx + 1;
            }
        }

        // Handle palette drop (new element)
        if (dragFromPalette) {
            addElement(dragFromPalette, null, insertIdx);
            dragFromPalette = null;
            cleanupDragState();
            return;
        }

        // Handle canvas reorder
        if (dragSrcId) {
            var srcIdx = canvasItems.findIndex(function (i) { return i.id === dragSrcId; });
            if (srcIdx > -1 && insertIdx !== srcIdx) {
                var moved = canvasItems.splice(srcIdx, 1)[0];
                var adjustedIdx = insertIdx > srcIdx ? insertIdx - 1 : insertIdx;
                canvasItems.splice(adjustedIdx, 0, moved);
                renderCanvas();
                renderPreview();
            }
            dragSrcId = null;
        }
        cleanupDragState();
    });

    /* ═══════════════════════════════════════════════════
     *  Preview renderer
     * ═══════════════════════════════════════════════════ */

    // Header title change → preview
    $headerTitle.on('input', function () { renderPreview(); });

    // Color change → preview
    $('#mp-cb-colors').on('change', 'input[name="mp_cb_color"]', function () {
        renderPreview();
    });

    // Variable chip click → copy to clipboard
    $(document).on('click', '.mp-cb-var-chip', function () {
        var v = $(this).data('var');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(v);
            showToast('Đã copy: ' + v, 'success');
        }
    });

    function renderPreview() {
        var title = $headerTitle.val() || 'Thông báo';
        var colorName = $('input[name="mp_cb_color"]:checked').val() || 'green';
        var colorMap = {
            blue: '#3370ff', green: '#34c724', orange: '#ff7d00',
            red: '#f54a45', purple: '#7c3aed', indigo: '#4f46e5', turquoise: '#2dd4bf'
        };
        var color = colorMap[colorName] || '#34c724';

        var html = '<div class="mp-cb-preview-card__header" style="border-bottom-color:' + color + ';">'
                 + substituteVars(escHtml(title)) + '</div>';
        html += '<div class="mp-cb-preview-card__body">';

        canvasItems.forEach(function (item) {
            switch (item.type) {
                case 'text':
                    html += '<div class="mp-cb-preview-card__text">' + substituteVars(escHtml(item.content || '')) + '</div>';
                    break;
                case 'hr':
                    html += '<hr class="mp-cb-preview-card__hr">';
                    break;
                case 'fields':
                    html += '<div class="mp-cb-preview-card__fields">';
                    (item.fields || []).forEach(function (f) {
                        html += '<div class="mp-cb-preview-card__field">';
                        html += '<div class="mp-cb-preview-card__field-label">' + substituteVars(escHtml(f.label || '')) + '</div>';
                        html += '<div class="mp-cb-preview-card__field-value">' + substituteVars(escHtml(f.value || '')) + '</div>';
                        html += '</div>';
                    });
                    html += '</div>';
                    break;
                case 'note':
                    html += '<div class="mp-cb-preview-card__note">' + substituteVars(escHtml(item.content || '')) + '</div>';
                    break;
                case 'url_button':
                    html += '<div class="mp-cb-preview-card__actions">';
                    html += '<span class="mp-cb-preview-card__btn" style="border-color:#3370ff;color:#3370ff;">'
                          + substituteVars(escHtml(item.text || 'Link')) + '</span>';
                    html += '</div>';
                    break;
            }
        });
        html += '</div>';

        $previewCard.html(html).css('--preview-color', color);
    }

    function substituteVars(str) {
        Object.keys(sampleVars).forEach(function (k) {
            str = str.split(k).join('<b>' + sampleVars[k] + '</b>');
        });
        return str;
    }

    /* ═══════════════════════════════════════════════════
     *  Template management
     * ═══════════════════════════════════════════════════ */

    function loadTemplate(tpl) {
        canvasItems = [];
        idCounter = 0;
        if (!tpl) tpl = defaultTpl;

        $headerTitle.val(tpl.header_title || '💰 Nhận tiền thành công');
        var c = tpl.header_color || 'green';
        $('input[name="mp_cb_color"][value="' + c + '"]').prop('checked', true);

        (tpl.elements || []).forEach(function (el) {
            addElement(el.type, el);
        });

        if (canvasItems.length === 0) {
            (defaultTpl.elements || []).forEach(function (el) {
                addElement(el.type, el);
            });
        }
        renderPreview();
    }

    function serializeTemplate() {
        var tpl = {
            header_title: $headerTitle.val() || 'Thông báo',
            header_color: $('input[name="mp_cb_color"]:checked').val() || 'green',
            elements: []
        };

        canvasItems.forEach(function (item) {
            var el = { type: item.type };
            switch (item.type) {
                case 'text': case 'note':
                    el.content = item.content || '';
                    break;
                case 'fields':
                    el.fields = (item.fields || []).map(function (f) {
                        return { label: f.label || '', value: f.value || '' };
                    });
                    break;
                case 'url_button':
                    el.text = item.text || '';
                    el.url  = item.url  || '';
                    break;
            }
            tpl.elements.push(el);
        });
        return tpl;
    }

    /* ───── Modal actions ────────────────────────────── */

    // Open Card Builder
    $('#mp-pf-open-card-builder').on('click', function () {
        currentMode = 'credit';
        $('.mp-cb-tab').removeClass('is-active');
        $('.mp-cb-tab[data-mode="credit"]').addClass('is-active');
        loadTemplate(window._mpCurrentCardTemplate);
        openModal($builderModal);
    });

    // Tab switching
    $(document).on('click', '.mp-cb-tab', function () {
        var newMode = $(this).data('mode');
        if (newMode === currentMode) return;

        // Save current canvas to the right global var
        var serialized = serializeTemplate();
        if (currentMode === 'credit') {
            window._mpCurrentCardTemplate = serialized;
        } else {
            window._mpCurrentCardTemplateDebit = serialized;
        }

        // Switch mode
        currentMode = newMode;
        $('.mp-cb-tab').removeClass('is-active');
        $(this).addClass('is-active');

        // Load the other template
        if (currentMode === 'credit') {
            loadTemplate(window._mpCurrentCardTemplate);
        } else {
            loadTemplate(window._mpCurrentCardTemplateDebit || defaultDebitTpl);
        }
    });

    // Apply
    $('#mp-cb-apply').on('click', function () {
        var serialized = serializeTemplate();
        if (currentMode === 'credit') {
            window._mpCurrentCardTemplate = serialized;
        } else {
            window._mpCurrentCardTemplateDebit = serialized;
        }
        closeModal($builderModal);
        showToast('Đã áp dụng template ' + (currentMode === 'credit' ? 'Tiền vào' : 'Tiền ra') + '!', 'success');
    });

    // Reset
    $('#mp-cb-reset').on('click', function () {
        var resetTpl = currentMode === 'credit' ? defaultTpl : defaultDebitTpl;
        loadTemplate(resetTpl);
        showToast('Đã reset về mặc định (' + (currentMode === 'credit' ? 'Tiền vào' : 'Tiền ra') + ')', 'info');
    });

    // Cancel / Close
    $('#mp-cb-cancel, #mp-cb-close').on('click', function () {
        closeModal($builderModal);
    });
    $builderModal.find('.mp-modal__backdrop').on('click', function () {
        closeModal($builderModal);
    });

})(jQuery);
