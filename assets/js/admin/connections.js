/**
 * MonkeyPay – Connections Page Module
 *
 * Handles the platform connection grid & modal:
 *   - Open / close platform modal
 *   - Toggle connection enabled state
 *   - Create / update / delete connection via REST API
 *   - Test webhook endpoint
 *
 * Dependencies: jQuery, MonkeyPay global (MP)
 * @since 3.0.0
 */
(function ($) {
    'use strict';

    if (typeof window.MonkeyPay === 'undefined') return;

    const MP          = window.MonkeyPay;
    const restUrl     = MP.restUrl || '';
    const nonce       = MP.nonce   || '';
    const showToast   = MP.showToast   || function () {};
    const openModal   = MP.openModal   || function () {};
    const closeModal  = MP.closeModal  || function () {};

    /* ───── DOM references ──────────────────────────────── */
    const modalEl = document.getElementById('mp-platform-modal');
    const grid    = document.getElementById('mp-conn-grid');
    if (!modalEl || !grid) return; // Not on connections page

    const $modal = $(modalEl);
    const $form  = $('#mp-platform-form');
    const meta   = window.mpPlatformMeta || {};
    const allConns = window.mpConnections || [];

    // Index connections by platform
    const connByPlatform = {};
    allConns.forEach(function (c) {
        const p = c.platform || '';
        if (!connByPlatform[p]) connByPlatform[p] = [];
        connByPlatform[p].push(c);
    });

    // URL hint per platform
    const urlHints = {
        webhook:       'Nhập URL endpoint nhận dữ liệu POST',
        lark:          'Lấy URL từ Lark Bot → Webhook',
        telegram:      'Dùng bot token: https://api.telegram.org/bot<TOKEN>/sendMessage',
        google_sheets: 'URL của Google Apps Script Web App',
        slack:         'Incoming Webhook URL từ Slack App',
        discord:       'Discord Webhook URL (Settings → Integrations)',
        mqtt:          'Broker URL, ví dụ: mqtt://broker.hivemq.com',
        viber:         'URL từ Viber Bot API',
        whatsapp:      'WhatsApp Business API endpoint',
    };

    /* ───── Open modal for a platform ────────────────── */
    function openPlatformModal(platform, connData) {
        const pm = meta[platform];
        if (!pm) return;

        // Set accent color CSS variable
        modalEl.style.setProperty('--mp-modal-accent', pm.color || '#06b6d4');

        // Platform icon — clone from card
        const cardIcon = grid.querySelector(
            '.mp-conn-card[data-platform="' + platform + '"] .mp-conn-card__logo'
        );
        const iconContainer = document.getElementById('mp-modal-icon');
        if (cardIcon && iconContainer) {
            iconContainer.innerHTML = cardIcon.innerHTML;
            iconContainer.style.color = pm.color || '';
        }

        // Title & subtitle
        $('#mp-modal-title').text(connData ? 'Cấu hình ' + pm.label : 'Kết nối ' + pm.label);
        $('#mp-modal-subtitle').text(pm.description || '');

        // URL hint
        $('#mp-pf-url-hint').text(urlHints[platform] || '');

        // Hidden fields
        $('#mp-pf-platform').val(platform);
        $('#mp-pf-conn-id').val('');

        // Reset form fields
        $('#mp-pf-name').val('');
        $('#mp-pf-url').val('');
        $('#mp-pf-secret').val('');
        $('input[name="mp_pf_events[]"]').prop('checked', false);
        $('input[name="mp_pf_events[]"][value="payment_received"]').prop('checked', true);

        // Show / hide edit-only buttons
        const $deleteBtn = $('#mp-pf-delete');
        const $testBtn   = $('#mp-pf-test');
        const $saveText  = $('#mp-pf-save-text');

        if (connData && connData.id) {
            // Edit mode — populate form
            $('#mp-pf-conn-id').val(connData.id);
            $('#mp-pf-name').val(connData.name || '');
            $('#mp-pf-url').val(connData.webhook_url || '');
            $('#mp-pf-secret').val(connData.secret_key || '');
            $('input[name="mp_pf_events[]"]').prop('checked', false);
            (connData.events || []).forEach(function (evt) {
                $('input[name="mp_pf_events[]"][value="' + evt + '"]').prop('checked', true);
            });
            $deleteBtn.show();
            $testBtn.show();
            $saveText.text('Lưu thay đổi');
        } else {
            $deleteBtn.hide();
            $testBtn.hide();
            $saveText.text('Lưu kết nối');
        }

        // Card Builder group — only for Lark
        const $cbGroup = $('#mp-pf-card-builder-group');
        if (platform === 'lark') {
            $cbGroup.show();
            if (connData && connData.card_template) {
                window._mpCurrentCardTemplate = connData.card_template;
            } else {
                window._mpCurrentCardTemplate = null;
            }
            if (connData && connData.card_template_debit) {
                window._mpCurrentCardTemplateDebit = connData.card_template_debit;
            } else {
                window._mpCurrentCardTemplateDebit = null;
            }
        } else {
            $cbGroup.hide();
            window._mpCurrentCardTemplate = null;
            window._mpCurrentCardTemplateDebit = null;
        }

        // Open with animation
        openModal($modal);
    }

    /* ───── Get active connection for a platform ──────── */
    function getActiveConn(platform) {
        const conns = connByPlatform[platform] || [];
        if (!conns.length) return null;
        return conns.find(function (c) { return c.enabled; }) || conns[0];
    }

    /* ───── Card click → open modal ─────────────────── */
    $(document).on('click', '.mp-conn-card', function (e) {
        if ($(e.target).closest('.mp-conn-toggle-wrap').length) return;

        const card     = $(this);
        const platform = card.data('platform');
        if (card.hasClass('is-coming-soon')) {
            showToast('Nền tảng này sắp ra mắt!', 'info');
            return;
        }
        const conn = getActiveConn(platform);
        openPlatformModal(platform, conn);
    });

    /* ───── Toggle switch logic ─────────────────────── */
    $(document).on('change', '.mp-conn-toggle-input', function (e) {
        e.stopPropagation();
        const $toggle  = $(this);
        const platform = $toggle.data('platform');
        const connId   = $toggle.data('conn-id');
        const checked  = $toggle.is(':checked');

        if (checked && !connId) {
            $toggle.prop('checked', false);
            openPlatformModal(platform, null);
            return;
        }
        if (!connId) return;

        $toggle.prop('disabled', true);
        $.ajax({
            url: restUrl + 'connections/' + connId,
            method: 'PUT',
            headers: { 'X-WP-Nonce': nonce },
            contentType: 'application/json',
            data: JSON.stringify({ enabled: checked }),
            success: function (res) {
                if (res.success) {
                    showToast(checked ? 'Đã bật kết nối' : 'Đã tắt kết nối', 'success');
                    var card = $toggle.closest('.mp-conn-card');
                    var statusEl = card.find('.mp-conn-status');
                    if (checked) {
                        card.addClass('is-active');
                        statusEl.removeClass('is-off is-empty').addClass('is-on');
                        statusEl.html('<span class="mp-conn-status__dot"></span> Đang hoạt động');
                    } else {
                        card.removeClass('is-active');
                        statusEl.removeClass('is-on').addClass('is-off');
                        statusEl.html('<span class="mp-conn-status__dot"></span> Đã tắt');
                    }
                } else {
                    showToast(res.message || 'Lỗi cập nhật', 'error');
                    $toggle.prop('checked', !checked);
                }
            },
            error: function () {
                showToast('Không thể cập nhật trạng thái', 'error');
                $toggle.prop('checked', !checked);
            },
            complete: function () {
                $toggle.prop('disabled', false);
            },
        });
    });

    /* ───── Close modal ─────────────────────────────── */
    $(document).on('click', '#mp-modal-close, #mp-pf-cancel, .mp-modal__backdrop', function () {
        closeModal($modal);
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $modal.hasClass('is-open')) {
            closeModal($modal);
        }
    });

    /* ───── Submit form (Create / Update) ──────────── */
    $form.on('submit', function (e) {
        e.preventDefault();
        var connId   = $('#mp-pf-conn-id').val();
        var platform = $('#mp-pf-platform').val();

        var data = {
            name:        $('#mp-pf-name').val(),
            platform:    platform,
            webhook_url: $('#mp-pf-url').val(),
            secret_key:  $('#mp-pf-secret').val(),
            events:      $('input[name="mp_pf_events[]"]:checked')
                             .map(function () { return this.value; }).get(),
            enabled: true,
        };

        // Inject card_template for Lark connections
        if (platform === 'lark') {
            if (window._mpCurrentCardTemplate) {
                data.card_template = window._mpCurrentCardTemplate;
            }
            if (window._mpCurrentCardTemplateDebit) {
                data.card_template_debit = window._mpCurrentCardTemplateDebit;
            }
        }

        if (!data.webhook_url) {
            showToast('Vui lòng nhập URL webhook', 'error');
            $('#mp-pf-url').focus();
            return;
        }

        var isEdit = !!connId;
        var method = isEdit ? 'PUT' : 'POST';
        var url    = isEdit
            ? restUrl + 'connections/' + connId
            : restUrl + 'connections';

        var $saveBtn = $('#mp-pf-save');
        $saveBtn.prop('disabled', true).addClass('is-loading');

        $.ajax({
            url: url, method: method,
            headers: { 'X-WP-Nonce': nonce },
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function (res) {
                if (res.success) {
                    showToast(isEdit ? 'Đã cập nhật kết nối' : 'Đã tạo kết nối mới', 'success');
                    setTimeout(function () { location.reload(); }, 700);
                } else {
                    showToast(res.message || 'Lỗi lưu kết nối', 'error');
                }
            },
            error: function (xhr) {
                showToast('Lỗi: ' + ((xhr.responseJSON && xhr.responseJSON.message) || xhr.statusText), 'error');
            },
            complete: function () {
                $saveBtn.prop('disabled', false).removeClass('is-loading');
            },
        });
    });

    /* ───── Delete connection ───────────────────────── */
    $(document).on('click', '#mp-pf-delete', function () {
        var connId = $('#mp-pf-conn-id').val();
        if (!connId) return;
        if (!confirm('Bạn có chắc muốn xóa kết nối này?')) return;

        var $btn = $(this);
        $btn.prop('disabled', true).addClass('is-loading');

        $.ajax({
            url: restUrl + 'connections/' + connId,
            method: 'DELETE',
            headers: { 'X-WP-Nonce': nonce },
            success: function (res) {
                if (res.success) {
                    showToast('Đã xóa kết nối', 'success');
                    closeModal($modal);
                    setTimeout(function () { location.reload(); }, 700);
                } else {
                    showToast(res.message || 'Lỗi xóa', 'error');
                }
            },
            error: function () {
                showToast('Không thể xóa kết nối', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).removeClass('is-loading');
            },
        });
    });

    /* ───── Test connection ──────────────────────────── */
    $(document).on('click', '#mp-pf-test', function () {
        var connId = $('#mp-pf-conn-id').val();
        if (!connId) return;

        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html(
            '<svg class="mp-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;animation:mp-spin 1s linear infinite;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Đang test...'
        );

        $.ajax({
            url: restUrl + 'connections/' + connId + '/test',
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce },
            success: function (res) {
                if (res.success) {
                    $btn.html('✅ Thành công!');
                    showToast('Test webhook thành công!', 'success');
                } else {
                    $btn.html('❌ Thất bại');
                    showToast('Test webhook thất bại: ' + (res.message || ''), 'error');
                }
                setTimeout(function () {
                    $btn.prop('disabled', false).html(originalHtml);
                }, 2500);
            },
            error: function () {
                $btn.html('❌ Lỗi');
                showToast('Không thể gửi test webhook', 'error');
                setTimeout(function () {
                    $btn.prop('disabled', false).html(originalHtml);
                }, 2500);
            },
        });
    });

})(jQuery);
