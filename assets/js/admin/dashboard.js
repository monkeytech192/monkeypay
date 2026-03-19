/**
 * MonkeyPay Admin — Dashboard Page
 *
 * Bank data loading, transaction rendering, auto-refresh,
 * dashboard API key pills, connection logos, and create key modal.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

(function ($) {
    'use strict';

    const MP = window.MonkeyPay;

    // ─── Helpers ────────────────────────────────────

    function toApiDate(dateStr) {
        // yyyy-mm-dd → DD/MM/YYYY
        if (!dateStr) return '';
        const [y, m, d] = dateStr.split('-');
        return `${d}/${m}/${y}`;
    }

    function initDashboardDates() {
        // Use Vietnam time (UTC+7) to get correct "today"
        const now = new Date();
        const vnNow = new Date(now.getTime() + 7 * 60 * 60 * 1000);
        const today = vnNow.toISOString().split('T')[0];
        $('#mp-date-from').val(today);
        $('#mp-date-to').val(today);
    }

    /**
     * Mask an API key string: show first 6 + last 3 chars, middle replaced with dots.
     * e.g. "mp_live_abcdefghijklmnop" → "mp_liv•••nop"
     */
    function maskApiKey(key) {
        if (!key || key.length <= 10) return key || '';
        return key.substring(0, 6) + '•••' + key.substring(key.length - 3);
    }

    // ─── Load Bank Dashboard ────────────────────────

    async function loadBankDashboard() {
        const fromVal = $('#mp-date-from').val();
        const toVal = $('#mp-date-to').val();
        const from = toApiDate(fromVal);
        const to = toApiDate(toVal);
        const qs = from && to ? `?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}` : '';

        // Show loading
        $('#mp-refresh-data').addClass('loading');

        try {
            // Fetch summary (balance + stats)
            const summaryRes = await $.ajax({
                url: MP.restUrl + 'bank/summary' + qs,
                method: 'GET',
                headers: { 'X-WP-Nonce': MP.nonce },
            });

            if (summaryRes && summaryRes.success && summaryRes.data) {
                const d = summaryRes.data;
                // Balance: if null (multi-merchant mode), show account info instead
                if (d.balance !== null && d.balance !== undefined) {
                    $('#mp-balance-amount').text(MP.formatVND(d.balance));
                } else {
                    $('#mp-balance-amount').text(d.account_name || 'N/A');
                    // Update label from "SỐ DƯ HIỆN TẠI" to "TÀI KHOẢN NHẬN"
                    $('#mp-balance-label').text('TÀI KHOẢN NHẬN');
                }
                $('#mp-balance-account').text(
                    (d.account_name || '') + (d.account_number ? ' • ' + d.account_number : '')
                );
                $('#mp-stat-in').text(MP.formatVND(d.total_in));
                $('#mp-stat-out').text(MP.formatVND(d.total_out));
                $('#mp-stat-total').text(String(d.total_transactions || 0));
                $('#mp-stat-in-count').text((d.count_in || 0) + ' GD');
                $('#mp-stat-out-count').text((d.count_out || 0) + ' GD');
            } else {
                $('#mp-balance-amount').text('Không khả dụng');
                $('#mp-balance-account').text('Kiểm tra kết nối server');
            }
        } catch (err) {
            console.error('Bank summary error:', err);
            $('#mp-balance-amount').text('Lỗi kết nối');
            $('#mp-balance-account').text(err.responseJSON?.message || err.statusText || '');
        }

        try {
            // Fetch transaction history
            const historyRes = await $.ajax({
                url: MP.restUrl + 'bank/history' + qs,
                method: 'GET',
                headers: { 'X-WP-Nonce': MP.nonce },
            });

            $('#mp-tx-loading').hide();

            if (historyRes && historyRes.success && historyRes.data) {
                const txs = historyRes.data.transactions || [];
                if (txs.length === 0) {
                    $('#mp-tx-table').hide();
                    $('#mp-tx-empty').show();
                    $('#mp-tx-view-all').hide();
                } else {
                    // Dashboard: show max 5 recent transactions
                    const MAX_DASHBOARD_ROWS = 5;
                    const displayTxs = txs.slice(0, MAX_DASHBOARD_ROWS);
                    renderTransactions(displayTxs);
                    $('#mp-tx-empty').hide();
                    $('#mp-tx-table').show();

                    // Show "View All" link if more transactions exist
                    if (txs.length > MAX_DASHBOARD_ROWS) {
                        $('#mp-tx-view-all').show();
                    } else {
                        $('#mp-tx-view-all').hide();
                    }
                }
            } else {
                $('#mp-tx-table').hide();
                $('#mp-tx-empty').show();
                $('#mp-tx-view-all').hide();
            }
        } catch (err) {
            console.error('Bank history error:', err);
            $('#mp-tx-loading').hide();
            $('#mp-tx-table').hide();
            $('#mp-tx-empty').show();
        }

        $('#mp-refresh-data').removeClass('loading');
    }

    // ─── Render Transactions ────────────────────────

    function renderTransactions(txs) {
        const tbody = $('#mp-tx-tbody');
        tbody.empty();

        txs.forEach(tx => {
            const credit = parseFloat(tx.creditAmount || 0);
            const debit = parseFloat(tx.debitAmount || 0);
            const isCredit = credit > 0;
            const amount = isCredit ? credit : debit;
            const amountClass = isCredit ? 'mp-tx-amount--credit' : 'mp-tx-amount--debit';
            const amountPrefix = isCredit ? '+' : '-';
            const desc = tx.transactionDesc || tx.description || '—';
            const time = tx.transactionDate || tx.postDate || '';
            const balance = tx.balanceAvailable;

            const tr = $('<tr>')
                .append($('<td>').addClass('mp-tx-col-time').html(`<span class="mp-tx-time">${MP.escHtml(time)}</span>`))
                .append($('<td>').addClass('mp-tx-col-desc').html(`<span class="mp-tx-desc" title="${MP.escHtml(desc)}">${MP.escHtml(desc)}</span>`))
                .append($('<td>').addClass('mp-tx-col-amount ' + amountClass).text(amountPrefix + MP.formatVND(amount)))
                .append($('<td>').addClass('mp-tx-col-balance').text(balance != null ? MP.formatVND(balance) : '—'));

            tbody.append(tr);
        });
    }

    // ─── Dashboard API Keys ────────────────────────

    /**
     * Fetch active API keys and render as masked pills on dashboard.
     */
    async function loadDashboardApiKeys() {
        const $container = $('#mp-qa-apikeys-pills');
        if (!$container.length) return;

        try {
            const res = await $.ajax({
                url: MP.restUrl + 'api-keys',
                method: 'GET',
                headers: { 'X-WP-Nonce': MP.nonce },
            });

            $container.empty();

            if (res && res.success && res.data) {
                // Filter active keys only
                const activeKeys = (res.data || []).filter(function (k) {
                    return k.status === 'active';
                });

                if (activeKeys.length === 0) {
                    $container.html('<span class="mp-dash-pills__empty">Chưa có API key nào</span>');
                    return;
                }

                // Show max 3 pills
                var displayKeys = activeKeys.slice(0, 3);
                displayKeys.forEach(function (key) {
                    var prefix = key.key_prefix || '';
                    var label = key.label || 'Key';
                    var masked = maskApiKey(prefix);
                    var $pill = $('<span class="mp-dash-pill mp-dash-pill--active" title="' + MP.escHtml(masked) + '">' +
                        '<span class="mp-dash-pill__dot"></span>' +
                        '<span class="mp-dash-pill__name">' + MP.escHtml(label) + '</span>' +
                        '<button type="button" class="mp-dash-pill__copy" data-copy="' + MP.escHtml(prefix) + '" title="Sao chép key prefix">' +
                            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>' +
                        '</button>' +
                        '</span>');
                    $container.append($pill);
                });

                // Quick copy handler for pills
                $container.off('click', '.mp-dash-pill__copy').on('click', '.mp-dash-pill__copy', function (e) {
                    e.stopPropagation();
                    var text = $(this).data('copy') || $(this).attr('data-copy') || '';
                    if (!text) return;
                    navigator.clipboard.writeText(text).then(function () {
                        MP.showToast('Đã sao chép key prefix', 'success');
                    });
                });

                // Show +N more if exists
                if (activeKeys.length > 3) {
                    $container.append('<span class="mp-dash-pill mp-dash-pill--more">+' + (activeKeys.length - 3) + '</span>');
                }
            } else {
                // No data or not successful — treat as empty (not an error)
                $container.html('<span class="mp-dash-pills__empty">Chưa có API key nào</span>');
            }
        } catch (err) {
            // API key not configured or server unreachable — show empty state, not error
            console.warn('Dashboard API keys:', err.statusText || 'unavailable');
            $container.html('<span class="mp-dash-pills__empty">Chưa có API key nào</span>');
        }
    }

    // ─── Dashboard Connections ────────────────────────

    /**
     * Render enabled connections as mini logo badges on dashboard.
     * Uses window.mpPlatformMeta and window.mpConnections injected by PHP.
     */
    function loadDashboardConnections() {
        const $container = $('#mp-qa-conn-logos');
        if (!$container.length) return;

        const meta = window.mpPlatformMeta || {};
        const conns = window.mpConnections || [];

        // Filter enabled connections
        var enabled = conns.filter(function (c) { return c.enabled; });

        $container.empty();

        if (enabled.length === 0) {
            $container.html('<span class="mp-dash-pills__empty">Chưa có kết nối nào</span>');
            return;
        }

        // Deduplicate by platform
        var seen = {};
        enabled.forEach(function (conn) {
            var platform = conn.platform || '';
            if (seen[platform]) return;
            seen[platform] = true;

            var pm = meta[platform] || {};
            var color = pm.color || '#6366f1';
            var name = pm.label || platform;

            var $badge = $('<span class="mp-dash-logo-badge" title="' + MP.escHtml(name) + '" style="--badge-color:' + color + '">' +
                '<span class="mp-dash-logo-badge__dot"></span>' +
                '<span class="mp-dash-logo-badge__name">' + MP.escHtml(name) + '</span>' +
                '</span>');
            $container.append($badge);
        });
    }

    // ─── Dashboard Payment Gateways ────────────────────

    /**
     * Render configured payment gateways as bank logo badges on dashboard.
     * Fetches from REST API GET /gateways to get real gateway data.
     */
    function loadDashboardGateways() {
        var $container = $('#mp-qa-gateways-logos');
        if (!$container.length) return;

        // Bank code to VietQR logo map
        var bankLogos = {
            mbbank:      'https://api.vietqr.io/img/MB.png',
            vpbank:      'https://api.vietqr.io/img/VPB.png',
            vietcombank: 'https://api.vietqr.io/img/VCB.png',
            bidv:        'https://api.vietqr.io/img/BIDV.png',
            techcombank: 'https://api.vietqr.io/img/TCB.png',
            acb:         'https://api.vietqr.io/img/ACB.png',
            tpbank:      'https://api.vietqr.io/img/TPB.png',
            sacombank:   'https://api.vietqr.io/img/STB.png',
        };

        $.ajax({
            url: MP.restUrl + 'gateways',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', MP.nonce);
            },
            success: function (res) {
                // Response: { success: true, data: { gateways: [...] } }
                var serverData = res.data || res || {};
                var gateways = serverData.gateways || serverData || [];
                if (!Array.isArray(gateways)) gateways = [];
                $container.empty();

                if (!gateways.length) {
                    $container.html('<span class="mp-dash-pills__empty">Chưa có cổng thanh toán</span>');
                    return;
                }

                gateways.forEach(function (gw) {
                    var code = (gw.bank_code || '').toLowerCase();
                    var logo = bankLogos[code] || '';
                    var name = gw.bank_code || 'Bank';
                    var acct = gw.account_number || '';
                    // Mask account number: show first 3 + last 3
                    var masked = acct.length > 6
                        ? acct.substring(0, 3) + '***' + acct.substring(acct.length - 3)
                        : acct;

                    var html = '<span class="mp-dash-logo-badge" title="' + MP.escHtml(name + ' - ' + masked) + '" style="--badge-color:#10b981">';
                    if (logo) {
                        html += '<img src="' + MP.escHtml(logo) + '" alt="' + MP.escHtml(name) + '" class="mp-dash-logo-badge__icon" style="width:18px;height:18px;border-radius:4px;object-fit:contain;" />';
                    } else {
                        html += '<span class="mp-dash-logo-badge__dot"></span>';
                    }
                    html += '<span class="mp-dash-logo-badge__name">' + MP.escHtml(name) + '</span>';
                    html += '</span>';
                    $container.append($(html));
                });
            },
            error: function () {
                $container.empty().html('<span class="mp-dash-pills__empty">Chưa có cổng thanh toán</span>');
            },
        });
    }

    // ─── Dashboard Create Key Modal ────────────────────

    /**
     * Handle create key flow directly from dashboard modal.
     */
    function initDashboardCreateKey() {
        var $createModal = $('#mp-create-key-modal');
        var $showModal = $('#mp-show-key-modal');
        if (!$createModal.length) return;

        // Open create modal from Quick Action
        $('#mp-qa-create-key').on('click', function (e) {
            e.preventDefault();
            $('#mp-new-key-label').val('');
            MP.openModal($createModal);
        });

        // Close modals
        $(document).on('click', '.mp-dash-modal-cancel, #mp-create-modal-close', function () {
            MP.closeModal($createModal);
            MP.closeModal($showModal);
        });

        // Confirm create key
        $('#mp-confirm-create-key').on('click', async function () {
            var $btn = $(this);
            if ($btn.prop('disabled')) return;
            $btn.prop('disabled', true).css('opacity', '0.6');

            var label = $.trim($('#mp-new-key-label').val());

            try {
                var res = await $.ajax({
                    url: MP.restUrl + 'api-keys',
                    method: 'POST',
                    headers: { 'X-WP-Nonce': MP.nonce },
                    contentType: 'application/json',
                    data: JSON.stringify({ label: label || 'Dashboard Key' }),
                });

                if (res && res.success && res.data) {
                    var fullKey = res.data.api_key || res.data.key || '';

                    // Close create modal, open show key modal
                    MP.closeModal($createModal);

                    $('#mp-new-key-value').text(fullKey);
                    $('#mp-copy-new-key').attr('data-copy', fullKey).data('copy', fullKey);
                    MP.openModal($showModal);

                    // Refresh dashboard API key pills
                    loadDashboardApiKeys();

                    if (typeof MP.showToast === 'function') {
                        MP.showToast('API Key đã tạo thành công!', 'success');
                    }
                } else {
                    var msg = (res && res.data && res.data.message) || (res && res.message) || 'Không thể tạo API Key';
                    MP.showToast(msg, 'error');
                }
            } catch (err) {
                console.error('Create key error:', err);
                var errMsg = 'Không thể kết nối đến server';
                if (err.responseJSON) {
                    errMsg = err.responseJSON.message || err.responseJSON.error || errMsg;
                } else if (err.status === 404) {
                    errMsg = 'API endpoint không khả dụng. Kiểm tra cấu hình server.';
                } else if (err.status === 0) {
                    errMsg = 'Không thể kết nối đến server. Kiểm tra mạng.';
                } else if (err.statusText && err.statusText !== 'error') {
                    errMsg = 'Lỗi server: ' + err.statusText;
                }
                MP.showToast(errMsg, 'error');
            }

            $btn.prop('disabled', false).css('opacity', '');
        });
    }

    // ─── Dashboard Create Gateway Modal ─────────────

    /**
     * Handle create gateway flow directly from dashboard modal.
     * Custom bank dropdown with logos + POST /gateways.
     */
    function initDashboardCreateGateway() {
        var $gwModal = $('#mp-create-gateway-modal');
        if (!$gwModal.length) return;

        var $trigger = $('#mp-gw-bank-trigger');
        var $dropdown = $('#mp-gw-bank-dropdown');
        var $hiddenInput = $('#mp-gw-bank-code');
        var selectedBank = null;

        // Toggle dropdown
        $trigger.on('click', function (e) {
            e.stopPropagation();
            var isOpen = $dropdown.hasClass('mp-bank-select__dropdown--open');
            $dropdown.toggleClass('mp-bank-select__dropdown--open', !isOpen);
            $trigger.toggleClass('mp-bank-select__trigger--open', !isOpen);
        });

        // Select bank option
        $dropdown.on('click', '.mp-bank-option', function () {
            var $opt = $(this);
            selectedBank = {
                code: $opt.data('code'),
                name: $opt.data('name'),
                logo: $opt.data('logo'),
            };
            $hiddenInput.val(selectedBank.code);

            // Update trigger display
            $trigger.html(
                '<img src="' + MP.escHtml(selectedBank.logo) + '" alt="" class="mp-bank-select__selected-logo" />' +
                '<span class="mp-bank-select__selected-name">' + MP.escHtml(selectedBank.name) + '</span>' +
                '<svg class="mp-bank-select__chevron" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>'
            );

            // Mark selected
            $dropdown.find('.mp-bank-option').removeClass('mp-bank-option--active');
            $opt.addClass('mp-bank-option--active');

            // Close dropdown
            $dropdown.removeClass('mp-bank-select__dropdown--open');
            $trigger.removeClass('mp-bank-select__trigger--open');
        });

        // Close dropdown on outside click
        $(document).on('click', function () {
            $dropdown.removeClass('mp-bank-select__dropdown--open');
            $trigger.removeClass('mp-bank-select__trigger--open');
        });

        // Prevent dropdown close when clicking inside modal
        $gwModal.on('click', function (e) {
            if (!$(e.target).closest('.mp-bank-select').length) {
                $dropdown.removeClass('mp-bank-select__dropdown--open');
                $trigger.removeClass('mp-bank-select__trigger--open');
            }
        });

        // Open modal from Quick Action card
        $('#mp-qa-create-gateway').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            // Reset form
            selectedBank = null;
            $hiddenInput.val('');
            $trigger.html(
                '<span class="mp-bank-select__placeholder">Chọn ngân hàng...</span>' +
                '<svg class="mp-bank-select__chevron" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>'
            );
            $dropdown.find('.mp-bank-option').removeClass('mp-bank-option--active');
            $('#mp-gw-account-number').val('');
            $('#mp-gw-account-name').val('');
            MP.openModal($gwModal);
        });

        // Close modal
        $(document).on('click', '.mp-gw-modal-cancel, #mp-gateway-modal-close', function () {
            MP.closeModal($gwModal);
        });

        // Confirm create gateway
        $('#mp-confirm-create-gateway').on('click', async function () {
            var $btn = $(this);
            if ($btn.prop('disabled')) return;

            var bankCode = $hiddenInput.val();
            var accountNumber = $.trim($('#mp-gw-account-number').val());

            if (!bankCode) {
                if (typeof MP.showToast === 'function') {
                    MP.showToast('Vui lòng chọn ngân hàng', 'error');
                }
                return;
            }
            if (!accountNumber) {
                if (typeof MP.showToast === 'function') {
                    MP.showToast('Vui lòng nhập số tài khoản', 'error');
                }
                return;
            }

            $btn.prop('disabled', true).css('opacity', '0.6');

            var payload = {
                bank_code: bankCode,
                bank_name: selectedBank ? selectedBank.name : bankCode,
                account_number: accountNumber,
                account_name: $.trim($('#mp-gw-account-name').val()).toUpperCase(),
            };

            try {
                var res = await $.ajax({
                    url: MP.restUrl + 'gateways',
                    method: 'POST',
                    headers: { 'X-WP-Nonce': MP.nonce },
                    contentType: 'application/json',
                    data: JSON.stringify(payload),
                });

                if (res && res.success) {
                    MP.closeModal($gwModal);
                    loadDashboardGateways();

                    if (typeof MP.showToast === 'function') {
                        MP.showToast('Đã tạo cổng thanh toán thành công!', 'success');
                    }
                } else {
                    var msg = (res && res.data && (res.data.error || res.data.message)) || 'Có lỗi xảy ra';
                    if (typeof MP.showToast === 'function') {
                        MP.showToast(msg, 'error');
                    }
                }
            } catch (err) {
                console.error('Create gateway error:', err);
                var errMsg = (err.responseJSON && (err.responseJSON.message || err.responseJSON.error)) || 'Có lỗi xảy ra';
                if (typeof MP.showToast === 'function') {
                    MP.showToast(errMsg, 'error');
                }
            }

            $btn.prop('disabled', false).css('opacity', '');
        });
    }

    // ─── Connection Path Status ────────────────────────

    /**
     * Update a single flow node's status dot.
     * @param {string} nodeId - e.g. 'mp-flow-bank'
     * @param {'ok'|'error'|'checking'} status
     */
    function setNodeStatus(nodeId, status) {
        var $status = $('#' + nodeId + '-status');
        var dotClass = 'mp-status-dot mp-status-dot--' + status;
        var label = status === 'ok' ? 'OK' : status === 'error' ? 'Lỗi' : '...';
        $status.html('<span class="' + dotClass + '"></span> ' + label);
    }

    /**
     * Update connector line between nodes.
     * @param {string} lineId - e.g. 'mp-flow-line-1'
     * @param {'ok'|'error'|''} status
     */
    function setLineStatus(lineId, status) {
        var $line = $('#' + lineId);
        $line.removeClass('mp-flow-line--ok mp-flow-line--error');
        if (status) {
            $line.addClass('mp-flow-line--' + status);
        }
    }

    /**
     * Check connection flow: Bank → Server → Website
     * Calls /monkeypay/v1/health which proxies to the MonkeyPay server.
     * If the server is healthy and returns bank connectivity info, all nodes are OK.
     */
    async function checkConnectionFlow() {
        // Reset all to checking
        setNodeStatus('mp-flow-bank', 'checking');
        setNodeStatus('mp-flow-server', 'checking');
        setNodeStatus('mp-flow-website', 'checking');
        setLineStatus('mp-flow-line-1', '');
        setLineStatus('mp-flow-line-2', '');

        // Website is always OK if this page loads
        setTimeout(function () {
            setNodeStatus('mp-flow-website', 'ok');
        }, 300);

        try {
            var result = await $.ajax({
                url: MP.restUrl + 'health',
                method: 'GET',
                headers: { 'X-WP-Nonce': MP.nonce },
                timeout: 15000,
            });

            // Server responded
            if (result && result.success) {
                setNodeStatus('mp-flow-server', 'ok');
                setLineStatus('mp-flow-line-2', 'ok');

                // Check if bank info is available in health response
                var data = result.data || {};
                if (data.status === 'ok' || data.bank_connected !== false) {
                    setNodeStatus('mp-flow-bank', 'ok');
                    setLineStatus('mp-flow-line-1', 'ok');
                } else {
                    setNodeStatus('mp-flow-bank', 'error');
                    setLineStatus('mp-flow-line-1', 'error');
                }
            } else {
                setNodeStatus('mp-flow-server', 'error');
                setLineStatus('mp-flow-line-2', 'error');
                setNodeStatus('mp-flow-bank', 'error');
                setLineStatus('mp-flow-line-1', 'error');
            }
        } catch (err) {
            console.error('Connection flow check error:', err);
            setNodeStatus('mp-flow-server', 'error');
            setLineStatus('mp-flow-line-2', 'error');
            setNodeStatus('mp-flow-bank', 'error');
            setLineStatus('mp-flow-line-1', 'error');
        }
    }

    // ─── Copy Webhook URL ──────────────────────────────

    function initCopyButtons() {
        $(document).on('click', '.monkeypay-btn-copy', function () {
            var $btn = $(this);
            var text = $btn.data('copy');
            if (!text) return;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    showCopyFeedback($btn);
                });
            } else {
                // Fallback for older browsers
                var $temp = $('<textarea>').val(text).appendTo('body').select();
                document.execCommand('copy');
                $temp.remove();
                showCopyFeedback($btn);
            }
        });
    }

    function showCopyFeedback($btn) {
        var $icon = $btn.find('svg');
        var originalHtml = $btn.html();
        $btn.html('<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>');
        $btn.css('color', 'var(--mp-success)');
        setTimeout(function () {
            $btn.html(originalHtml);
            $btn.css('color', '');
        }, 1500);
    }

    // ─── Init ───────────────────────────────────────

    $(document).ready(function () {
        // Copy buttons (global)
        initCopyButtons();

        // Dashboard — bank data + auto-refresh
        if ($('#mp-dashboard-hero').length) {
            initDashboardDates();
            loadBankDashboard();
            $('#mp-refresh-data').on('click', loadBankDashboard);
            $('#mp-date-from, #mp-date-to').on('change', loadBankDashboard);

            // Auto-refresh every 30 seconds (only when tab is visible)
            var autoRefreshTimer = setInterval(function () {
                if (!document.hidden) {
                    loadBankDashboard();
                }
            }, 30000);

            // Cleanup on page unload
            $(window).on('beforeunload', function () {
                clearInterval(autoRefreshTimer);
            });

            // ── Dashboard Quick Action card data ──
            loadDashboardApiKeys();
            loadDashboardConnections();
            loadDashboardGateways();
            initDashboardCreateKey();
            initDashboardCreateGateway();
        }

        // Connection path — check flow status
        if ($('#mp-connection-path').length) {
            checkConnectionFlow();
            $('#mp-test-flow').on('click', function () {
                checkConnectionFlow();
            });
        }
    });

})(jQuery);
