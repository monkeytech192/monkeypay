<?php
/**
 * MonkeyPay Settings Page
 *
 * API configuration with organization creation flow.
 * - If no API key: show registration form
 * - If API key exists: show usage stats + settings
 *
 * @package MonkeyPay
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$api_url        = get_option( 'monkeypay_api_url', MONKEYPAY_API_URL );
$api_key        = get_option( 'monkeypay_api_key', '' );
$webhook_secret = get_option( 'monkeypay_webhook_secret', '' );
$admin_secret   = get_option( 'monkeypay_admin_secret', '' );
$has_api_key    = ! empty( $api_key );
?>

<div class="wrap monkeypay-admin-wrap">
    <?php include MONKEYPAY_PLUGIN_DIR . 'templates/partials/global-header.php'; ?>

    <div class="monkeypay-admin-page">

        <!-- ═══ API URL (always shown first) ═══ -->
        <div class="monkeypay-card">
            <div class="monkeypay-card__header">
                <div class="monkeypay-card__header-left">
                    <div class="monkeypay-card__icon">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    </div>
                    <h2><?php esc_html_e( 'Cấu Hình API', 'monkeypay' ); ?></h2>
                </div>
            </div>

            <form id="monkeypay-settings-form">

                <div class="monkeypay-field">
                    <label for="monkeypay_api_url"><?php esc_html_e( 'MonkeyPay Server URL', 'monkeypay' ); ?></label>
                    <input type="url" id="monkeypay_api_url" name="monkeypay_api_url"
                           value="<?php echo esc_attr( $api_url ); ?>"
                           placeholder="<?php echo esc_attr( MONKEYPAY_API_URL ); ?>" readonly />
                    <p class="monkeypay-field__hint"><?php esc_html_e( 'Server được cấu hình tự động. Không cần thay đổi.', 'monkeypay' ); ?></p>
                </div>

                <?php if ( $has_api_key ) : ?>
                <!-- ═══ Existing API Key display ═══ -->
                <div class="monkeypay-field">
                    <label for="monkeypay_api_key"><?php esc_html_e( 'API Key', 'monkeypay' ); ?></label>
                    <div class="monkeypay-password-wrapper">
                        <input type="password" id="monkeypay_api_key" name="monkeypay_api_key"
                               value="<?php echo esc_attr( $api_key ); ?>"
                               placeholder="mpk_xxxxxxxxx" readonly />
                        <button type="button" class="monkeypay-secret-toggle" data-target="monkeypay_api_key" aria-label="Toggle visibility">
                            <svg class="mp-eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="mp-eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    <p class="monkeypay-field__hint"><?php esc_html_e( 'API Key được tạo tự động khi đăng ký tổ chức', 'monkeypay' ); ?></p>
                </div>

                <div class="monkeypay-field">
                    <label for="monkeypay_webhook_secret"><?php esc_html_e( 'Webhook Secret', 'monkeypay' ); ?></label>
                    <div class="monkeypay-password-wrapper">
                        <input type="password" id="monkeypay_webhook_secret" name="monkeypay_webhook_secret"
                               value="<?php echo esc_attr( $webhook_secret ); ?>"
                               placeholder="mps_xxxxxxxxx" readonly />
                        <button type="button" class="monkeypay-secret-toggle" data-target="monkeypay_webhook_secret" aria-label="Toggle visibility">
                            <svg class="mp-eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="mp-eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    <p class="monkeypay-field__hint"><?php esc_html_e( 'Dùng để verify HMAC-SHA256 của incoming webhook', 'monkeypay' ); ?></p>
                </div>

                <div class="monkeypay-field">
                    <label for="monkeypay_admin_secret"><?php esc_html_e( 'Admin Secret', 'monkeypay' ); ?></label>
                    <div class="monkeypay-password-wrapper">
                        <input type="password" id="monkeypay_admin_secret" name="monkeypay_admin_secret"
                               value="<?php echo esc_attr( $admin_secret ); ?>"
                               placeholder="your-admin-secret" readonly />
                        <button type="button" class="monkeypay-secret-toggle" data-target="monkeypay_admin_secret" aria-label="Toggle visibility">
                            <svg class="mp-eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="mp-eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    <p class="monkeypay-field__hint"><?php esc_html_e( 'Được lấy tự động từ server khi đăng ký/đăng nhập. Dùng chung cho tất cả site cùng server.', 'monkeypay' ); ?></p>
                </div>
                <?php endif; ?>

                <button type="submit" class="monkeypay-btn monkeypay-btn--primary" id="monkeypay-save-btn">
                    <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <?php esc_html_e( 'Lưu Cài Đặt', 'monkeypay' ); ?>
                </button>
            </form>
        </div>



        <?php if ( $has_api_key ) : ?>
        <!-- ═══ Usage Stats Card ═══ -->
        <div class="monkeypay-card" id="monkeypay-usage-card">
            <div class="monkeypay-card__header">
                <div class="monkeypay-card__header-left">
                    <div class="monkeypay-card__icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </div>
                    <div>
                        <h2><?php esc_html_e( 'Thông Tin Gói & Sử Dụng', 'monkeypay' ); ?></h2>
                        <p class="monkeypay-card__subtitle" id="monkeypay-usage-subtitle"><?php esc_html_e( 'Đang tải...', 'monkeypay' ); ?></p>
                    </div>
                </div>
                <div class="monkeypay-card__header-right">
                    <span class="monkeypay-plan-pill" id="monkeypay-plan-pill">—</span>
                </div>
            </div>

            <div id="monkeypay-usage-content">
                <div class="monkeypay-usage-loading">
                    <div class="monkeypay-spinner"></div>
                    <span><?php esc_html_e( 'Đang tải thông tin...', 'monkeypay' ); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
