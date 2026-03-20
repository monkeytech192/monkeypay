/**
 * MonkeyPay Admin — Dashboard Page v4.2
 *
 * Bank data, Chart.js cash flow, pill date filter,
 * transaction table, quick-action cards, modals, connection flow.
 * Timezone-aware dates, hourly chart for single-day, i18n support.
 *
 * @package MonkeyPay
 * @since   4.2.0
 */

(function ($) {
    'use strict';

    const MP = window.MonkeyPay;

    // ─── Date Helpers ───────────────────────────────

    /**
     * Convert yyyy-mm-dd → DD/MM/YYYY for API.
     */
    function toApiDate(dateStr) {
        if (!dateStr) return '';
        const [y, m, d] = dateStr.split('-');
        return `${d}/${m}/${y}`;
    }

    /**
     * Get today's date in yyyy-mm-dd using configured timezone.
     * Delegates to i18n module.
     */
    function vnToday() {
        return MP.localToday ? MP.localToday() : new Date().toISOString().split('T')[0];
    }

    /**
     * Format a date string for the API: yyyy-mm-dd.
     */
    function ymd(date) {
        const d = new Date(date);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    /**
     * Get a "now" Date object offset to configured timezone.
     */
    function tzNowDate() {
        try {
            var parts = new Intl.DateTimeFormat('en-CA', {
                timeZone: MP.timezone || 'Asia/Ho_Chi_Minh',
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit',
                hour12: false,
            }).formatToParts(new Date());

            var obj = {};
            parts.forEach(function (p) { obj[p.type] = p.value; });
            return new Date(
                parseInt(obj.year), parseInt(obj.month) - 1, parseInt(obj.day),
                parseInt(obj.hour), parseInt(obj.minute), parseInt(obj.second)
            );
        } catch (e) {
            return new Date();
        }
    }

    /**
     * Check if the current from/to filter is a single day.
     */
    function isSingleDayRange() {
        var from = $('#mp-date-from').val();
        var to   = $('#mp-date-to').val();
        return from && to && from === to;
    }

    /**
     * Calculate date range from a pill range key.
     * Uses timezone-aware "now" date.
     * Returns { from: 'yyyy-mm-dd', to: 'yyyy-mm-dd' }.
     */
    function getDateRange(rangeKey) {
        const tz = tzNowDate();
        const today = ymd(tz);

        switch (rangeKey) {
            case 'today':
                return { from: today, to: today };

            case 'yesterday': {
                const d = new Date(tz);
                d.setDate(d.getDate() - 1);
                const yd = ymd(d);
                return { from: yd, to: yd };
            }

            case '7days': {
                const d = new Date(tz);
                d.setDate(d.getDate() - 6);
                return { from: ymd(d), to: today };
            }

            case '30days': {
                const d = new Date(tz);
                d.setDate(d.getDate() - 29);
                return { from: ymd(d), to: today };
            }

            case 'this_week': {
                const d = new Date(tz);
                const day = d.getDay();
                const diff = day === 0 ? 6 : day - 1;
                d.setDate(d.getDate() - diff);
                return { from: ymd(d), to: today };
            }

            case 'last_week': {
                const d = new Date(tz);
                const day = d.getDay();
                const diff = day === 0 ? 6 : day - 1;
                const thisMonday = new Date(d);
                thisMonday.setDate(d.getDate() - diff);
                const lastMonday = new Date(thisMonday);
                lastMonday.setDate(thisMonday.getDate() - 7);
                const lastSunday = new Date(thisMonday);
                lastSunday.setDate(thisMonday.getDate() - 1);
                return { from: ymd(lastMonday), to: ymd(lastSunday) };
            }

            case 'this_month': {
                const start = new Date(tz.getFullYear(), tz.getMonth(), 1);
                return { from: ymd(start), to: today };
            }

            case 'last_month': {
                const start = new Date(tz.getFullYear(), tz.getMonth() - 1, 1);
                const end = new Date(tz.getFullYear(), tz.getMonth(), 0);
                return { from: ymd(start), to: ymd(end) };
            }

            default:
                return { from: today, to: today };
        }
    }

    // Active pill range
    var currentRange = 'today';

    /**
     * Format raw transaction time — delegates to i18n module.
     */
    function formatTime(raw) {
        return MP.formatTxTime ? MP.formatTxTime(raw) : (raw || '—');
    }

    /**
     * Mask an API key: show first 6 + last 3 chars.
     */
    function maskApiKey(key) {
        if (!key || key.length <= 10) return key || '';
        return key.substring(0, 6) + '•••' + key.substring(key.length - 3);
    }

    // ─── Pill Date Filter ───────────────────────────

    function initPillFilter() {
        var $pills = $('#mp-date-pills');
        var $customWrap = $('#mp-date-custom');
        if (!$pills.length) return;

        // Set initial dates
        var initial = getDateRange('today');
        $('#mp-date-from').val(initial.from);
        $('#mp-date-to').val(initial.to);

        // Pill click
        $pills.on('click', '.mp-date-pill', function () {
            var $pill = $(this);
            var range = $pill.data('range');

            // Toggle active
            $pills.find('.mp-date-pill').removeClass('mp-date-pill--active');
            $pill.addClass('mp-date-pill--active');

            if (range === 'custom') {
                $customWrap.slideDown(200);
                return;
            }

            $customWrap.slideUp(200);
            currentRange = range;

            var dates = getDateRange(range);
            $('#mp-date-from').val(dates.from);
            $('#mp-date-to').val(dates.to);
            loadBankDashboard();
        });

        // Apply custom range
        $('#mp-apply-custom').on('click', function () {
            currentRange = 'custom';
            loadBankDashboard();
        });
    }

    // ═══════════════════════════════════════════════════
    // Chart.js Cash Flow
    // ═══════════════════════════════════════════════════

    var cashFlowChart = null;

    /**
     * Extract hour (HH) from a transaction date/time string.
     * Handles "dd/mm/yyyy HH:MM:SS" and ISO formats.
     */
    function extractHour(raw) {
        if (!raw) return '00';
        // "dd/mm/yyyy HH:MM:SS" — time part after space
        var spaceParts = raw.split(' ');
        if (spaceParts.length >= 2) {
            var timeBits = spaceParts[1].split(':');
            return (timeBits[0] || '00').padStart(2, '0');
        }
        // ISO: try parsing with timezone
        try {
            var d = new Date(raw);
            if (!isNaN(d.getTime())) {
                var tz = MP.timezone || 'Asia/Ho_Chi_Minh';
                var parts = new Intl.DateTimeFormat('en-GB', {
                    timeZone: tz,
                    hour: '2-digit',
                    hour12: false,
                }).formatToParts(d);
                var hv = '00';
                parts.forEach(function (p) { if (p.type === 'hour') hv = p.value; });
                return hv.padStart(2, '0');
            }
        } catch (e) { /* ignore */ }
        return '00';
    }

    /**
     * Parse a raw date string (ISO or dd/mm/yyyy) into a normalized
     * dateKey "YYYY-MM-DD" for bucketing, using the configured timezone.
     */
    function normalizeDateKey(raw) {
        if (!raw) return '';
        // Already dd/mm/yyyy format from API
        if (/^\d{2}\/\d{2}\/\d{4}/.test(raw)) {
            var bits = raw.split(' ')[0].split('/');
            return bits[2] + '-' + bits[1] + '-' + bits[0];
        }
        // ISO timestamp
        try {
            var d = new Date(raw);
            if (!isNaN(d.getTime())) {
                var tz = MP.timezone || 'Asia/Ho_Chi_Minh';
                var parts = new Intl.DateTimeFormat('en-CA', {
                    timeZone: tz,
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                }).formatToParts(d);
                var y = '', m = '', dd = '';
                parts.forEach(function (p) {
                    if (p.type === 'year')  y  = p.value;
                    if (p.type === 'month') m  = p.value;
                    if (p.type === 'day')   dd = p.value;
                });
                return y + '-' + m + '-' + dd;
            }
        } catch (e) { /* ignore */ }
        return raw.split('T')[0] || raw.split(' ')[0] || raw;
    }

    /**
     * Format a dateKey (YYYY-MM-DD) into a short display label.
     * Returns "dd/mm" for multi-day or "dd/mm/yy" if needed.
     */
    function formatShortDate(dateKey) {
        if (!dateKey) return dateKey;
        // YYYY-MM-DD → dd/mm
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateKey)) {
            var p = dateKey.split('-');
            return p[2] + '/' + p[1];
        }
        // dd/mm/yyyy → dd/mm
        if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateKey)) {
            return dateKey.substring(0, 5);
        }
        return dateKey;
    }

    /**
     * Build or update the Chart.js mixed bar+line chart.
     * For single-day ranges: aggregates by HOUR (00h..23h).
     * For multi-day ranges: aggregates by DATE (dd/mm).
     * @param {Array} txs - raw transactions array
     */
    function updateChart(txs) {
        var canvas = document.getElementById('mp-cashflow-chart');
        if (!canvas || typeof Chart === 'undefined') return;

        // Swap skeleton → canvas
        $('#mp-chart-skeleton').hide();
        $(canvas).show();

        var singleDay = isSingleDayRange();
        var bucketMap = {};

        if (singleDay) {
            // Pre-fill all 24 hours
            for (var h = 0; h < 24; h++) {
                var hKey = String(h).padStart(2, '0');
                bucketMap[hKey] = { credit: 0, debit: 0 };
            }

            (txs || []).forEach(function (tx) {
                var rawDate = tx.transactionDate || tx.postDate || '';
                var hour = extractHour(rawDate);
                if (!bucketMap[hour]) bucketMap[hour] = { credit: 0, debit: 0 };
                bucketMap[hour].credit += parseFloat(tx.creditAmount || 0);
                bucketMap[hour].debit  += parseFloat(tx.debitAmount || 0);
            });
        } else {
            // Aggregate by date (normalize to YYYY-MM-DD via timezone)
            (txs || []).forEach(function (tx) {
                var rawDate = tx.transactionDate || tx.postDate || '';
                var dateKey = normalizeDateKey(rawDate);
                if (!dateKey) return;

                if (!bucketMap[dateKey]) bucketMap[dateKey] = { credit: 0, debit: 0 };
                bucketMap[dateKey].credit += parseFloat(tx.creditAmount || 0);
                bucketMap[dateKey].debit  += parseFloat(tx.debitAmount || 0);
            });
        }

        // Sort keys
        var labels = Object.keys(bucketMap).sort();
        var credits = labels.map(function (k) { return bucketMap[k].credit; });
        var debits  = labels.map(function (k) { return bucketMap[k].debit; });
        var net     = labels.map(function (k) { return bucketMap[k].credit - bucketMap[k].debit; });

        // Format short labels for X-axis
        var shortLabels;
        if (singleDay) {
            shortLabels = labels.map(function (h) { return h + ':00'; });
        } else {
            shortLabels = labels.map(function (d) {
                return formatShortDate(d);
            });
        }

        var chartData = {
            labels: shortLabels,
            datasets: [
                {
                    label: MP.__('credit'),
                    type: 'bar',
                    data: credits,
                    backgroundColor: 'rgba(16, 185, 129, 0.65)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    order: 2,
                },
                {
                    label: MP.__('debit'),
                    type: 'bar',
                    data: debits,
                    backgroundColor: 'rgba(239, 68, 68, 0.5)',
                    borderColor: 'rgba(239, 68, 68, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    order: 2,
                },
                {
                    label: MP.__('net_cashflow'),
                    type: 'line',
                    data: net,
                    borderColor: 'rgba(6, 182, 212, 1)',
                    backgroundColor: 'rgba(6, 182, 212, 0.08)',
                    borderWidth: 2,
                    pointRadius: singleDay ? 2 : 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgba(6, 182, 212, 1)',
                    pointBorderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    order: 1,
                },
            ],
        };

        var chartOpts = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'rectRounded',
                        padding: 16,
                        font: { size: 11, weight: '500' },
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { size: 12 },
                    bodyFont: { size: 12 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (ctx) {
                            var val = ctx.parsed.y || 0;
                            return ' ' + ctx.dataset.label + ': ' + MP.formatVND(Math.abs(val));
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 11 },
                        color: '#94a3b8',
                        maxRotation: singleDay ? 0 : 45,
                        autoSkip: true,
                        maxTicksLimit: singleDay ? 24 : 15,
                    },
                },
                y: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.04)',
                        drawBorder: false,
                    },
                    ticks: {
                        font: { size: 11 },
                        color: '#94a3b8',
                        callback: function (val) {
                            if (Math.abs(val) >= 1e9) return (val / 1e9).toFixed(1) + 'B';
                            if (Math.abs(val) >= 1e6) return (val / 1e6).toFixed(1) + 'M';
                            if (Math.abs(val) >= 1e3) return (val / 1e3).toFixed(0) + 'K';
                            return val;
                        },
                    },
                },
            },
        };

        if (cashFlowChart) {
            cashFlowChart.data = chartData;
            cashFlowChart.options = chartOpts;
            cashFlowChart.update('none');
        } else {
            cashFlowChart = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: chartData,
                options: chartOpts,
            });
        }
    }

    // ═══════════════════════════════════════════════════
    // Load Bank Dashboard
    // ═══════════════════════════════════════════════════

    async function loadBankDashboard() {
        const fromVal = $('#mp-date-from').val();
        const toVal = $('#mp-date-to').val();
        const from = toApiDate(fromVal);
        const to = toApiDate(toVal);
        const qs = from && to ? `?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}` : '';

        // Show loading — skeleton states
        $('#mp-refresh-data').addClass('loading');
        $('#mp-chart-skeleton').show();
        $('#mp-cashflow-chart').hide();
        $('#mp-tx-loading').show();
        $('#mp-tx-table').hide();
        $('#mp-tx-empty').hide();

        try {
            // Fetch summary (balance + stats)
            const summaryRes = await $.ajax({
                url: MP.restUrl + 'bank/summary' + qs,
                method: 'GET',
                headers: { 'X-WP-Nonce': MP.nonce },
            });

            if (summaryRes && summaryRes.success && summaryRes.data) {
                const d = summaryRes.data;

                // Populate stats
                $('#mp-stat-in').text(MP.formatVND(d.total_in));
                $('#mp-stat-out').text(MP.formatVND(d.total_out));
                $('#mp-stat-total').text(String(d.total_transactions || 0));
                $('#mp-stat-in-count').text((d.count_in || 0) + ' GD');
                $('#mp-stat-out-count').text((d.count_out || 0) + ' GD');

                // Populate card holder (bank account name)
                if (d.account_name) {
                    $('#mp-card-holder').text(d.account_name);
                }
            } else {
                $('#mp-card-holder').text('—');
            }
        } catch (err) {
            console.error('Bank summary error:', err);
            $('#mp-card-holder').text(err.responseJSON?.message || err.statusText || '');
        }

        // Fetch merchant info for Membership card display
        try {
            const usageRes = await $.ajax({
                url: MP.restUrl + 'usage',
                method: 'GET',
                headers: { 'X-WP-Nonce': MP.nonce },
            });

            if (usageRes && usageRes.success && usageRes.data) {
                const merchant = usageRes.data.merchant || {};
                const apiKey = merchant.api_key || '';
                const orgName = merchant.name || merchant.organization || merchant.org_name || '';
                const planName = merchant.plan || merchant.plan_name || '';
                const planExpiry = merchant.plan_expires || merchant.expires_at || merchant.plan_expiry || '';

                // Organization name
                if (orgName) {
                    $('#mp-card-org').text(orgName);
                } else {
                    $('#mp-card-org').text(MP.__('not_setup'));
                }

                // Plan badge
                if (planName) {
                    $('#mp-card-plan').text(planName.toUpperCase());
                } else {
                    $('#mp-card-plan').text('FREE');
                }

                // Plan expiry
                if (planExpiry) {
                    try {
                        const d = new Date(planExpiry);
                        if (!isNaN(d.getTime())) {
                            const dd = String(d.getDate()).padStart(2, '0');
                            const mm = String(d.getMonth() + 1).padStart(2, '0');
                            const yy = d.getFullYear();
                            $('#mp-card-expiry').text(dd + '/' + mm + '/' + yy);
                        } else {
                            $('#mp-card-expiry').text(planExpiry);
                        }
                    } catch (e) {
                        $('#mp-card-expiry').text(planExpiry);
                    }
                } else {
                    $('#mp-card-expiry').text(MP.__('forever'));
                }

                // API Key masked
                if (apiKey) {
                    const masked = apiKey.substring(0, 6) + ' •••• ' + apiKey.substring(apiKey.length - 4);
                    $('#mp-card-apikey').text(masked);
                } else {
                    $('#mp-card-apikey').text(MP.__('no_api_key'));
                }
            }
        } catch (err) {
            console.error('Usage fetch error:', err);
            $('#mp-card-org').text('N/A');
            $('#mp-card-plan').text('—');
            $('#mp-card-apikey').text('N/A');
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

                // Update chart with ALL transactions
                updateChart(txs);

                if (txs.length === 0) {
                    $('#mp-tx-table').hide();
                    $('#mp-tx-empty').show();
                    $('#mp-tx-view-all').hide();
                } else {
                    // Dashboard: max 5 recent
                    const MAX_DASHBOARD_ROWS = 5;
                    const displayTxs = txs.slice(0, MAX_DASHBOARD_ROWS);
                    renderTransactions(displayTxs);
                    $('#mp-tx-empty').hide();
                    $('#mp-tx-table').show();

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
                updateChart([]);
            }
        } catch (err) {
            console.error('Bank history error:', err);
            $('#mp-tx-loading').hide();
            $('#mp-tx-table').hide();
            $('#mp-tx-empty').show();
            updateChart([]);
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
            const rawTime = tx.transactionDate || tx.postDate || '';
            const time = formatTime(rawTime);
            const balance = tx.balanceAvailable;

            const tr = $('<tr>')
                .append($('<td>').addClass('mp-tx-col-time').html(`<span class="mp-tx-time">${MP.escHtml(time)}</span>`))
                .append($('<td>').addClass('mp-tx-col-desc').html(`<span class="mp-tx-desc" title="${MP.escHtml(desc)}">${MP.escHtml(desc)}</span>`))
                .append($('<td>').addClass('mp-tx-col-amount ' + amountClass).text(amountPrefix + MP.formatVND(amount)))
                .append($('<td>').addClass('mp-tx-col-balance').text(balance != null ? MP.formatVND(balance) : '—'));

            tbody.append(tr);
        });
    }

    // ─── Dashboard API Keys ─────────────────────────

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
                const activeKeys = (res.data || []).filter(function (k) {
                    return k.status === 'active';
                });

                if (activeKeys.length === 0) {
                    $container.html('<span class="mp-dash-pills__empty">Chưa có API key nào</span>');
                    return;
                }

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

                $container.off('click', '.mp-dash-pill__copy').on('click', '.mp-dash-pill__copy', function (e) {
                    e.stopPropagation();
                    var text = $(this).data('copy') || $(this).attr('data-copy') || '';
                    if (!text) return;
                    navigator.clipboard.writeText(text).then(function () {
                        MP.showToast('Đã sao chép key prefix', 'success');
                    });
                });

                if (activeKeys.length > 3) {
                    $container.append('<span class="mp-dash-pill mp-dash-pill--more">+' + (activeKeys.length - 3) + '</span>');
                }
            } else {
                $container.html('<span class="mp-dash-pills__empty">Chưa có API key nào</span>');
            }
        } catch (err) {
            console.warn('Dashboard API keys:', err.statusText || 'unavailable');
            $container.html('<span class="mp-dash-pills__empty">Chưa có API key nào</span>');
        }
    }

    // ─── Dashboard Connections ───────────────────────

    function loadDashboardConnections() {
        const $container = $('#mp-qa-conn-logos');
        if (!$container.length) return;

        const meta = window.mpPlatformMeta || {};
        const conns = window.mpConnections || [];
        var enabled = conns.filter(function (c) { return c.enabled; });

        $container.empty();

        if (enabled.length === 0) {
            $container.html('<span class="mp-dash-pills__empty">Chưa có kết nối nào</span>');
            return;
        }

        var seen = {};
        enabled.forEach(function (conn) {
            var platform = conn.platform || '';
            if (seen[platform]) return;
            seen[platform] = true;

            var pm = meta[platform] || {};
            var color = pm.color || '#6366f1';
            var name = pm.label || platform;
            var baseUrl = (MP.pluginUrl || '') + 'assets/img/platforms/';

            // Platform logo images (real PNGs)
            var logos = {
                lark: baseUrl + 'lark.png',
            };

            // SVG icon fallbacks for platforms without logo
            var icons = {
                telegram:      '<path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>',
                slack:         '<path d="M14.5 10c-.83 0-1.5-.67-1.5-1.5v-5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5v5c0 .83-.67 1.5-1.5 1.5zm6 0H19V8.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM9.5 14c.83 0 1.5.67 1.5 1.5v5c0 .83-.67 1.5-1.5 1.5S8 21.33 8 20.5v-5c0-.83.67-1.5 1.5-1.5zm-6 0H5v1.5C5 16.33 4.33 17 3.5 17S2 16.33 2 15.5 2.67 14 3.5 14z"/>',
                google_sheets: '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8M8 9h2"/>',
                discord:       '<circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><path d="M7.5 7.5c2-1 4.5-1 4.5-1s2.5 0 4.5 1M8 16.5s1 1.5 4 1.5 4-1.5 4-1.5"/>',
                webhook:       '<path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>',
            };

            var html;
            if (logos[platform]) {
                // Real logo image
                html = '<img src="' + MP.escHtml(logos[platform]) + '" alt="' + MP.escHtml(name) + '"' +
                    ' title="' + MP.escHtml(name) + '" class="mp-qa-conn-logo mp-qa-conn-logo--img" />';
            } else {
                // SVG icon fallback
                var svgContent = icons[platform] || icons.webhook;
                html = '<span class="mp-qa-conn-logo" title="' + MP.escHtml(name) + '" style="background:' + color + '">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                    svgContent + '</svg></span>';
            }
            $container.append($(html));
        });
    }

    // ─── Dashboard Payment Gateways ─────────────────

    function loadDashboardGateways() {
        var $container = $('#mp-qa-gateways-logos');
        if (!$container.length) return;

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
                    var masked = acct.length > 6
                        ? acct.substring(0, 3) + '***' + acct.substring(acct.length - 3)
                        : acct;

                    if (logo) {
                        var html = '<img src="' + MP.escHtml(logo) + '" alt="' + MP.escHtml(name) + '"' +
                            ' title="' + MP.escHtml(name + ' - ' + masked) + '"' +
                            ' class="mp-qa-bank-logo" />';
                        $container.append($(html));
                    } else {
                        var html = '<span class="mp-dash-logo-badge" title="' + MP.escHtml(name + ' - ' + masked) + '" style="--badge-color:#10b981">' +
                            '<span class="mp-dash-logo-badge__dot"></span>' +
                            '<span class="mp-dash-logo-badge__name">' + MP.escHtml(name) + '</span>' +
                            '</span>';
                        $container.append($(html));
                    }
                });
            },
            error: function () {
                $container.empty().html('<span class="mp-dash-pills__empty">Chưa có cổng thanh toán</span>');
            },
        });
    }

    // ─── Dashboard Create Key Modal ─────────────────

    function initDashboardCreateKey() {
        var $createModal = $('#mp-create-key-modal');
        var $showModal = $('#mp-show-key-modal');
        if (!$createModal.length) return;

        $('#mp-qa-create-key').on('click', function (e) {
            e.preventDefault();
            $('#mp-new-key-label').val('');
            MP.openModal($createModal);
        });

        $(document).on('click', '.mp-dash-modal-cancel, #mp-create-modal-close', function () {
            MP.closeModal($createModal);
            MP.closeModal($showModal);
        });

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
                    MP.closeModal($createModal);
                    $('#mp-new-key-value').text(fullKey);
                    $('#mp-copy-new-key').attr('data-copy', fullKey).data('copy', fullKey);
                    MP.openModal($showModal);
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

    function initDashboardCreateGateway() {
        var $gwModal = $('#mp-create-gateway-modal');
        if (!$gwModal.length) return;

        var $trigger = $('#mp-gw-bank-trigger');
        var $dropdown = $('#mp-gw-bank-dropdown');
        var $hiddenInput = $('#mp-gw-bank-code');
        var selectedBank = null;

        $trigger.on('click', function (e) {
            e.stopPropagation();
            var isOpen = $dropdown.hasClass('mp-bank-select__dropdown--open');
            $dropdown.toggleClass('mp-bank-select__dropdown--open', !isOpen);
            $trigger.toggleClass('mp-bank-select__trigger--open', !isOpen);
        });

        $dropdown.on('click', '.mp-bank-option', function () {
            var $opt = $(this);
            selectedBank = {
                code: $opt.data('code'),
                name: $opt.data('name'),
                logo: $opt.data('logo'),
            };
            $hiddenInput.val(selectedBank.code);
            $trigger.html(
                '<img src="' + MP.escHtml(selectedBank.logo) + '" alt="" class="mp-bank-select__selected-logo" />' +
                '<span class="mp-bank-select__selected-name">' + MP.escHtml(selectedBank.name) + '</span>' +
                '<svg class="mp-bank-select__chevron" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>'
            );
            $dropdown.find('.mp-bank-option').removeClass('mp-bank-option--active');
            $opt.addClass('mp-bank-option--active');
            $dropdown.removeClass('mp-bank-select__dropdown--open');
            $trigger.removeClass('mp-bank-select__trigger--open');
        });

        $(document).on('click', function () {
            $dropdown.removeClass('mp-bank-select__dropdown--open');
            $trigger.removeClass('mp-bank-select__trigger--open');
        });

        $gwModal.on('click', function (e) {
            if (!$(e.target).closest('.mp-bank-select').length) {
                $dropdown.removeClass('mp-bank-select__dropdown--open');
                $trigger.removeClass('mp-bank-select__trigger--open');
            }
        });

        $('#mp-qa-create-gateway').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
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

        $(document).on('click', '.mp-gw-modal-cancel, #mp-gateway-modal-close', function () {
            MP.closeModal($gwModal);
        });

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

    // ─── Connection Path Status ─────────────────────

    function setNodeStatus(nodeId, status) {
        var $status = $('#' + nodeId + '-status');
        var dotClass = 'mp-status-dot mp-status-dot--' + status;
        // Badge-only: just the dot, no text label
        $status.html('<span class="' + dotClass + '"></span>');
    }

    function setLineStatus(lineId, status) {
        var $line = $('#' + lineId);
        $line.removeClass('mp-flow-line--ok mp-flow-line--error');
        if (status) {
            $line.addClass('mp-flow-line--' + status);
        }
    }

    async function checkConnectionFlow() {
        setNodeStatus('mp-flow-bank', 'checking');
        setNodeStatus('mp-flow-server', 'checking');
        setNodeStatus('mp-flow-website', 'checking');
        setLineStatus('mp-flow-line-1', '');
        setLineStatus('mp-flow-line-2', '');

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

            if (result && result.success) {
                setNodeStatus('mp-flow-server', 'ok');
                setLineStatus('mp-flow-line-2', 'ok');

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

    // ─── Copy Webhook URL ───────────────────────────

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
                var $temp = $('<textarea>').val(text).appendTo('body').select();
                document.execCommand('copy');
                $temp.remove();
                showCopyFeedback($btn);
            }
        });
    }

    function showCopyFeedback($btn) {
        var originalHtml = $btn.html();
        $btn.html('<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>');
        $btn.css('color', 'var(--mp-success)');
        setTimeout(function () {
            $btn.html(originalHtml);
            $btn.css('color', '');
        }, 1500);
    }

    // ═══════════════════════════════════════════════════
    // Init
    // ═══════════════════════════════════════════════════

    $(document).ready(function () {
        // Apply i18n translations to DOM elements
        if (MP.applyI18n) MP.applyI18n();

        // Copy buttons (global)
        initCopyButtons();

        // Dashboard
        if ($('.mp-dashboard-layout').length) {
            // Pill date filter
            initPillFilter();

            // Bank data + auto-refresh
            loadBankDashboard();
            $('#mp-refresh-data').on('click', loadBankDashboard);

            // Auto-refresh every 30 seconds (only when visible)
            var autoRefreshTimer = setInterval(function () {
                if (!document.hidden) {
                    loadBankDashboard();
                }
            }, 30000);

            $(window).on('beforeunload', function () {
                clearInterval(autoRefreshTimer);
            });

            // Quick Action data
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
