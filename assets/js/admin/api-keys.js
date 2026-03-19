/**
 * MonkeyPay Admin — API Keys Management
 *
 * CRUD operations for API keys via REST proxy endpoints.
 * Full key shown only once on creation.
 *
 * @package MonkeyPay
 * @since   3.1.0
 */

(function ($) {
    'use strict';

    var MP = window.MonkeyPay || {};

    // ── State ───────────────────────────────────────
    var revokeTargetId = null;
    var editingKeyId   = null;

    // ── DOM Refs ────────────────────────────────────
    var $table    = $('#mp-keys-table');
    var $tbody    = $('#mp-keys-tbody');
    var $loading  = $('#mp-keys-loading');
    var $empty    = $('#mp-keys-empty');

    // Modals
    var $createModal  = $('#mp-create-key-modal');
    var $showKeyModal = $('#mp-show-key-modal');
    var $revokeModal  = $('#mp-revoke-key-modal');

    // ── API Call ────────────────────────────────────

    function apiCall(method, endpoint, data) {
        var opts = {
            method  : method,
            url     : MP.restUrl + endpoint,
            headers : { 'X-WP-Nonce': MP.nonce },
            contentType : 'application/json',
            dataType    : 'json',
        };

        if (data) {
            opts.data = JSON.stringify(data);
        }

        return $.ajax(opts);
    }

    // ── Load Keys ──────────────────────────────────

    function loadKeys() {
        $loading.show();
        $table.hide();
        $empty.hide();

        apiCall('GET', 'api-keys')
            .done(function (res) {
                var keys = (res && res.data) ? res.data : [];
                renderKeys(keys);
            })
            .fail(function (xhr) {
                var msg = 'Không thể tải API keys';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
                MP.showToast(msg, 'error');
                $loading.hide();
                $empty.show();
            });
    }

    // ── Render Keys ────────────────────────────────

    function renderKeys(keys) {
        $loading.hide();
        $tbody.empty();

        if (!keys || keys.length === 0) {
            $table.hide();
            $empty.show();
            return;
        }

        $table.show();
        $empty.hide();

        keys.forEach(function (key) {
            var isActive = key.status === 'active';
            var statusClass = isActive ? 'mp-key-status--active' : 'mp-key-status--revoked';
            var statusLabel = isActive ? 'Active' : 'Revoked';
            var prefix = key.key_prefix || '';
            var maskedKey = prefix ? (prefix + '...****') : '••••••••';
            var lastUsed = key.last_used_at ? formatDate(key.last_used_at) : '—';
            var created  = key.created_at ? formatDate(key.created_at) : '—';
            var label    = MP.escHtml(key.label || 'Unnamed');

            var actionsHtml = '';
            if (isActive) {
                actionsHtml = '' +
                    '<button type="button" class="mp-btn--icon mp-btn--copy-key" title="Sao chép key prefix" data-key="' + MP.escHtml(prefix) + '">' +
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>' +
                    '</button>' +
                    '<button type="button" class="mp-btn--icon mp-btn--danger-icon mp-revoke-key-btn" title="Thu hồi" data-id="' + key.id + '" data-label="' + MP.escHtml(label) + '">' +
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>' +
                    '</button>';
            }

            var row = '' +
                '<tr data-key-id="' + key.id + '">' +
                    '<td class="mp-col-label">' +
                        '<div class="mp-key-label">' +
                            '<span class="mp-key-label__text">' + label + '</span>' +
                            (isActive ? '<button type="button" class="mp-key-label__edit" title="Sửa nhãn" data-id="' + key.id + '" data-label="' + MP.escHtml(key.label || '') + '"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>' : '') +
                        '</div>' +
                    '</td>' +
                    '<td class="mp-col-key"><code class="mp-key-value">' + MP.escHtml(maskedKey) + '</code></td>' +
                    '<td class="mp-col-status"><span class="mp-key-status ' + statusClass + '"><span class="mp-key-status__dot"></span> ' + statusLabel + '</span></td>' +
                    '<td class="mp-col-used">' + lastUsed + '</td>' +
                    '<td class="mp-col-created">' + created + '</td>' +
                    '<td class="mp-col-actions"><div class="mp-key-actions">' + actionsHtml + '</div></td>' +
                '</tr>';

            $tbody.append(row);
        });
    }

    // ── Format Date ────────────────────────────────

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return '—';

        var day   = String(d.getDate()).padStart(2, '0');
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var year  = d.getFullYear();
        var hours = String(d.getHours()).padStart(2, '0');
        var mins  = String(d.getMinutes()).padStart(2, '0');

        return day + '/' + month + '/' + year + ' ' + hours + ':' + mins;
    }

    // ── Create Key ─────────────────────────────────

    function handleCreateKey() {
        var label = $.trim($('#mp-new-key-label').val());
        var $btn  = $('#mp-confirm-create-key');

        $btn.prop('disabled', true).text('Đang tạo...');

        apiCall('POST', 'api-keys', { label: label })
            .done(function (res) {
                MP.closeModal($createModal);
                $('#mp-new-key-label').val('');

                if (res && res.data && res.data.api_key) {
                    // Show full key one time
                    var fullKey = res.data.api_key;
                    $('#mp-new-key-value').text(fullKey);
                    $('#mp-copy-new-key').attr('data-copy', fullKey);
                    MP.openModal($showKeyModal);
                }

                MP.showToast('API key đã được tạo!', 'success');
                loadKeys();
            })
            .fail(function (xhr) {
                var msg = 'Không thể tạo API key';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
                MP.showToast(msg, 'error');
            })
            .always(function () {
                $btn.prop('disabled', false).html(
                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> Tạo Key'
                );
            });
    }

    // ── Revoke Key ─────────────────────────────────

    function handleRevokeKey() {
        if (!revokeTargetId) return;
        var $btn = $('#mp-confirm-revoke-key');

        $btn.prop('disabled', true).text('Đang thu hồi...');

        apiCall('POST', 'api-keys/' + revokeTargetId + '/revoke')
            .done(function () {
                MP.closeModal($revokeModal);
                MP.showToast('API key đã bị thu hồi', 'success');
                loadKeys();
            })
            .fail(function (xhr) {
                var msg = 'Không thể thu hồi key';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
                MP.showToast(msg, 'error');
            })
            .always(function () {
                $btn.prop('disabled', false).text('Thu hồi');
                revokeTargetId = null;
            });
    }

    // ── Inline Edit Label ──────────────────────────

    function startEditLabel($btn) {
        var keyId  = $btn.data('id');
        var label  = $btn.data('label') || '';
        var $cell  = $btn.closest('.mp-key-label');

        // Prevent double-editing
        if (editingKeyId === keyId) return;
        editingKeyId = keyId;

        var $display = $cell.find('.mp-key-label__text');
        var $editBtn = $cell.find('.mp-key-label__edit');
        var original = label;

        // Replace text with input
        $display.hide();
        $editBtn.hide();

        var $input = $('<input type="text" class="mp-key-label__input" maxlength="100">')
            .val(original)
            .appendTo($cell);

        $input.focus().select();

        function saveLabel() {
            var newLabel = $.trim($input.val());
            $input.remove();
            $display.show();
            $editBtn.show();
            editingKeyId = null;

            if (newLabel === original || newLabel === '') {
                return;
            }

            $display.text(newLabel);
            $editBtn.data('label', newLabel);

            apiCall('PUT', 'api-keys/' + keyId, { label: newLabel })
                .done(function () {
                    MP.showToast('Đã cập nhật nhãn', 'success');
                })
                .fail(function () {
                    $display.text(original);
                    $editBtn.data('label', original);
                    MP.showToast('Không thể cập nhật nhãn', 'error');
                });
        }

        $input.on('blur', saveLabel);
        $input.on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); saveLabel(); }
            if (e.key === 'Escape') { $input.val(original); saveLabel(); }
        });
    }

    // ── Event Handlers ─────────────────────────────

    $(document).ready(function () {
        // Only init on API keys page
        if (!$table.length) return;

        loadKeys();

        // Open create modal
        $('#mp-create-key-btn').on('click', function () {
            $('#mp-new-key-label').val('');
            MP.openModal($createModal);
        });

        // Confirm create
        $('#mp-confirm-create-key').on('click', handleCreateKey);

        // Enter key in label input -> create
        $('#mp-new-key-label').on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); handleCreateKey(); }
        });

        // Open revoke modal
        $(document).on('click', '.mp-revoke-key-btn', function () {
            revokeTargetId = $(this).data('id');
            var label = $(this).data('label') || 'Unnamed';
            $('#mp-revoke-key-label').text(label);
            MP.openModal($revokeModal);
        });

        // Confirm revoke
        $('#mp-confirm-revoke-key').on('click', handleRevokeKey);

        // Edit label inline
        $(document).on('click', '.mp-key-label__edit', function () {
            startEditLabel($(this));
        });

        // Copy masked key
        $(document).on('click', '.mp-btn--copy-key', function () {
            var key = $(this).data('key');
            if (key) {
                navigator.clipboard.writeText(key).then(function () {
                    MP.showToast('Đã sao chép!', 'success');
                });
            }
        });

        // Close modals (new mp-modal system)
        $(document).on('click', '.mp-apikeys-modal-cancel, #mp-create-modal-close', function () {
            var $modal = $(this).closest('.mp-modal');
            MP.closeModal($modal);
        });

        $(document).on('click', '.mp-modal__backdrop', function () {
            var $modal = $(this).closest('.mp-modal');
            MP.closeModal($modal);
        });

        // Copy new key from Show Key modal
        $('#mp-copy-new-key').on('click', function () {
            var $btn = $(this);
            var key = $btn.attr('data-copy');
            if (key) {
                navigator.clipboard.writeText(key).then(function () {
                    $btn.addClass('is-copied').find('span').text('Đã sao chép!');
                    MP.showToast('Đã sao chép API key!', 'success');
                    setTimeout(function () {
                        $btn.removeClass('is-copied').find('span').text('Sao chép API Key');
                    }, 3000);
                }).catch(function () {
                    // Fallback for older browsers
                    var $temp = $('<textarea>').val(key).appendTo('body').select();
                    document.execCommand('copy');
                    $temp.remove();
                    $btn.addClass('is-copied').find('span').text('Đã sao chép!');
                    MP.showToast('Đã sao chép API key!', 'success');
                    setTimeout(function () {
                        $btn.removeClass('is-copied').find('span').text('Sao chép API Key');
                    }, 3000);
                });
            }
        });
    });

})(jQuery);
