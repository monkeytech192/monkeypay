<?php
/**
 * MonkeyPay Integrations Page
 *
 * Connection cards — same pattern as checkin-mkt192-wp Connections page
 * Each integration has: icon, name, status badge, toggle, settings button
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$has_woo      = class_exists( 'WooCommerce' );
$has_checkin   = class_exists( 'Checkin_MKT192_WP' ) || in_array( 'checkin-mkt192-wp/checkin-mkt192-wp.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true );
$wc_enabled   = MonkeyPay_Settings::get( 'wc_enabled', '0' ) === '1';
$checkin_on   = MonkeyPay_Settings::get( 'checkin_bridge', '0' ) === '1';

$integrations = [
    'woocommerce' => [
        'title'       => 'WooCommerce',
        'description' => __( 'Thanh toán QR tại trang checkout WooCommerce', 'monkeypay' ),
        'icon'        => '<svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
        'color'       => '#7B61FF',
        'installed'   => $has_woo,
        'enabled'     => $has_woo && $wc_enabled,
        'option_key'  => 'monkeypay_wc_enabled',
    ],
    'checkin' => [
        'title'       => 'Checkin MKT192',
        'description' => __( 'Tự động xác nhận hóa đơn qua webhook (thay cron polling)', 'monkeypay' ),
        'icon'        => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'color'       => '#06b6d4',
        'installed'   => $has_checkin,
        'enabled'     => $has_checkin && $checkin_on,
        'option_key'  => 'monkeypay_checkin_bridge',
    ],
];
?>

<div class="wrap monkeypay-admin-wrap">
    <?php include MONKEYPAY_PLUGIN_DIR . 'templates/partials/global-header.php'; ?>

    <div class="monkeypay-admin-page">

        <div class="monkeypay-page-header">
            <div>
                <h2 class="monkeypay-page-title">
                    <svg viewBox="0 0 24 24" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;color:var(--mp-primary);fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    <?php esc_html_e( 'Cài đặt Tích Hợp', 'monkeypay' ); ?>
                </h2>
                <p class="monkeypay-page-desc"><?php esc_html_e( 'Kết nối MonkeyPay với WooCommerce và các plugin trong hệ sinh thái Monkey', 'monkeypay' ); ?></p>
            </div>
        </div>

        <!-- Connections Grid -->
        <div class="monkeypay-connections-grid">
            <?php foreach ( $integrations as $key => $int ) : ?>
            <div class="monkeypay-connection-card <?php echo $int['enabled'] ? 'enabled' : ( $int['installed'] ? 'disabled' : 'not-installed' ); ?>"
                 data-integration="<?php echo esc_attr( $key ); ?>">
                <div class="monkeypay-connection-header">
                    <div class="monkeypay-connection-icon" style="background: <?php echo esc_attr( $int['color'] ); ?>">
                        <?php echo $int['icon']; ?>
                    </div>
                    <div class="monkeypay-connection-info">
                        <h3 class="monkeypay-connection-title"><?php echo esc_html( $int['title'] ); ?></h3>
                        <div class="monkeypay-connection-status">
                            <?php if ( ! $int['installed'] ) : ?>
                                <span class="monkeypay-badge monkeypay-badge-gray"><?php esc_html_e( 'Chưa cài đặt', 'monkeypay' ); ?></span>
                            <?php elseif ( $int['enabled'] ) : ?>
                                <span class="monkeypay-badge monkeypay-badge-success"><?php esc_html_e( 'Đã kết nối', 'monkeypay' ); ?></span>
                            <?php else : ?>
                                <span class="monkeypay-badge monkeypay-badge-gray"><?php esc_html_e( 'Chưa kết nối', 'monkeypay' ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="monkeypay-connection-body">
                    <p class="monkeypay-connection-desc"><?php echo esc_html( $int['description'] ); ?></p>
                    <?php if ( $int['installed'] ) : ?>
                    <div class="monkeypay-connection-actions">
                        <div class="monkeypay-connection-toggle-wrapper">
                            <label class="monkeypay-switch">
                                <input type="checkbox" class="monkeypay-integration-toggle"
                                       data-option="<?php echo esc_attr( $int['option_key'] ); ?>"
                                       <?php checked( $int['enabled'], true ); ?> />
                                <span class="monkeypay-switch__slider"></span>
                            </label>
                            <span class="monkeypay-toggle-status-text">
                                <?php echo $int['enabled'] ? esc_html__( 'Đang bật', 'monkeypay' ) : esc_html__( 'Đang tắt', 'monkeypay' ); ?>
                            </span>
                        </div>
                    </div>
                    <?php else : ?>
                    <div class="monkeypay-connection-actions">
                        <span class="monkeypay-not-installed-hint">
                            <?php printf( esc_html__( 'Cài đặt %s để sử dụng tích hợp này', 'monkeypay' ), '<strong>' . esc_html( $int['title'] ) . '</strong>' ); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>
