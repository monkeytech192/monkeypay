<?php
/**
 * MonkeyPay Dashboard Page
 *
 * Bank balance overview, connection path, transaction stats, recent transactions
 *
 * @package MonkeyPay
 * @since   4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load connections data for dashboard mini section
$connections_mgr = MonkeyPay_Connections::get_instance();
$connections     = $connections_mgr->get_connections();
$platform_meta   = MonkeyPay_Connections::get_platform_meta();
?>

<div class="wrap monkeypay-admin-wrap">
    <?php include MONKEYPAY_PLUGIN_DIR . 'templates/partials/global-header.php'; ?>

    <div class="monkeypay-admin-page">

        <!-- ═══ Balance Overview Card ═══ -->
        <div class="mp-dashboard-hero mp-fade-up" id="mp-dashboard-hero">
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

        <!-- ═══ Connection Path Visual ═══ -->
        <div class="mp-connection-path mp-fade-up" id="mp-connection-path">
            <div class="mp-connection-path__header">
                <h3><?php esc_html_e( 'Luồng kết nối', 'monkeypay' ); ?></h3>
                <button type="button" class="monkeypay-btn monkeypay-btn--sm monkeypay-btn--ghost" id="mp-test-flow">
                    <svg viewBox="0 0 24 24"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                    <?php esc_html_e( 'Kiểm tra', 'monkeypay' ); ?>
                </button>
            </div>
            <div class="mp-flow">
                <!-- Node: Bank -->
                <div class="mp-flow-node" id="mp-flow-bank">
                    <div class="mp-flow-node__icon mp-flow-node__icon--bank">
                        <svg viewBox="0 0 24 24"><path d="M3 21h18"/><path d="M3 10h18"/><path d="M5 6l7-3 7 3"/><path d="M4 10v11"/><path d="M20 10v11"/><path d="M8 14v3"/><path d="M12 14v3"/><path d="M16 14v3"/></svg>
                    </div>
                    <span class="mp-flow-node__label"><?php esc_html_e( 'Ngân hàng', 'monkeypay' ); ?></span>
                    <span class="mp-flow-node__status" id="mp-flow-bank-status">
                        <span class="mp-status-dot mp-status-dot--checking"></span>
                    </span>
                </div>

                <!-- Connector -->
                <div class="mp-flow-connector">
                    <div class="mp-flow-connector__line" id="mp-flow-line-1"></div>
                    <svg class="mp-flow-connector__arrow" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                </div>

                <!-- Node: Server -->
                <div class="mp-flow-node" id="mp-flow-server">
                    <div class="mp-flow-node__icon mp-flow-node__icon--server">
                        <svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
                    </div>
                    <span class="mp-flow-node__label"><?php esc_html_e( 'MonkeyPay Server', 'monkeypay' ); ?></span>
                    <span class="mp-flow-node__status" id="mp-flow-server-status">
                        <span class="mp-status-dot mp-status-dot--checking"></span>
                    </span>
                </div>

                <!-- Connector -->
                <div class="mp-flow-connector">
                    <div class="mp-flow-connector__line" id="mp-flow-line-2"></div>
                    <svg class="mp-flow-connector__arrow" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                </div>

                <!-- Node: Website -->
                <div class="mp-flow-node" id="mp-flow-website">
                    <div class="mp-flow-node__icon mp-flow-node__icon--website">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    </div>
                    <span class="mp-flow-node__label"><?php esc_html_e( 'Website', 'monkeypay' ); ?></span>
                    <span class="mp-flow-node__status" id="mp-flow-website-status">
                        <span class="mp-status-dot mp-status-dot--checking"></span>
                    </span>
                </div>
            </div>

            <!-- Webhook URL (compact, inline) -->
            <div class="mp-flow-webhook">
                <span class="mp-flow-webhook__label"><?php esc_html_e( 'Webhook URL:', 'monkeypay' ); ?></span>
                <code class="mp-flow-webhook__url" id="webhook-url"><?php echo esc_html( rest_url( 'monkeypay/v1/webhook' ) ); ?></code>
                <button type="button" class="monkeypay-btn-copy" data-copy="<?php echo esc_attr( rest_url( 'monkeypay/v1/webhook' ) ); ?>">
                    <svg viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                </button>
            </div>
        </div>

        <!-- ═══ Quick Actions ═══ -->
        <div class="mp-quick-actions mp-fade-up">
            <h3 class="mp-quick-actions__title"><?php esc_html_e( 'Thao Tác Nhanh', 'monkeypay' ); ?></h3>
            <div class="mp-quick-actions__grid">

                <!-- Card: Kết Nối — click chuyển trang, hiển thị logo connections bên trong -->
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=monkeypay-connections' ) ); ?>" class="mp-qa-card mp-qa-card--rich">
                    <div class="mp-qa-card__top">
                        <div class="mp-qa-card__icon mp-qa-card__icon--connect">
                            <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        </div>
                        <div class="mp-qa-card__info">
                            <span class="mp-qa-card__label"><?php esc_html_e( 'Kết Nối', 'monkeypay' ); ?></span>
                            <span class="mp-qa-card__desc"><?php esc_html_e( 'Cấu hình tích hợp', 'monkeypay' ); ?></span>
                        </div>
                        <svg class="mp-qa-card__arrow" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                    </div>
                    <div class="mp-qa-card__inline-data" id="mp-qa-conn-logos">
                        <div class="mp-dash-pills__loading"><div class="mp-spinner mp-spinner--sm"></div></div>
                    </div>
                </a>

                <!-- Card: API Key — hiển thị keys bên trong + nút tạo mới góc phải -->
                <div class="mp-qa-card mp-qa-card--rich mp-qa-card--interactive">
                    <div class="mp-qa-card__top">
                        <div class="mp-qa-card__icon mp-qa-card__icon--key">
                            <svg viewBox="0 0 24 24"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                        </div>
                        <div class="mp-qa-card__info">
                            <span class="mp-qa-card__label"><?php esc_html_e( 'API Keys', 'monkeypay' ); ?></span>
                            <span class="mp-qa-card__desc"><?php esc_html_e( 'Xác thực tích hợp', 'monkeypay' ); ?></span>
                        </div>
                        <button type="button" class="mp-qa-card__add-btn" id="mp-qa-create-key" title="<?php esc_attr_e( 'Tạo API Key mới', 'monkeypay' ); ?>">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>
                    </div>
                    <div class="mp-qa-card__inline-data" id="mp-qa-apikeys-pills">
                        <div class="mp-dash-pills__loading"><div class="mp-spinner mp-spinner--sm"></div></div>
                    </div>
                </div>

                <!-- Card: Cổng Thanh Toán — hiển thị logo ngân hàng + nút thêm góc phải -->
                <div class="mp-qa-card mp-qa-card--rich mp-qa-card--interactive">
                    <div class="mp-qa-card__top">
                        <div class="mp-qa-card__icon mp-qa-card__icon--gateways">
                            <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        </div>
                        <div class="mp-qa-card__info">
                            <span class="mp-qa-card__label"><?php esc_html_e( 'Cổng Thanh Toán', 'monkeypay' ); ?></span>
                            <span class="mp-qa-card__desc"><?php esc_html_e( 'Phương thức thanh toán', 'monkeypay' ); ?></span>
                        </div>
                        <button type="button" class="mp-qa-card__add-btn" id="mp-qa-create-gateway" title="<?php esc_attr_e( 'Thêm cổng thanh toán', 'monkeypay' ); ?>">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>
                    </div>
                    <div class="mp-qa-card__inline-data" id="mp-qa-gateways-logos">
                        <div class="mp-dash-pills__loading"><div class="mp-spinner mp-spinner--sm"></div></div>
                    </div>
                </div>

            </div>
        </div>


        <!-- ═══ Transaction History Card ═══ -->
        <div class="monkeypay-card mp-transactions-card mp-fade-up">
            <div class="monkeypay-card__header">
                <div class="monkeypay-card__header-left">
                    <div class="monkeypay-card__icon">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <h2><?php esc_html_e( 'Giao Dịch Gần Đây', 'monkeypay' ); ?></h2>
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

            <!-- View All link -->
            <div class="mp-tx-view-all" id="mp-tx-view-all" style="display:none;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=monkeypay&tab=transactions' ) ); ?>" class="mp-link-view-all">
                    <?php esc_html_e( 'Xem tất cả giao dịch', 'monkeypay' ); ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            </div>
        </div>

    </div>
</div>

<!-- ═══ Create Key Modal (reused from API Keys page) ═══ -->
<div id="mp-create-key-modal" class="mp-modal" aria-hidden="true">
    <div class="mp-modal__backdrop"></div>
    <div class="mp-modal__sheet" role="dialog" aria-modal="true">
        <div class="mp-modal__drag-handle"><span></span></div>
        <div class="mp-modal__header">
            <div class="mp-modal__platform-info">
                <div class="mp-modal__platform-icon" style="color: var(--mp-primary);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </div>
                <div>
                    <h3 class="mp-modal__title"><?php esc_html_e( 'Tạo API Key Mới', 'monkeypay' ); ?></h3>
                    <p class="mp-modal__subtitle"><?php esc_html_e( 'Đặt nhãn để phân biệt giữa các key', 'monkeypay' ); ?></p>
                </div>
            </div>
            <button type="button" class="mp-modal__close" id="mp-create-modal-close" aria-label="<?php esc_attr_e( 'Đóng', 'monkeypay' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="mp-modal__body">
            <div class="mp-form-group">
                <label class="mp-form-label" for="mp-new-key-label"><?php esc_html_e( 'Nhãn', 'monkeypay' ); ?> <span class="mp-form-opt">(<?php esc_html_e( 'tùy chọn', 'monkeypay' ); ?>)</span></label>
                <p class="mp-form-hint" style="margin: 2px 0 10px;"><?php esc_html_e( 'Đặt tên để dễ phân biệt giữa các key.', 'monkeypay' ); ?></p>
                <input type="text" id="mp-new-key-label" class="mp-form-input" placeholder="<?php esc_attr_e( 'Ví dụ: Production, Staging, Test...', 'monkeypay' ); ?>" maxlength="100">
            </div>
        </div>
        <div class="mp-modal__actions mp-modal__actions--right" style="padding: 0 24px 24px; border-top: none; margin-top: 0;">
            <button type="button" class="mp-btn mp-btn--ghost mp-dash-modal-cancel"><?php esc_html_e( 'Hủy', 'monkeypay' ); ?></button>
            <button type="button" class="mp-btn mp-btn--primary" id="mp-confirm-create-key">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <?php esc_html_e( 'Tạo Key', 'monkeypay' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- ═══ Show New Key Modal ═══ -->
<div id="mp-show-key-modal" class="mp-modal" aria-hidden="true">
    <div class="mp-modal__backdrop"></div>
    <div class="mp-modal__sheet" role="dialog" aria-modal="true">
        <div class="mp-modal__drag-handle"><span></span></div>
        <div class="mp-modal__header">
            <div class="mp-modal__platform-info">
                <div class="mp-modal__platform-icon" style="color: var(--mp-success); background: rgba(16, 185, 129, 0.1);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div>
                    <h3 class="mp-modal__title" style="color: var(--mp-success);"><?php esc_html_e( 'API Key Đã Tạo!', 'monkeypay' ); ?></h3>
                    <p class="mp-modal__subtitle"><?php esc_html_e( 'Sao chép ngay — key sẽ không hiển thị lại', 'monkeypay' ); ?></p>
                </div>
            </div>
            <button type="button" class="mp-modal__close mp-dash-modal-cancel" aria-label="<?php esc_attr_e( 'Đóng', 'monkeypay' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="mp-modal__body">
            <div class="mp-new-key-display">
                <div class="mp-new-key-warning">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span><?php esc_html_e( 'Sao chép ngay! Key sẽ', 'monkeypay' ); ?> <strong><?php esc_html_e( 'không hiển thị lại', 'monkeypay' ); ?></strong>.</span>
                </div>
                <div class="mp-new-key-box">
                    <code id="mp-new-key-value" class="mp-new-key-code"></code>
                    <button type="button" class="mp-btn mp-btn--outline mp-btn--sm monkeypay-btn-copy" id="mp-copy-new-key" data-copy="">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <?php esc_html_e( 'Sao chép', 'monkeypay' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="mp-modal__actions" style="padding: 0 24px 24px;">
            <span></span>
            <div class="mp-modal__actions-right">
                <button type="button" class="mp-btn mp-btn--primary mp-dash-modal-cancel"><?php esc_html_e( 'Đã sao chép, đóng', 'monkeypay' ); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Create Gateway Modal ═══ -->
<div id="mp-create-gateway-modal" class="mp-modal" aria-hidden="true">
    <div class="mp-modal__backdrop"></div>
    <div class="mp-modal__sheet" role="dialog" aria-modal="true">
        <div class="mp-modal__drag-handle"><span></span></div>
        <div class="mp-modal__header">
            <div class="mp-modal__platform-info">
                <div class="mp-modal__platform-icon" style="color: #10b981; background: rgba(16, 185, 129, 0.1);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                </div>
                <div>
                    <h3 class="mp-modal__title"><?php esc_html_e( 'Tạo Cổng Thanh Toán', 'monkeypay' ); ?></h3>
                    <p class="mp-modal__subtitle"><?php esc_html_e( 'Chọn ngân hàng và nhập thông tin tài khoản', 'monkeypay' ); ?></p>
                </div>
            </div>
            <button type="button" class="mp-modal__close" id="mp-gateway-modal-close" aria-label="<?php esc_attr_e( 'Đóng', 'monkeypay' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="mp-modal__body">
            <!-- Bank Selector -->
            <div class="mp-form-group">
                <label class="mp-form-label"><?php esc_html_e( 'Ngân hàng', 'monkeypay' ); ?> <span style="color:var(--mp-error);">*</span></label>
                <div class="mp-bank-select" id="mp-gw-bank-select">
                    <div class="mp-bank-select__trigger" id="mp-gw-bank-trigger">
                        <span class="mp-bank-select__placeholder"><?php esc_html_e( 'Chọn ngân hàng...', 'monkeypay' ); ?></span>
                        <svg class="mp-bank-select__chevron" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <div class="mp-bank-select__dropdown" id="mp-gw-bank-dropdown">
                        <div class="mp-bank-option" data-code="mbbank" data-name="MB Bank" data-logo="https://api.vietqr.io/img/MB.png" data-bin="970422">
                            <img src="https://api.vietqr.io/img/MB.png" alt="MB Bank" class="mp-bank-option__logo" />
                            <span class="mp-bank-option__name">MB Bank</span>
                        </div>
                        <div class="mp-bank-option" data-code="vpbank" data-name="VPBank" data-logo="https://api.vietqr.io/img/VPB.png" data-bin="970432">
                            <img src="https://api.vietqr.io/img/VPB.png" alt="VPBank" class="mp-bank-option__logo" />
                            <span class="mp-bank-option__name">VPBank</span>
                        </div>
                        <div class="mp-bank-option" data-code="vietcombank" data-name="Vietcombank" data-logo="https://api.vietqr.io/img/VCB.png" data-bin="970436">
                            <img src="https://api.vietqr.io/img/VCB.png" alt="Vietcombank" class="mp-bank-option__logo" />
                            <span class="mp-bank-option__name">Vietcombank</span>
                        </div>
                        <div class="mp-bank-option" data-code="bidv" data-name="BIDV" data-logo="https://api.vietqr.io/img/BIDV.png" data-bin="970418">
                            <img src="https://api.vietqr.io/img/BIDV.png" alt="BIDV" class="mp-bank-option__logo" />
                            <span class="mp-bank-option__name">BIDV</span>
                        </div>
                    </div>
                    <input type="hidden" id="mp-gw-bank-code" value="" />
                </div>
            </div>
            <!-- Account Number -->
            <div class="mp-form-group">
                <label class="mp-form-label" for="mp-gw-account-number"><?php esc_html_e( 'Số tài khoản', 'monkeypay' ); ?> <span style="color:var(--mp-error);">*</span></label>
                <input type="text" id="mp-gw-account-number" class="mp-form-input" placeholder="<?php esc_attr_e( 'Ví dụ: 0962794917', 'monkeypay' ); ?>" maxlength="30">
                <p class="mp-form-hint"><?php esc_html_e( 'Số tài khoản ngân hàng nhận thanh toán.', 'monkeypay' ); ?></p>
            </div>
            <!-- Account Name -->
            <div class="mp-form-group">
                <label class="mp-form-label" for="mp-gw-account-name"><?php esc_html_e( 'Tên chủ tài khoản', 'monkeypay' ); ?></label>
                <input type="text" id="mp-gw-account-name" class="mp-form-input" placeholder="<?php esc_attr_e( 'Ví dụ: HO LE MINH TUAN', 'monkeypay' ); ?>" maxlength="100" style="text-transform:uppercase;">
                <p class="mp-form-hint"><?php esc_html_e( 'Tên chủ tài khoản viết IN HOA, không dấu.', 'monkeypay' ); ?></p>
            </div>
        </div>
        <div class="mp-modal__actions mp-modal__actions--right" style="padding: 16px 24px 24px; margin-top: 8px;">
            <button type="button" class="mp-btn mp-btn--ghost mp-gw-modal-cancel"><?php esc_html_e( 'Hủy', 'monkeypay' ); ?></button>
            <button type="button" class="mp-btn mp-btn--primary" id="mp-confirm-create-gateway">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <?php esc_html_e( 'Tạo cổng thanh toán', 'monkeypay' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- Dashboard data for JS -->
<script>
    window.mpPlatformMeta = <?php echo wp_json_encode( $platform_meta ); ?>;
    window.mpConnections  = <?php echo wp_json_encode( $connections ); ?>;
</script>
