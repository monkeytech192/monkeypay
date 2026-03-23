/**
 * MonkeyPay Admin — Transaction History Module
 *
 * Handles: date filter, flow filter, search, pagination,
 *          column toggle, CSV export, desktop table + mobile cards.
 *
 * @package MonkeyPay
 * @since   4.2.0
 */
(function ($) {
    'use strict';

    /* ── State ────────────────────────────────────────── */
    const STATE = {
        range: 'today',
        dateFrom: '',
        dateTo: '',
        flow: 'all',       // all | in | out
        search: '',
        page: 1,
        perPage: 20,
        totalPages: 1,
        totalRows: 0,
        data: [],           // current page rows
        allData: [],        // full result set (for client-side filter)
        columns: {
            time: true,
            desc: true,
            amount: true,
            type: true,
            balance: true,
            ref: true,
            reconcile: true,
        },
        reconcileMap: {},   // index → reconcile result
    };

    const COL_LABELS = {
        time: 'Thời gian',
        desc: 'Mô tả',
        amount: 'Số tiền',
        type: 'Loại',
        balance: 'Số dư',
        ref: 'Mã tham chiếu',
        reconcile: 'Đối soát',
    };

    /* ── DOM cache ────────────────────────────────────── */
    let $loading, $empty, $table, $tbody, $mobileList, $pagination, $pageInfo, $pageBtns;
    let $statTotal, $statIn, $statOut, $statBalance;
    let searchTimer = null;

    /* ── Init ─────────────────────────────────────────── */
    $(function () {
        $loading    = $('#mptx-loading');
        $empty      = $('#mptx-empty');
        $table      = $('#mptx-table');
        $tbody      = $('#mptx-tbody');
        $mobileList = $('#mptx-mobile-list');
        $pagination = $('#mptx-pagination');
        $pageInfo   = $('#mptx-page-info');
        $pageBtns   = $('#mptx-page-btns');
        $statTotal  = $('#mptx-stat-total');
        $statIn     = $('#mptx-stat-in');
        $statOut    = $('#mptx-stat-out');
        $statBalance = $('#mptx-stat-balance');

        // Restore column prefs from localStorage
        try {
            const saved = localStorage.getItem('mptx_columns');
            if (saved) Object.assign(STATE.columns, JSON.parse(saved));
        } catch (e) { /* ignore */ }

        bindEvents();
        fetchTransactions();
    });

    /* ── Event Binding ────────────────────────────────── */
    function bindEvents() {
        // Date pills
        $('#mptx-date-pills').on('click', '.mptx-date-pill', function () {
            const range = $(this).data('range');
            $('#mptx-date-pills .mptx-date-pill').removeClass('mptx-date-pill--active');
            $(this).addClass('mptx-date-pill--active');

            if (range === 'custom') {
                $('#mptx-date-custom').addClass('active');
                return;
            }
            $('#mptx-date-custom').removeClass('active');
            STATE.range = range;
            STATE.dateFrom = '';
            STATE.dateTo = '';
            STATE.page = 1;
            fetchTransactions();
        });

        // Custom date apply
        $('#mptx-apply-custom').on('click', function () {
            STATE.dateFrom = $('#mptx-date-from').val();
            STATE.dateTo   = $('#mptx-date-to').val();
            if (!STATE.dateFrom || !STATE.dateTo) return;
            STATE.range = 'custom';
            STATE.page = 1;
            fetchTransactions();
        });

        // Flow pills
        $('#mptx-flow-pills').on('click', '.mptx-flow-pill', function () {
            const flow = $(this).data('flow');
            $('#mptx-flow-pills .mptx-flow-pill').removeClass('mptx-flow-pill--active');
            $(this).addClass('mptx-flow-pill--active');
            STATE.flow = flow;
            STATE.page = 1;
            applyClientFilters();
        });

        // Search (debounced)
        $('#mptx-search').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                STATE.search = $(this).val().trim().toLowerCase();
                STATE.page = 1;
                applyClientFilters();
            }, 300);
        });

        // Refresh
        $('#mptx-refresh').on('click', function () {
            const $btn = $(this);
            $btn.addClass('loading');
            fetchTransactions().finally(() => $btn.removeClass('loading'));
        });

        // Export CSV
        $('#mptx-export').on('click', exportCSV);

        // Column settings modal
        $('#mptx-col-settings-btn').on('click', openColumnModal);
        $('#mptx-col-modal-close').on('click', closeColumnModal);
        $('#mptx-col-modal').on('click', function (e) {
            if (e.target === this) closeColumnModal();
        });

        // Pagination
        $pageBtns.on('click', '.mptx-pagination__btn', function () {
            const p = $(this).data('page');
            if (p && p !== STATE.page) {
                STATE.page = p;
                renderPage();
            }
        });
    }

    /* ── API Fetch ────────────────────────────────────── */
    function fetchTransactions() {
        showLoading();

        const { dateFrom, dateTo } = getDateRange(STATE.range, STATE.dateFrom, STATE.dateTo);
        // Convert yyyy-mm-dd → DD/MM/YYYY for bank API
        const from = toApiDate(dateFrom);
        const to   = toApiDate(dateTo);
        const qs   = from && to ? '?from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to) : '';

        const bankUrl = monkeypayAdmin.restUrl + 'bank/history' + qs;
        const bdsdUrl = monkeypayAdmin.restUrl + 'bdsd-transactions' + qs;

        // Fetch bank history and BDSD data in parallel
        return $.when(
            $.ajax({
                url: bankUrl,
                headers: { 'X-WP-Nonce': monkeypayAdmin.nonce },
                dataType: 'json',
            }),
            $.ajax({
                url: bdsdUrl,
                headers: { 'X-WP-Nonce': monkeypayAdmin.nonce },
                dataType: 'json',
            }).catch(function () {
                // BDSD endpoint may not exist yet (pre-migration) — graceful fallback
                return [{ success: true, data: { transactions: [] } }];
            })
        )
        .done(function (bankResp, bdsdResp) {
            // $.when wraps each response in [data, textStatus, jqXHR]
            const bankData = Array.isArray(bankResp) ? bankResp[0] : bankResp;
            const bdsdData = Array.isArray(bdsdResp) ? bdsdResp[0] : bdsdResp;

            if (bankData && bankData.success && bankData.data) {
                const rawTxs = bankData.data.transactions || [];
                const bdsdTxs = (bdsdData && bdsdData.success && bdsdData.data)
                    ? (bdsdData.data.transactions || [])
                    : [];

                // Normalize bank transactions and merge BDSD IDs
                const txList = rawTxs.map(function (tx) {
                    const normalized = normalizeTx(tx);
                    // Try to match with BDSD record by amount + description similarity
                    const matched = findBdsdMatch(normalized, bdsdTxs);
                    if (matched) {
                        normalized.reference_number = matched.tx_id || normalized.reference_number;
                        normalized.bdsd_id = matched.bdsd_id || '';
                    }
                    return normalized;
                });

                STATE.allData = txList;
                updateSummary(txList);

                // Batch reconcile with checkin invoices
                fetchReconcile(txList).then(function () {
                    applyClientFilters();
                });
            } else {
                STATE.allData = [];
                STATE.data = [];
                showEmpty();
            }
        })
        .fail(function () {
            STATE.allData = [];
            STATE.data = [];
            showEmpty();
        });
    }

    /**
     * Find matching BDSD transaction for a bank transaction.
     * Matches by: amount (exact) + description (substring match) + close timestamp.
     *
     * @param {Object} bankTx Normalized bank transaction
     * @param {Array}  bdsdTxs BDSD transaction records
     * @return {Object|null} Matched BDSD record or null
     */
    function findBdsdMatch(bankTx, bdsdTxs) {
        if (!bdsdTxs || !bdsdTxs.length) return null;

        const bankAmt  = Math.abs(parseFloat(bankTx.amount) || 0);
        const bankDesc = (bankTx.description || '').toLowerCase().trim();
        const bankTime = bankTx.transaction_date ? new Date(bankTx.transaction_date).getTime() : 0;

        for (let i = 0; i < bdsdTxs.length; i++) {
            const bdsd = bdsdTxs[i];
            const bdsdAmt  = Math.abs(parseFloat(bdsd.amount) || 0);
            const bdsdDesc = (bdsd.description || '').toLowerCase().trim();
            const bdsdTime = bdsd.transaction_date ? new Date(bdsd.transaction_date).getTime() : 0;

            // Amount must match exactly
            if (bankAmt !== bdsdAmt) continue;

            // Description must have significant overlap (substring match)
            const descMatch = bankDesc && bdsdDesc && (
                bankDesc.includes(bdsdDesc) || bdsdDesc.includes(bankDesc)
            );

            // Time within 5 minutes tolerance
            const timeClose = bankTime && bdsdTime
                ? Math.abs(bankTime - bdsdTime) < 5 * 60 * 1000
                : true; // If no time data, don't filter by time

            if (descMatch && timeClose) {
                // Remove from array to prevent double-matching
                bdsdTxs.splice(i, 1);
                return bdsd;
            }
        }

        return null;
    }

    /**
     * Batch reconcile: send descriptions+amounts to backend,
     * receive match status for each transaction.
     */
    function fetchReconcile(txList) {
        // Only reconcile credit (incoming) transactions
        const payload = txList.map(function (tx) {
            return {
                description: tx.description || '',
                amount: tx.amount || 0,
            };
        });

        return $.ajax({
            url: monkeypayAdmin.restUrl + 'reconcile',
            method: 'POST',
            headers: { 'X-WP-Nonce': monkeypayAdmin.nonce },
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
        }).then(function (resp) {
            if (resp && resp.success && resp.data) {
                STATE.reconcileMap = {};
                resp.data.forEach(function (r, i) {
                    STATE.reconcileMap[i] = r;
                });
            }
        }).catch(function () {
            // Graceful fallback if endpoint not available
            STATE.reconcileMap = {};
        });
    }

    /**
     * Render reconcile badge HTML.
     */
    function renderReconcileBadge(globalIdx) {
        const r = STATE.reconcileMap[globalIdx];
        if (!r || r.status === 'na') {
            return '<span class="mptx-reconcile-badge mptx-reconcile--na">—</span>';
        }
        if (r.status === 'matched') {
            return '<span class="mptx-reconcile-badge mptx-reconcile--matched" title="' + escHtml(r.invoice_id) + ' • Đã thanh toán">✓ Đã khớp</span>';
        }
        if (r.status === 'amount_ok') {
            return '<span class="mptx-reconcile-badge mptx-reconcile--pending" title="' + escHtml(r.invoice_id) + ' • Chờ xác nhận">' + escHtml(r.invoice_id) + '</span>';
        }
        if (r.status === 'mismatch') {
            return '<span class="mptx-reconcile-badge mptx-reconcile--mismatch" title="Số tiền không khớp: HĐ ' + (r.expected || 0).toLocaleString('vi-VN') + '₫ / Bank ' + (r.actual || 0).toLocaleString('vi-VN') + '₫">⚠ Sai tiền</span>';
        }
        if (r.status === 'not_found') {
            return '<span class="mptx-reconcile-badge mptx-reconcile--notfound" title="Mã ' + escHtml(r.invoice_id) + ' không tìm thấy trong hệ thống">✗ Không tìm thấy</span>';
        }
        return '<span class="mptx-reconcile-badge mptx-reconcile--na">—</span>';
    }

    /**
     * Render BDSD ID with tooltip explaining ID gaps.
     */
    function renderBdsdRef(tx) {
        const ref = tx.reference_number || tx.ref || '—';
        const bdsdId = tx.bdsd_id || '';

        if (bdsdId) {
            return '<span class="mptx-ref mptx-bdsd-ref" title="BDSD ID được gán tự tăng trên Cloud Run, dùng chung cho nhiều tài khoản. Nhảy số là bình thường.">' + escHtml(bdsdId) + '</span>';
        }
        return '<span class="mptx-ref">' + escHtml(ref) + '</span>';
    }

    /**
     * Convert yyyy-mm-dd → DD/MM/YYYY for bank API.
     */
    function toApiDate(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    /**
     * Normalize bank API transaction fields to internal format.
     */
    function normalizeTx(tx) {
        const credit = parseFloat(tx.creditAmount || 0);
        const debit  = parseFloat(tx.debitAmount || 0);
        const amount = credit > 0 ? credit : -debit;
        return {
            amount: amount,
            description: tx.transactionDesc || tx.description || '',
            transaction_date: tx.transactionDate || tx.postDate || '',
            balance: tx.balanceAvailable != null ? tx.balanceAvailable : (tx.balance || 0),
            reference_number: tx.refNo || tx.reference || tx.reference_number || tx.ref || '',
        };
    }

    /* ── Date Range Helper ────────────────────────────── */
    function getDateRange(range, customFrom, customTo) {
        const now = new Date();
        const today = fmtDate(now);
        let dateFrom = today;
        let dateTo   = today;

        switch (range) {
            case 'today':
                break;
            case 'yesterday': {
                const y = new Date(now);
                y.setDate(y.getDate() - 1);
                dateFrom = dateTo = fmtDate(y);
                break;
            }
            case '7days': {
                const d = new Date(now);
                d.setDate(d.getDate() - 6);
                dateFrom = fmtDate(d);
                break;
            }
            case '30days': {
                const d = new Date(now);
                d.setDate(d.getDate() - 29);
                dateFrom = fmtDate(d);
                break;
            }
            case 'this_week': {
                const d = new Date(now);
                const dayOfWeek = d.getDay() || 7;
                d.setDate(d.getDate() - dayOfWeek + 1);
                dateFrom = fmtDate(d);
                break;
            }
            case 'last_week': {
                const d = new Date(now);
                const dayOfWeek = d.getDay() || 7;
                d.setDate(d.getDate() - dayOfWeek - 6);
                dateFrom = fmtDate(d);
                const e = new Date(d);
                e.setDate(e.getDate() + 6);
                dateTo = fmtDate(e);
                break;
            }
            case 'this_month':
                dateFrom = fmtDate(new Date(now.getFullYear(), now.getMonth(), 1));
                break;
            case 'last_month': {
                const first = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                const last  = new Date(now.getFullYear(), now.getMonth(), 0);
                dateFrom = fmtDate(first);
                dateTo   = fmtDate(last);
                break;
            }
            case 'custom':
                dateFrom = customFrom || today;
                dateTo   = customTo || today;
                break;
        }
        return { dateFrom, dateTo };
    }

    function fmtDate(d) {
        return d.toISOString().slice(0, 10);
    }

    /* ── Client-Side Filters (flow, search) ──────────── */
    function applyClientFilters() {
        let filtered = STATE.allData.slice();

        // Flow filter
        if (STATE.flow === 'in') {
            filtered = filtered.filter(tx => parseFloat(tx.amount) > 0);
        } else if (STATE.flow === 'out') {
            filtered = filtered.filter(tx => parseFloat(tx.amount) < 0);
        }

        // Search
        if (STATE.search) {
            const q = STATE.search;
            filtered = filtered.filter(tx => {
                const desc = (tx.description || '').toLowerCase();
                const ref  = (tx.reference_number || '').toLowerCase();
                const amt  = String(tx.amount || '');
                return desc.includes(q) || ref.includes(q) || amt.includes(q);
            });
        }

        // Paginate
        STATE.totalRows  = filtered.length;
        STATE.totalPages = Math.max(1, Math.ceil(filtered.length / STATE.perPage));
        if (STATE.page > STATE.totalPages) STATE.page = STATE.totalPages;

        const start = (STATE.page - 1) * STATE.perPage;
        STATE.data = filtered.slice(start, start + STATE.perPage);

        renderPage();
    }

    /* ── Summary Stats ────────────────────────────────── */
    function updateSummary(txList) {
        let totalIn = 0, totalOut = 0;
        txList.forEach(tx => {
            const amt = parseFloat(tx.amount) || 0;
            if (amt > 0) totalIn += amt;
            else totalOut += Math.abs(amt);
        });

        $statTotal.text(txList.length.toLocaleString('vi-VN'));
        $statIn.text('+' + formatMoney(totalIn));
        $statOut.text('-' + formatMoney(totalOut));

        // Balance from latest tx
        const lastTx = txList[0];
        const balance = lastTx ? (parseFloat(lastTx.balance) || 0) : 0;
        $statBalance.text(formatMoney(balance));
    }

    /* ── Render Page ──────────────────────────────────── */
    function renderPage() {
        if (!STATE.data.length) {
            showEmpty();
            return;
        }

        renderDesktopTable();
        renderMobileCards();
        renderPagination();

        $loading.hide();
        $empty.hide();
        $table.show();
        $pagination.show();
    }

    /* ── Desktop Table Render ─────────────────────────── */
    function renderDesktopTable() {
        // Update header visibility
        $table.find('th').each(function () {
            const col = $(this).data('col');
            $(this).toggle(!!STATE.columns[col]);
        });

        // Calculate global start index for reconcile map lookup
        const globalStart = (STATE.page - 1) * STATE.perPage;

        let html = '';
        STATE.data.forEach((tx, localIdx) => {
            const amt    = parseFloat(tx.amount) || 0;
            const isIn   = amt > 0;
            const desc   = tx.description || tx.content || '—';
            const time   = formatTime(tx.transaction_date || tx.created_at || tx.date);
            const bal    = parseFloat(tx.balance) || 0;

            // Find global index in allData for reconcile lookup
            const globalIdx = STATE.allData.indexOf(tx);

            html += '<tr>';
            if (STATE.columns.time)      html += `<td class="mptx-col-time">${time}</td>`;
            if (STATE.columns.desc)      html += `<td class="mptx-col-desc">${escHtml(desc)}</td>`;
            if (STATE.columns.amount)    html += `<td class="mptx-col-amount ${isIn ? 'mptx-amount--in' : 'mptx-amount--out'}">${isIn ? '+' : ''}${formatMoney(amt)}</td>`;
            if (STATE.columns.type)      html += `<td class="mptx-col-type"><span class="mptx-type-badge mptx-type-badge--${isIn ? 'in' : 'out'}">${isIn ? 'Vào' : 'Ra'}</span></td>`;
            if (STATE.columns.balance)   html += `<td class="mptx-col-balance">${formatMoney(bal)}</td>`;
            if (STATE.columns.ref)       html += `<td class="mptx-col-ref">${renderBdsdRef(tx)}</td>`;
            if (STATE.columns.reconcile) html += `<td class="mptx-col-reconcile">${renderReconcileBadge(globalIdx)}</td>`;
            html += '</tr>';
        });

        $tbody.html(html);
    }

    /* ── Mobile Cards Render ──────────────────────────── */
    function renderMobileCards() {
        let html = '';
        STATE.data.forEach(tx => {
            const amt  = parseFloat(tx.amount) || 0;
            const isIn = amt > 0;
            const desc = tx.description || tx.content || '—';
            const time = formatTime(tx.transaction_date || tx.created_at || tx.date);
            const bal  = parseFloat(tx.balance) || 0;

            const arrowSvg = isIn
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 11 12 6 7 11"/><line x1="12" y1="6" x2="12" y2="18"/></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="7 13 12 18 17 13"/><line x1="12" y1="18" x2="12" y2="6"/></svg>';

            const globalIdx = STATE.allData.indexOf(tx);
            const reconcileHtml = renderReconcileBadge(globalIdx);
            const bdsdHtml = tx.bdsd_id ? `<span class="mptx-mobile-card__bdsd">${escHtml(tx.bdsd_id)}</span>` : '';

            html += `
                <div class="mptx-mobile-card">
                    <div class="mptx-mobile-card__icon mptx-mobile-card__icon--${isIn ? 'in' : 'out'}">
                        ${arrowSvg}
                    </div>
                    <div class="mptx-mobile-card__body">
                        <div class="mptx-mobile-card__desc">${escHtml(desc)}</div>
                        <div class="mptx-mobile-card__meta">${time} ${bdsdHtml}</div>
                    </div>
                    <div class="mptx-mobile-card__amount">
                        <div class="mptx-mobile-card__value ${isIn ? 'mptx-amount--in' : 'mptx-amount--out'}">${isIn ? '+' : ''}${formatMoney(amt)}</div>
                        <div class="mptx-mobile-card__balance">Dư: ${formatMoney(bal)}</div>
                        <div class="mptx-mobile-card__reconcile">${reconcileHtml}</div>
                    </div>
                </div>`;
        });

        $mobileList.html(html);
    }

    /* ── Pagination Render ────────────────────────────── */
    function renderPagination() {
        const { page, totalPages, totalRows, perPage } = STATE;
        const start = (page - 1) * perPage + 1;
        const end   = Math.min(page * perPage, totalRows);

        $pageInfo.text(`${start}–${end} / ${totalRows} giao dịch`);

        let btns = '';
        btns += `<button class="mptx-pagination__btn" data-page="${page - 1}" ${page <= 1 ? 'disabled' : ''}>&lsaquo;</button>`;

        // Show max 7 page buttons
        const maxBtns = 7;
        let startPage = Math.max(1, page - Math.floor(maxBtns / 2));
        let endPage   = Math.min(totalPages, startPage + maxBtns - 1);
        if (endPage - startPage < maxBtns - 1) startPage = Math.max(1, endPage - maxBtns + 1);

        for (let i = startPage; i <= endPage; i++) {
            btns += `<button class="mptx-pagination__btn ${i === page ? 'mptx-pagination__btn--active' : ''}" data-page="${i}">${i}</button>`;
        }

        btns += `<button class="mptx-pagination__btn" data-page="${page + 1}" ${page >= totalPages ? 'disabled' : ''}>&rsaquo;</button>`;
        $pageBtns.html(btns);
    }

    /* ── Column Settings Modal ────────────────────────── */
    function openColumnModal() {
        const $container = $('#mptx-col-toggles');
        let html = '';
        Object.entries(STATE.columns).forEach(([key, on]) => {
            html += `
                <div class="mptx-col-settings__item" data-col="${key}">
                    <span class="mptx-col-settings__item-label">${COL_LABELS[key] || key}</span>
                    <div class="mptx-toggle ${on ? 'active' : ''}"></div>
                </div>`;
        });
        $container.html(html);

        // Toggle click
        $container.off('click', '.mptx-col-settings__item').on('click', '.mptx-col-settings__item', function () {
            const col = $(this).data('col');
            STATE.columns[col] = !STATE.columns[col];
            $(this).find('.mptx-toggle').toggleClass('active', STATE.columns[col]);
            localStorage.setItem('mptx_columns', JSON.stringify(STATE.columns));
            renderDesktopTable();
        });

        $('#mptx-col-modal').addClass('active');
    }

    function closeColumnModal() {
        $('#mptx-col-modal').removeClass('active');
    }

    /* ── CSV Export ────────────────────────────────────── */
    function exportCSV() {
        if (!STATE.allData.length) return;

        const headers = ['Thời gian', 'Mô tả', 'Số tiền', 'Loại', 'Số dư', 'Mã tham chiếu', 'Đối soát'];
        const rows = STATE.allData.map((tx, i) => {
            const amt = parseFloat(tx.amount) || 0;
            const r = STATE.reconcileMap[i];
            let reconcileText = '—';
            if (r) {
                if (r.status === 'matched') reconcileText = 'Đã khớp (' + (r.invoice_id || '') + ')';
                else if (r.status === 'amount_ok') reconcileText = r.invoice_id || 'Chờ xác nhận';
                else if (r.status === 'mismatch') reconcileText = 'Sai tiền';
                else if (r.status === 'not_found') reconcileText = 'Không tìm thấy';
            }
            return [
                tx.transaction_date || tx.created_at || tx.date || '',
                (tx.description || tx.content || '').replace(/"/g, '""'),
                amt,
                amt > 0 ? 'Vào' : 'Ra',
                tx.balance || 0,
                tx.bdsd_id || tx.reference_number || tx.ref || '',
                reconcileText,
            ];
        });

        let csv = '\uFEFF'; // BOM for Excel UTF-8
        csv += headers.join(',') + '\n';
        rows.forEach(row => {
            csv += row.map(c => `"${c}"`).join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `monkeypay-transactions-${fmtDate(new Date())}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }

    /* ── UI Helpers ───────────────────────────────────── */
    function showLoading() {
        $loading.show();
        $empty.hide();
        $table.hide();
        $pagination.hide();
    }

    function showEmpty() {
        $loading.hide();
        $empty.css('display', 'flex');
        $table.hide();
        $pagination.hide();
    }

    function formatMoney(n) {
        return Math.abs(n).toLocaleString('vi-VN') + ' ₫';
    }

    function formatTime(raw) {
        if (!raw) return '—';
        const d = new Date(raw);
        if (isNaN(d.getTime())) return raw;
        const pad = v => String(v).padStart(2, '0');
        return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

})(jQuery);
