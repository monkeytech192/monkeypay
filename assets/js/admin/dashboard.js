/**
 * MonkeyPay Admin — Dashboard Page
 *
 * Bank data loading, transaction rendering, auto-refresh.
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
                } else {
                    renderTransactions(txs);
                    $('#mp-tx-empty').hide();
                    $('#mp-tx-table').show();
                }
            } else {
                $('#mp-tx-table').hide();
                $('#mp-tx-empty').show();
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

    // ─── Init ───────────────────────────────────────

    $(document).ready(function () {
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
        }
    });

})(jQuery);
