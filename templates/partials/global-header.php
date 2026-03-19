<?php
/**
 * MonkeyPay Global Header
 *
 * Logo + Navigation Tabs — same pattern as checkin-mkt192-wp
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

$nav_items = [
    'monkeypay' => [
        'title' => __( 'Dashboard', 'monkeypay' ),
        'icon'  => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
    ],
    'monkeypay-integrations' => [
        'title' => __( 'Tích Hợp', 'monkeypay' ),
        'icon'  => '<svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
    ],
    'monkeypay-connections' => [
        'title' => __( 'Kết Nối', 'monkeypay' ),
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 7h3a5 5 0 0 1 0 10h-3"/><path d="M9 17H6a5 5 0 0 1 0-10h3"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
    ],
    'monkeypay-gateways' => [
        'title' => __( 'Cổng Thanh Toán', 'monkeypay' ),
        'icon'  => '<svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
    ],
    'monkeypay-account' => [
        'title' => __( 'Tài Khoản', 'monkeypay' ),
        'icon'  => '<svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    ],
    'monkeypay-settings' => [
        'title' => __( 'Cài Đặt', 'monkeypay' ),
        'icon'  => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    ],
    'monkeypay-pricing' => [
        'title' => __( 'Bảng Giá', 'monkeypay' ),
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
    ],
];
?>

<div class="monkeypay-global-header">
    <div class="monkeypay-header-brand">
        <div class="monkeypay-brand-logo">
            <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="monkeypay-brand-info">
            <h1 class="monkeypay-brand-name">MonkeyPay</h1>
            <p class="monkeypay-brand-version">v<?php echo MONKEYPAY_VERSION; ?></p>
        </div>
    </div>

    <nav class="monkeypay-global-nav">
        <?php foreach ( $nav_items as $page_slug => $item ) : ?>
        <a href="<?php echo admin_url( 'admin.php?page=' . $page_slug ); ?>"
            class="monkeypay-nav-item <?php echo $current_page === $page_slug ? 'active' : ''; ?>">
            <span class="monkeypay-nav-icon"><?php echo $item['icon']; ?></span>
            <span class="monkeypay-nav-label"><?php echo esc_html( $item['title'] ); ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
