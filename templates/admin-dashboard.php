<?php
/**
 * MonkeyPay Dashboard Page
 *
 * Bank balance overview, transaction stats, recent transactions
 *
 * @package MonkeyPay
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap monkeypay-admin-wrap">
    <?php include MONKEYPAY_PLUGIN_DIR . 'templates/partials/global-header.php'; ?>

    <div class="monkeypay-admin-page">

        <!-- ═══ Balance Overview Card ═══ -->
        <div class="mp-dashboard-hero" id="mp-dashboard-hero">
            <div class="mp-hero-balance">
                <div class="mp-hero-balance__label" id="mp-balance-label">
                    <svg viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <?php esc_html_e( 'Tài khoản nhận', 'monkeypay' ); ?>
                </div>
                <div class="mp-hero-balance__amount" id="mp-balance-amount">
                    <span class="mp-balance-skeleton"></span>
                </div>
                <div class="mp-hero-balance__account" id="mp-balance-account">—</div>
            </div>
            <div class="mp-hero-stats">
                <div class="mp-hero-stat mp-hero-stat--in">
                    <div class="mp-hero-stat__icon">
                        <svg viewBox="0 0 24 24"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>
                    </div>
                    <div class="mp-hero-stat__info">
                        <span class="mp-hero-stat__label"><?php esc_html_e( 'Tiền vào', 'monkeypay' ); ?></span>
                        <span class="mp-hero-stat__value" id="mp-stat-in"><span class="mp-balance-skeleton mp-balance-skeleton--sm"></span></span>
                    </div>
                    <span class="mp-hero-stat__count" id="mp-stat-in-count">0 GD</span>
                </div>
                <div class="mp-hero-stat mp-hero-stat--out">
                    <div class="mp-hero-stat__icon">
                        <svg viewBox="0 0 24 24"><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                    </div>
                    <div class="mp-hero-stat__info">
                        <span class="mp-hero-stat__label"><?php esc_html_e( 'Tiền ra', 'monkeypay' ); ?></span>
                        <span class="mp-hero-stat__value" id="mp-stat-out"><span class="mp-balance-skeleton mp-balance-skeleton--sm"></span></span>
                    </div>
                    <span class="mp-hero-stat__count" id="mp-stat-out-count">0 GD</span>
                </div>
                <div class="mp-hero-stat mp-hero-stat--total">
                    <div class="mp-hero-stat__icon">
                        <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    </div>
                    <div class="mp-hero-stat__info">
                        <span class="mp-hero-stat__label"><?php esc_html_e( 'Tổng GD', 'monkeypay' ); ?></span>
                        <span class="mp-hero-stat__value" id="mp-stat-total">—</span>
                    </div>
                    <span class="mp-hero-stat__count" id="mp-stat-period"><?php esc_html_e( 'Hôm nay', 'monkeypay' ); ?></span>
                </div>
            </div>
        </div>

        <!-- ═══ Transaction History Card ═══ -->
        <div class="monkeypay-card mp-transactions-card">
            <div class="monkeypay-card__header">
                <div class="monkeypay-card__header-left">
                    <div class="monkeypay-card__icon">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <h2><?php esc_html_e( 'Lịch Sử Giao Dịch', 'monkeypay' ); ?></h2>
                </div>
                <div class="mp-tx-controls">
                    <div class="mp-date-range">
                        <input type="date" id="mp-date-from" class="mp-date-input" />
                        <span class="mp-date-sep">—</span>
                        <input type="date" id="mp-date-to" class="mp-date-input" />
                    </div>
                    <button type="button" class="monkeypay-btn monkeypay-btn--sm" id="mp-refresh-data">
                        <svg viewBox="0 0 24 24"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                        <?php esc_html_e( 'Làm mới', 'monkeypay' ); ?>
                    </button>
                </div>
            </div>

            <!-- Transaction Table -->
            <div class="mp-tx-table-wrap" id="mp-tx-table-wrap">
                <div class="mp-tx-loading" id="mp-tx-loading">
                    <div class="mp-spinner"></div>
                    <span><?php esc_html_e( 'Đang tải dữ liệu...', 'monkeypay' ); ?></span>
                </div>
                <table class="mp-tx-table" id="mp-tx-table" style="display:none;">
                    <thead>
                        <tr>
                            <th class="mp-tx-col-time"><?php esc_html_e( 'Thời gian', 'monkeypay' ); ?></th>
                            <th class="mp-tx-col-desc"><?php esc_html_e( 'Nội dung', 'monkeypay' ); ?></th>
                            <th class="mp-tx-col-amount"><?php esc_html_e( 'Số tiền', 'monkeypay' ); ?></th>
                            <th class="mp-tx-col-balance"><?php esc_html_e( 'Số dư', 'monkeypay' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="mp-tx-tbody"></tbody>
                </table>
                <div class="mp-tx-empty" id="mp-tx-empty" style="display:none;">
                    <svg viewBox="0 0 24 24"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                    <span><?php esc_html_e( 'Không có giao dịch trong khoảng thời gian này', 'monkeypay' ); ?></span>
                </div>
            </div>
        </div>

        <!-- ═══ Connection & Info Card ═══ -->
        <div class="monkeypay-card" id="monkeypay-status-card">
            <div class="monkeypay-card__header">
                <div class="monkeypay-card__header-left">
                    <div class="monkeypay-card__icon">
                        <svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    </div>
                    <h2><?php esc_html_e( 'Trạng Thái Kết Nối', 'monkeypay' ); ?></h2>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="monkeypay-header__badge" id="monkeypay-status-badge">
                        <span class="monkeypay-status-dot"></span>
                        <span class="monkeypay-status-text"><?php esc_html_e( 'Đang kiểm tra...', 'monkeypay' ); ?></span>
                    </div>
                    <button type="button" class="monkeypay-btn monkeypay-btn--sm" id="monkeypay-test-connection">
                        <svg viewBox="0 0 24 24"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                        <?php esc_html_e( 'Kiểm tra', 'monkeypay' ); ?>
                    </button>
                </div>
            </div>
            <div class="monkeypay-status-grid" id="monkeypay-status-details">
                <div class="monkeypay-stat">
                    <span class="monkeypay-stat__label"><?php esc_html_e( 'Server', 'monkeypay' ); ?></span>
                    <span class="monkeypay-stat__value" id="stat-server">—</span>
                </div>
                <div class="monkeypay-stat">
                    <span class="monkeypay-stat__label"><?php esc_html_e( 'Cổng TT', 'monkeypay' ); ?></span>
                    <span class="monkeypay-stat__value" id="stat-gateways">—</span>
                </div>
                <div class="monkeypay-stat">
                    <span class="monkeypay-stat__label"><?php esc_html_e( 'Requests', 'monkeypay' ); ?></span>
                    <span class="monkeypay-stat__value" id="stat-requests">—</span>
                </div>
                <div class="monkeypay-stat">
                    <span class="monkeypay-stat__label"><?php esc_html_e( 'Gói', 'monkeypay' ); ?></span>
                    <span class="monkeypay-stat__value" id="stat-plan">—</span>
                </div>
            </div>
        </div>

        <!-- Webhook Info -->
        <div class="monkeypay-card">
            <div class="monkeypay-card__header">
                <div class="monkeypay-card__header-left">
                    <div class="monkeypay-card__icon">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    </div>
                    <h2><?php esc_html_e( 'Thông Tin', 'monkeypay' ); ?></h2>
                </div>
            </div>
            <div class="monkeypay-info-box">
                <strong><?php esc_html_e( 'Webhook URL', 'monkeypay' ); ?>:</strong>
                <code id="webhook-url"><?php echo esc_html( rest_url( 'monkeypay/v1/webhook' ) ); ?></code>
                <button type="button" class="monkeypay-btn-copy" data-copy="<?php echo esc_attr( rest_url( 'monkeypay/v1/webhook' ) ); ?>">
                    <svg viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                </button>
                <br/>
                <span style="font-size: 12px; color: var(--mp-text-muted);">
                    <?php esc_html_e( 'Cấu hình URL này trong MonkeyPay Server để nhận thông báo thanh toán.', 'monkeypay' ); ?>
                </span>
            </div>
        </div>

    </div>
</div>
