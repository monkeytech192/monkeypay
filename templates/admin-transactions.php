<?php
/**
 * MonkeyPay Admin — Transaction History Page
 *
 * Full transaction history with date filter, search, pagination,
 * column toggle, flow filter, and CSV export.
 *
 * @package MonkeyPay
 * @since   4.2.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="monkeypay-admin-wrap">

    <!-- ═══════════════════════════════════════════════════
         PAGE HEADER
         ═══════════════════════════════════════════════════ -->
    <div class="mptx-header mp-fade-up">
        <div class="mptx-header__left">
            <h2 class="mptx-header__title">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
                <?php esc_html_e( 'Lịch sử giao dịch', 'monkeypay' ); ?>
            </h2>
        </div>
        <div class="mptx-header__actions">
            <button type="button" class="mptx-btn-export" id="mptx-export" title="Xuất CSV">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Xuất CSV
            </button>
            <button type="button" class="mptx-col-settings__btn" id="mptx-col-settings-btn" title="Cài đặt cột">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                </svg>
                Cột
            </button>
            <button type="button" class="mptx-btn-refresh" id="mptx-refresh" title="Làm mới">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         SUMMARY STATS
         ═══════════════════════════════════════════════════ -->
    <div class="mptx-summary mp-fade-up">
        <div class="mptx-summary__card">
            <div class="mptx-summary__label">Tổng giao dịch</div>
            <div class="mptx-summary__value" id="mptx-stat-total">—</div>
        </div>
        <div class="mptx-summary__card">
            <div class="mptx-summary__label">Tiền vào</div>
            <div class="mptx-summary__value mptx-summary__value--in" id="mptx-stat-in">—</div>
        </div>
        <div class="mptx-summary__card">
            <div class="mptx-summary__label">Tiền ra</div>
            <div class="mptx-summary__value mptx-summary__value--out" id="mptx-stat-out">—</div>
        </div>
        <div class="mptx-summary__card">
            <div class="mptx-summary__label">Số dư hiện tại</div>
            <div class="mptx-summary__value" id="mptx-stat-balance">—</div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         FILTERS
         ═══════════════════════════════════════════════════ -->
    <div class="mptx-filters mp-fade-up">
        <div class="mptx-filters__row">
            <div class="mptx-date-pills" id="mptx-date-pills">
                <button type="button" class="mptx-date-pill mptx-date-pill--active" data-range="today">Hôm nay</button>
                <button type="button" class="mptx-date-pill" data-range="yesterday">Hôm qua</button>
                <button type="button" class="mptx-date-pill" data-range="7days">7 ngày</button>
                <button type="button" class="mptx-date-pill" data-range="30days">30 ngày</button>
                <button type="button" class="mptx-date-pill" data-range="this_week">Tuần này</button>
                <button type="button" class="mptx-date-pill" data-range="last_week">Tuần trước</button>
                <button type="button" class="mptx-date-pill" data-range="this_month">Tháng này</button>
                <button type="button" class="mptx-date-pill" data-range="last_month">Tháng trước</button>
                <button type="button" class="mptx-date-pill" data-range="custom">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:3px">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Tùy chọn
                </button>
            </div>
        </div>
        <div class="mptx-date-custom" id="mptx-date-custom">
            <input type="date" id="mptx-date-from" class="mptx-date-input" />
            <span class="mptx-date-sep">→</span>
            <input type="date" id="mptx-date-to" class="mptx-date-input" />
            <button type="button" class="monkeypay-btn monkeypay-btn--sm monkeypay-btn--primary" id="mptx-apply-custom">Áp dụng</button>
        </div>
        <div class="mptx-filters__row">
            <div class="mptx-flow-pills" id="mptx-flow-pills">
                <button type="button" class="mptx-flow-pill mptx-flow-pill--active" data-flow="all">Tất cả</button>
                <button type="button" class="mptx-flow-pill" data-flow="in">Tiền vào</button>
                <button type="button" class="mptx-flow-pill" data-flow="out">Tiền ra</button>
            </div>
            <div class="mptx-search-wrap">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" class="mptx-search" id="mptx-search" placeholder="Tìm kiếm nội dung, số tiền..." />
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         TABLE
         ═══════════════════════════════════════════════════ -->
    <div class="mptx-table-wrap mp-fade-up" id="mptx-table-wrap">

        <!-- Loading -->
        <div class="mptx-loading" id="mptx-loading">
            <div class="mp-spinner"></div>
            <span>Đang tải giao dịch...</span>
        </div>

        <!-- Empty -->
        <div class="mptx-empty" id="mptx-empty">
            <svg viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
            </svg>
            <span>Không có giao dịch nào trong khoảng thời gian này</span>
        </div>

        <!-- Desktop Table -->
        <table class="mptx-table" id="mptx-table" style="display:none">
            <thead>
                <tr>
                    <th class="mptx-col-time" data-col="time">Thời gian</th>
                    <th class="mptx-col-desc" data-col="desc">Mô tả</th>
                    <th class="mptx-col-amount" data-col="amount">Số tiền</th>
                    <th class="mptx-col-type" data-col="type">Loại</th>
                    <th class="mptx-col-balance" data-col="balance">Số dư</th>
                    <th class="mptx-col-ref" data-col="ref">Mã tham chiếu</th>
                </tr>
            </thead>
            <tbody id="mptx-tbody"></tbody>
        </table>

        <!-- Mobile Card List -->
        <div class="mptx-mobile-list" id="mptx-mobile-list"></div>

        <!-- Pagination -->
        <div class="mptx-pagination" id="mptx-pagination" style="display:none">
            <span class="mptx-pagination__info" id="mptx-page-info"></span>
            <div class="mptx-pagination__btns" id="mptx-page-btns"></div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════
     COLUMN SETTINGS MODAL
     ═══════════════════════════════════════════════════ -->
<div class="mptx-col-modal-overlay" id="mptx-col-modal">
    <div class="mptx-col-modal">
        <div class="mptx-col-modal__header">
            <h3 class="mptx-col-modal__title">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
                </svg>
                Cài đặt hiển thị cột
            </h3>
            <button type="button" class="mptx-col-modal__close" id="mptx-col-modal-close">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="mptx-col-modal__body">
            <p class="mptx-col-modal__desc">Bật/tắt các cột bạn muốn hiển thị trong bảng giao dịch.</p>
            <div id="mptx-col-toggles">
                <!-- Populated by JS -->
            </div>
        </div>
    </div>
</div>
