<?php
/**
 * Admin Dashboard Page — MonkeyPay v4.1
 *
 * Redesigned layout: 3-col top (Visa card + Stats + Connection Flow),
 * pill date filter, Chart.js cash flow chart, transaction table.
 *
 * @package MonkeyPay
 * @since   4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$webhook_url = rest_url( 'monkeypay/v1/webhook' );
$api_key     = get_option( 'monkeypay_api_key', '' );

// Connection data for JS — use singleton to read correct option key
$conn_manager     = MonkeyPay_Connections::get_instance();
$connections      = $conn_manager->get_connections();
$platform_meta    = MonkeyPay_Connections::get_platform_meta();
$connections_list = [];
if ( is_array( $connections ) ) {
    foreach ( $connections as $conn ) {
        $connections_list[] = [
            'id'       => $conn['id'] ?? '',
            'platform' => $conn['platform'] ?? '',
            'enabled'  => ! empty( $conn['enabled'] ),
        ];
    }
}
?>
<div class="wrap monkeypay-admin-wrap">
    <?php include MONKEYPAY_PLUGIN_DIR . 'templates/partials/global-header.php'; ?>

    <div class="monkeypay-admin-page">

        <!-- Page Header -->
        <div class="monkeypay-page-header">
            <div>
                <h2 class="monkeypay-page-title">
                    <svg viewBox="0 0 24 24" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;color:var(--mp-primary);fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    <?php esc_html_e( 'Dashboard', 'monkeypay' ); ?>
                </h2>
                <p class="monkeypay-page-desc" data-i18n="dashboard_desc"><?php esc_html_e( 'Tổng quan hoạt động thanh toán và trạng thái hệ thống', 'monkeypay' ); ?></p>
            </div>
        </div>

<script>
window.mpPlatformMeta = <?php echo wp_json_encode( $platform_meta ); ?>;
window.mpConnections  = <?php echo wp_json_encode( $connections_list ); ?>;
</script>

<!-- ═══════════════════════════════════════════════════
     DASHBOARD 2-COLUMN LAYOUT
     Left (1/3): Membership Card + Quick Actions (vertical)
     Right (2/3): Stats + Connection → Chart → Transactions
     ═══════════════════════════════════════════════════ -->
<div class="mp-dashboard-layout mp-fade-up">

    <!-- ── LEFT SIDEBAR ─────────────────────────────── -->
    <div class="mp-dashboard-left">

        <!-- Membership Card -->
        <div class="mp-visa-card mp-member-card">
            <div class="mp-visa-card__shine"></div>
            <div class="mp-visa-card__pattern"></div>

            <!-- Row 1: Brand + Plan badge -->
            <div class="mp-member-card__top">
                <div class="mp-member-card__brand">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="rgba(255,255,255,0.85)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
                    </svg>
                    <span class="mp-member-card__brand-name">MonkeyPay</span>
                </div>
                <span class="mp-member-card__plan-badge" id="mp-card-plan">
                    <span class="mp-balance-skeleton mp-balance-skeleton--xs">&nbsp;</span>
                </span>
            </div>

            <!-- Row 2: Merchant / Org name -->
            <div class="mp-member-card__org">
                <span class="mp-member-card__org-label" data-i18n="card_org">TỔ CHỨC</span>
                <span class="mp-member-card__org-name" id="mp-card-org">
                    <span class="mp-balance-skeleton mp-balance-skeleton--sm">&nbsp;</span>
                </span>
            </div>

            <!-- Row 3: API Key (masked) -->
            <div class="mp-member-card__key">
                <span class="mp-member-card__key-label" data-i18n="card_api_key">API KEY</span>
                <span class="mp-member-card__key-value" id="mp-card-apikey">
                    <span class="mp-balance-skeleton">&nbsp;</span>
                </span>
            </div>

            <!-- Row 4: Bottom — Account holder + Expiry -->
            <div class="mp-visa-card__bottom">
                <div class="mp-visa-card__holder">
                    <span class="mp-visa-card__holder-label" data-i18n="card_holder">CHỦ TÀI KHOẢN</span>
                    <span class="mp-visa-card__holder-name" id="mp-card-holder">
                        <span class="mp-balance-skeleton mp-balance-skeleton--sm">&nbsp;</span>
                    </span>
                </div>
                <div class="mp-member-card__expiry">
                    <span class="mp-member-card__expiry-label" data-i18n="card_expiry">HẾT HẠN</span>
                    <span class="mp-member-card__expiry-value" id="mp-card-expiry">—</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions (vertical stack) -->
        <div class="mp-sidebar-actions">
            <h4 class="mp-sidebar-actions__title" data-i18n="quick_actions">Thao tác nhanh</h4>

            <!-- Connections -->
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=monkeypay-connections' ) ); ?>" class="mp-qa-card">
                <div class="mp-qa-card__icon mp-qa-card__icon--conn">
                    <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                </div>
                <div class="mp-qa-card__info">
                    <span class="mp-qa-card__name" data-i18n="qa_connections">Kết nối</span>
                    <div class="mp-qa-card__inline" id="mp-qa-conn-logos">
                        <span class="mp-dash-pills__empty" data-i18n="loading">Đang tải...</span>
                    </div>
                </div>
            </a>

            <!-- API Keys -->
            <div class="mp-qa-card" id="mp-qa-create-key">
                <div class="mp-qa-card__icon mp-qa-card__icon--key">
                    <svg viewBox="0 0 24 24"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                </div>
                <div class="mp-qa-card__info">
                    <span class="mp-qa-card__name">API Keys</span>
                    <div class="mp-qa-card__inline" id="mp-qa-apikeys-pills">
                        <span class="mp-dash-pills__empty" data-i18n="loading">Đang tải...</span>
                    </div>
                </div>
            </div>

            <!-- Payment Gateways -->
            <div class="mp-qa-card" id="mp-qa-create-gateway">
                <div class="mp-qa-card__icon mp-qa-card__icon--gateway">
                    <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <div class="mp-qa-card__info">
                    <span class="mp-qa-card__name" data-i18n="qa_gateways">Cổng thanh toán</span>
                    <div class="mp-qa-card__inline" id="mp-qa-gateways-logos">
                        <span class="mp-dash-pills__empty" data-i18n="loading">Đang tải...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Display Settings (toggles, auto-save on click) -->
        <?php
            $qs_dark = get_option( 'monkeypay_dark_mode', 'light' );
            $qs_lang = get_option( 'monkeypay_language', 'vi' );
        ?>
        <div class="mp-quick-display">
            <h4 class="mp-quick-display__title" data-i18n="quick_display">Giao diện</h4>

            <!-- Theme + Language — single compact row -->
            <div class="mp-quick-display__row">
                <!-- Theme Toggle (slide) -->
                <div class="mp-slide-toggle" data-mp-key="monkeypay_dark_mode">
                    <button class="mp-slide-toggle__opt<?php echo $qs_dark === 'light' ? ' mp-slide-toggle__opt--active' : ''; ?>"
                            data-mp-value="light" aria-label="Light mode">☀️</button>
                    <button class="mp-slide-toggle__opt<?php echo $qs_dark === 'dark' ? ' mp-slide-toggle__opt--active' : ''; ?>"
                            data-mp-value="dark" aria-label="Dark mode">🌙</button>
                    <span class="mp-slide-toggle__thumb"></span>
                </div>

                <!-- Language Toggle (slide) -->
                <div class="mp-slide-toggle" data-mp-key="monkeypay_language">
                    <button class="mp-slide-toggle__opt<?php echo $qs_lang === 'vi' ? ' mp-slide-toggle__opt--active' : ''; ?>"
                            data-mp-value="vi" aria-label="Vietnamese"><svg viewBox="0 0 48 32" width="18" height="12"><rect width="48" height="32" rx="3" fill="#DA251D"/><polygon points="24,4 27.5,14.5 38.5,14.5 29.5,20.5 33,31 24,24 15,31 18.5,20.5 9.5,14.5 20.5,14.5" fill="#FFFF00"/></svg></button>
                    <button class="mp-slide-toggle__opt<?php echo $qs_lang === 'en' ? ' mp-slide-toggle__opt--active' : ''; ?>"
                            data-mp-value="en" aria-label="English"><svg viewBox="0 0 60 30" width="18" height="9"><clipPath id="ds"><rect width="60" height="30" rx="3"/></clipPath><g clip-path="url(#ds)"><rect width="60" height="30" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#C8102E" stroke-width="4"/><path d="M30,0 V30 M0,15 H60" stroke="#fff" stroke-width="10"/><path d="M30,0 V30 M0,15 H60" stroke="#C8102E" stroke-width="6"/></g></svg></button>
                    <span class="mp-slide-toggle__thumb"></span>
                </div>
            </div>
        </div>

    </div><!-- .mp-dashboard-left -->

    <!-- ── RIGHT MAIN CONTENT ───────────────────────── -->
    <div class="mp-dashboard-right">

        <!-- Row 1: Stats + Connection Status (side by side) -->
        <div class="mp-right-top">
            <!-- Stat Cards -->
            <div class="mp-hero-stats">
                <div class="mp-hero-stat mp-hero-stat--in">
                    <div class="mp-hero-stat__icon">
                        <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                    </div>
                    <div class="mp-hero-stat__info">
                        <span class="mp-hero-stat__label" data-i18n="money_in">Tiền vào</span>
                        <span class="mp-hero-stat__value" id="mp-stat-in">0 ₫</span>
                    </div>
                    <span class="mp-hero-stat__count" id="mp-stat-in-count">0 GD</span>
                </div>

                <div class="mp-hero-stat mp-hero-stat--out">
                    <div class="mp-hero-stat__icon">
                        <svg viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                    </div>
                    <div class="mp-hero-stat__info">
                        <span class="mp-hero-stat__label" data-i18n="money_out">Tiền ra</span>
                        <span class="mp-hero-stat__value" id="mp-stat-out">0 ₫</span>
                    </div>
                    <span class="mp-hero-stat__count" id="mp-stat-out-count">0 GD</span>
                </div>

                <div class="mp-hero-stat mp-hero-stat--total">
                    <div class="mp-hero-stat__icon">
                        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    </div>
                    <div class="mp-hero-stat__info">
                        <span class="mp-hero-stat__label" data-i18n="total_transactions">Tổng giao dịch</span>
                        <span class="mp-hero-stat__value" id="mp-stat-total">0</span>
                    </div>
                </div>
            </div>

            <!-- Connection Flow -->
            <div class="mp-conn-compact" id="mp-connection-path">
                <div class="mp-conn-compact__header">
                    <span class="mp-conn-compact__title" data-i18n="connection_status">Trạng thái kết nối</span>
                    <button type="button" class="mp-conn-compact__refresh" id="mp-test-flow" title="Kiểm tra lại" data-i18n-title="recheck">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                        </svg>
                    </button>
                </div>
                <div class="mp-conn-compact__flow">
                    <div class="mp-conn-compact__node" id="mp-flow-bank">
                        <div class="mp-conn-compact__icon mp-conn-compact__icon--bank">
                            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/>
                            </svg>
                            <span class="mp-conn-compact__badge" id="mp-flow-bank-status">
                                <span class="mp-status-dot mp-status-dot--checking"></span>
                            </span>
                        </div>
                        <span class="mp-conn-compact__label">Bank</span>
                    </div>

                    <div class="mp-conn-compact__line" id="mp-flow-line-1"></div>

                    <div class="mp-conn-compact__node" id="mp-flow-server">
                        <div class="mp-conn-compact__icon mp-conn-compact__icon--server">
                            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/>
                                <line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>
                            </svg>
                            <span class="mp-conn-compact__badge" id="mp-flow-server-status">
                                <span class="mp-status-dot mp-status-dot--checking"></span>
                            </span>
                        </div>
                        <span class="mp-conn-compact__label">Server</span>
                    </div>

                    <div class="mp-conn-compact__line" id="mp-flow-line-2"></div>

                    <div class="mp-conn-compact__node" id="mp-flow-website">
                        <div class="mp-conn-compact__icon mp-conn-compact__icon--website">
                            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                                <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                            </svg>
                            <span class="mp-conn-compact__badge" id="mp-flow-website-status">
                                <span class="mp-status-dot mp-status-dot--checking"></span>
                            </span>
                        </div>
                        <span class="mp-conn-compact__label">Website</span>
                    </div>
                </div>
                <div class="mp-conn-compact__webhook">
                    <span class="mp-conn-compact__webhook-label">Webhook</span>
                    <code class="mp-conn-compact__webhook-url"><?php echo esc_html( $webhook_url ); ?></code>
                    <button type="button" class="monkeypay-btn-copy" data-copy="<?php echo esc_attr( $webhook_url ); ?>" title="Sao chép" data-i18n-title="copy">
                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div><!-- .mp-right-top -->

        <!-- Row 2: Cash Flow Chart -->
        <div class="monkeypay-card mp-chart-section">
            <div class="mp-chart-section__header">
                <h3 class="mp-chart-section__title">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    <span data-i18n="cashflow_chart_title">Biểu đồ dòng tiền</span>
                </h3>
                <div class="mp-date-pills" id="mp-date-pills">
                    <button type="button" class="mp-date-pill mp-date-pill--active" data-range="today" data-i18n="date_today">Hôm nay</button>
                    <button type="button" class="mp-date-pill" data-range="yesterday" data-i18n="date_yesterday">Hôm qua</button>
                    <button type="button" class="mp-date-pill" data-range="7days" data-i18n="date_7days">7 ngày</button>
                    <button type="button" class="mp-date-pill" data-range="30days" data-i18n="date_30days">30 ngày</button>
                    <button type="button" class="mp-date-pill" data-range="this_week" data-i18n="date_this_week">Tuần này</button>
                    <button type="button" class="mp-date-pill" data-range="last_week" data-i18n="date_last_week">Tuần trước</button>
                    <button type="button" class="mp-date-pill" data-range="this_month" data-i18n="date_this_month">Tháng này</button>
                    <button type="button" class="mp-date-pill" data-range="last_month" data-i18n="date_last_month">Tháng trước</button>
                    <button type="button" class="mp-date-pill" data-range="custom">
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:3px">
                            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span data-i18n="date_custom">Khoảng thời gian</span>
                    </button>
                </div>
                <div class="mp-date-custom" id="mp-date-custom" style="display:none">
                    <input type="date" id="mp-date-from" class="mp-date-input" />
                    <span class="mp-date-sep">→</span>
                    <input type="date" id="mp-date-to" class="mp-date-input" />
                    <button type="button" class="monkeypay-btn monkeypay-btn--sm monkeypay-btn--primary" id="mp-apply-custom" data-i18n="apply">Áp dụng</button>
                </div>
                <button type="button" class="mp-chart-section__refresh" id="mp-refresh-data" title="Làm mới dữ liệu" data-i18n-title="refresh_data">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                        <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                    </svg>
                </button>
            </div>
            <div class="mp-chart-container">
                <!-- Skeleton chart bars -->
                <div id="mp-chart-skeleton" class="mp-chart-skeleton">
                    <div class="mp-chart-skeleton__bar mp-skeleton" style="height:45%"></div>
                    <div class="mp-chart-skeleton__bar mp-skeleton" style="height:70%"></div>
                    <div class="mp-chart-skeleton__bar mp-skeleton" style="height:55%"></div>
                    <div class="mp-chart-skeleton__bar mp-skeleton" style="height:85%"></div>
                    <div class="mp-chart-skeleton__bar mp-skeleton" style="height:40%"></div>
                    <div class="mp-chart-skeleton__bar mp-skeleton" style="height:65%"></div>
                    <div class="mp-chart-skeleton__bar mp-skeleton" style="height:50%"></div>
                    <div class="mp-chart-skeleton__bar mp-skeleton" style="height:75%"></div>
                    <div class="mp-chart-skeleton__bar mp-skeleton" style="height:60%"></div>
                    <div class="mp-chart-skeleton__bar mp-skeleton" style="height:35%"></div>
                    <div class="mp-chart-skeleton__bar mp-skeleton" style="height:80%"></div>
                    <div class="mp-chart-skeleton__bar mp-skeleton" style="height:45%"></div>
                </div>
                <canvas id="mp-cashflow-chart" style="display:none"></canvas>
            </div>
        </div>

        <!-- Row 3: Recent Transactions -->
        <div class="monkeypay-card mp-transactions-card">
            <div class="monkeypay-card__header">
                <h3 class="monkeypay-card__title">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                    </svg>
                    <span data-i18n="recent_transactions">Giao dịch gần đây</span>
                </h3>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=monkeypay-transactions' ) ); ?>" class="monkeypay-btn monkeypay-btn--ghost monkeypay-btn--sm" id="mp-tx-view-all" style="display:none">
                    <span data-i18n="view_all">Xem tất cả</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            </div>

            <!-- Skeleton Loading -->
            <div id="mp-tx-loading" class="mp-tx-skeleton">
                <?php for ($i = 0; $i < 5; $i++) : ?>
                <div class="mp-tx-skeleton__row">
                    <div class="mp-tx-skeleton__cell mp-skeleton mp-tx-skeleton__cell--sm"></div>
                    <div class="mp-tx-skeleton__cell mp-skeleton mp-tx-skeleton__cell--wide"></div>
                    <div class="mp-tx-skeleton__cell mp-skeleton mp-tx-skeleton__cell--xs"></div>
                    <div class="mp-tx-skeleton__cell mp-skeleton mp-tx-skeleton__cell--sm"></div>
                </div>
                <?php endfor; ?>

            <!-- Empty -->
            <div id="mp-tx-empty" class="mp-tx-empty" style="display:none">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span data-i18n="no_transactions">Không có giao dịch nào trong khoảng thời gian này</span>
            </div>

            <!-- Table -->
            <div id="mp-tx-table" class="mp-tx-table-wrap" style="display:none">
                <table class="mp-tx-table">
                    <thead>
                        <tr>
                            <th class="mp-tx-col-time" data-i18n="col_time">Thời gian</th>
                            <th class="mp-tx-col-desc" data-i18n="col_desc">Mô tả</th>
                            <th class="mp-tx-col-amount" data-i18n="col_amount">Số tiền</th>
                            <th class="mp-tx-col-balance" data-i18n="col_balance">Số dư</th>
                        </tr>
                    </thead>
                    <tbody id="mp-tx-tbody"></tbody>
                </table>
            </div>
        </div>

    </div><!-- .mp-dashboard-right -->

</div><!-- .mp-dashboard-layout -->

<!-- ═══════════════════════════════════════════════════
     MODALS (Create Key / Show Key / Create Gateway)
     ═══════════════════════════════════════════════════ -->

<!-- Create Key Modal -->
<div class="mp-dash-modal-overlay" id="mp-create-key-modal" style="display:none">
    <div class="mp-dash-modal">
        <div class="mp-dash-modal__header">
            <h3 data-i18n="modal_create_key">Tạo API Key mới</h3>
            <button type="button" class="mp-dash-modal__close" id="mp-create-modal-close">&times;</button>
        </div>
        <div class="mp-dash-modal__body">
            <label class="mp-dash-modal__label" for="mp-new-key-label" data-i18n="modal_key_label">Tên key (tuỳ chọn)</label>
            <input type="text" id="mp-new-key-label" class="mp-dash-modal__input" placeholder="Ví dụ: Website chính" data-i18n-placeholder="modal_key_placeholder">
        </div>
        <div class="mp-dash-modal__footer">
            <button type="button" class="monkeypay-btn monkeypay-btn--ghost mp-dash-modal-cancel" data-i18n="cancel">Huỷ</button>
            <button type="button" class="monkeypay-btn monkeypay-btn--primary" id="mp-confirm-create-key" data-i18n="create_key">Tạo Key</button>
        </div>
    </div>
</div>

<!-- Show New Key Modal -->
<div class="mp-dash-modal-overlay" id="mp-show-key-modal" style="display:none">
    <div class="mp-dash-modal">
        <div class="mp-dash-modal__header">
            <h3 data-i18n="modal_key_created">API Key đã tạo</h3>
            <button type="button" class="mp-dash-modal__close mp-dash-modal-cancel">&times;</button>
        </div>
        <div class="mp-dash-modal__body">
            <p class="mp-dash-modal__warn">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span data-i18n="modal_key_warning">Hãy sao chép key này ngay. Bạn sẽ không thể xem lại.</span>
            </p>
            <div class="mp-dash-modal__key-display">
                <code id="mp-new-key-value"></code>
                <button type="button" class="monkeypay-btn-copy" id="mp-copy-new-key" data-copy="">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="mp-dash-modal__footer">
            <button type="button" class="monkeypay-btn monkeypay-btn--primary mp-dash-modal-cancel" data-i18n="close">Đóng</button>
        </div>
    </div>
</div>

<!-- Create Gateway Modal -->
<div class="mp-dash-modal-overlay" id="mp-create-gateway-modal" style="display:none">
    <div class="mp-dash-modal">
        <div class="mp-dash-modal__header">
            <h3 data-i18n="modal_add_gateway">Thêm cổng thanh toán</h3>
            <button type="button" class="mp-dash-modal__close" id="mp-gateway-modal-close">&times;</button>
        </div>
        <div class="mp-dash-modal__body">
            <!-- Bank Select -->
            <label class="mp-dash-modal__label" data-i18n="bank">Ngân hàng</label>
            <div class="mp-bank-select">
                <div class="mp-bank-select__trigger" id="mp-gw-bank-trigger">
                    <span class="mp-bank-select__placeholder" data-i18n="select_bank">Chọn ngân hàng...</span>
                    <svg class="mp-bank-select__chevron" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <input type="hidden" id="mp-gw-bank-code" />
                <div class="mp-bank-select__dropdown" id="mp-gw-bank-dropdown">
                    <?php
                    $banks = [
                        [ 'code' => 'mbbank',      'name' => 'MB Bank',      'logo' => 'https://api.vietqr.io/img/MB.png' ],
                        [ 'code' => 'vpbank',      'name' => 'VPBank',       'logo' => 'https://api.vietqr.io/img/VPB.png' ],
                        [ 'code' => 'vietcombank', 'name' => 'Vietcombank',  'logo' => 'https://api.vietqr.io/img/VCB.png' ],
                        [ 'code' => 'bidv',        'name' => 'BIDV',         'logo' => 'https://api.vietqr.io/img/BIDV.png' ],
                        [ 'code' => 'techcombank', 'name' => 'Techcombank',  'logo' => 'https://api.vietqr.io/img/TCB.png' ],
                        [ 'code' => 'acb',         'name' => 'ACB',          'logo' => 'https://api.vietqr.io/img/ACB.png' ],
                        [ 'code' => 'tpbank',      'name' => 'TPBank',       'logo' => 'https://api.vietqr.io/img/TPB.png' ],
                        [ 'code' => 'sacombank',   'name' => 'Sacombank',    'logo' => 'https://api.vietqr.io/img/STB.png' ],
                    ];
                    foreach ( $banks as $bank ) :
                    ?>
                        <div class="mp-bank-option"
                             data-code="<?php echo esc_attr( $bank['code'] ); ?>"
                             data-name="<?php echo esc_attr( $bank['name'] ); ?>"
                             data-logo="<?php echo esc_attr( $bank['logo'] ); ?>">
                            <img src="<?php echo esc_url( $bank['logo'] ); ?>" alt="" width="24" height="24" />
                            <span><?php echo esc_html( $bank['name'] ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Account Number -->
            <label class="mp-dash-modal__label" for="mp-gw-account-number" style="margin-top:16px" data-i18n="account_number">Số tài khoản</label>
            <input type="text" id="mp-gw-account-number" class="mp-dash-modal__input" placeholder="Nhập số tài khoản" data-i18n-placeholder="enter_account_number" />

            <!-- Account Name -->
            <label class="mp-dash-modal__label" for="mp-gw-account-name" style="margin-top:12px" data-i18n="account_holder">Chủ tài khoản</label>
            <input type="text" id="mp-gw-account-name" class="mp-dash-modal__input" placeholder="VD: NGUYEN VAN A" data-i18n-placeholder="account_holder_placeholder" />
        </div>
        <div class="mp-dash-modal__footer">
            <button type="button" class="monkeypay-btn monkeypay-btn--ghost mp-gw-modal-cancel" data-i18n="cancel">Huỷ</button>
            <button type="button" class="monkeypay-btn monkeypay-btn--primary" id="mp-confirm-create-gateway" data-i18n="add_gateway">Thêm cổng</button>
        </div>
    </div>
</div>

    </div><!-- .monkeypay-admin-page -->
</div><!-- .monkeypay-admin-wrap -->
