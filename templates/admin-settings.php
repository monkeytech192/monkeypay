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

        <!-- ═══ Display Settings Card ═══ -->
        <div class="monkeypay-card" id="monkeypay-display-card">
            <div class="monkeypay-card__header">
                <div class="monkeypay-card__header-left">
                    <div class="monkeypay-card__icon monkeypay-card__icon--display">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 data-i18n="display_settings"><?php esc_html_e( 'Display Settings', 'monkeypay' ); ?></h2>
                        <p class="monkeypay-card__subtitle" data-i18n="display_settings_desc"><?php esc_html_e( 'Configure timezone, language, and appearance', 'monkeypay' ); ?></p>
                    </div>
                </div>
            </div>

            <div class="monkeypay-display-grid">
                <!-- Timezone — Modern Custom Dropdown -->
                <div class="monkeypay-field">
                    <label for="monkeypay_timezone" data-i18n="timezone"><?php esc_html_e( 'Timezone', 'monkeypay' ); ?></label>
                    <?php $tz = get_option( 'monkeypay_timezone', 'Asia/Ho_Chi_Minh' ); ?>
                    <input type="hidden" id="monkeypay_timezone" value="<?php echo esc_attr( $tz ); ?>">
                    <?php
                    $tz_options = [
                        'Asia/Ho_Chi_Minh'    => 'UTC+7 — Ho Chi Minh',
                        'Asia/Bangkok'        => 'UTC+7 — Bangkok',
                        'Asia/Singapore'      => 'UTC+8 — Singapore',
                        'Asia/Tokyo'          => 'UTC+9 — Tokyo',
                        'Asia/Seoul'          => 'UTC+9 — Seoul',
                        'America/New_York'    => 'UTC-5 — New York',
                        'America/Los_Angeles' => 'UTC-8 — Los Angeles',
                        'Europe/London'       => 'UTC+0 — London',
                        'Europe/Berlin'       => 'UTC+1 — Berlin',
                        'Australia/Sydney'    => 'UTC+10 — Sydney',
                    ];
                    $tz_label = $tz_options[ $tz ] ?? $tz;
                    ?>
                    <div class="mp-tz-dropdown" id="mp-tz-dropdown">
                        <button type="button" class="mp-tz-dropdown__trigger" id="mp-tz-trigger">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                            <span class="mp-tz-dropdown__value"><?php echo esc_html( $tz_label ); ?></span>
                            <svg class="mp-tz-dropdown__chevron" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="mp-tz-dropdown__panel" id="mp-tz-panel">
                            <input type="text" class="mp-tz-dropdown__search" id="mp-tz-search" placeholder="Search..." autocomplete="off">
                            <div class="mp-tz-dropdown__list" id="mp-tz-list">
                                <?php foreach ( $tz_options as $val => $lbl ) : ?>
                                <button type="button" class="mp-tz-dropdown__option<?php echo $val === $tz ? ' mp-tz-dropdown__option--active' : ''; ?>" data-value="<?php echo esc_attr( $val ); ?>">
                                    <?php echo esc_html( $lbl ); ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Language — Pill Toggle with SVG Flags -->
                <div class="monkeypay-field">
                    <label data-i18n="language"><?php esc_html_e( 'Language', 'monkeypay' ); ?></label>
                    <?php $lang = get_option( 'monkeypay_language', 'vi' ); ?>
                    <div class="mp-settings-pills" data-mp-field="monkeypay_language">
                        <button type="button" class="mp-settings-pill<?php echo $lang === 'vi' ? ' mp-settings-pill--active' : ''; ?>"
                                data-mp-value="vi">
                            <span class="mp-settings-pill__icon"><svg viewBox="0 0 48 32" width="20" height="14"><rect width="48" height="32" rx="3" fill="#DA251D"/><polygon points="24,4 27.5,14.5 38.5,14.5 29.5,20.5 33,31 24,24 15,31 18.5,20.5 9.5,14.5 20.5,14.5" fill="#FFFF00"/></svg></span>
                            <span class="mp-settings-pill__label">Tiếng Việt</span>
                        </button>
                        <button type="button" class="mp-settings-pill<?php echo $lang === 'en' ? ' mp-settings-pill--active' : ''; ?>"
                                data-mp-value="en">
                            <span class="mp-settings-pill__icon"><svg viewBox="0 0 60 30" width="20" height="10"><clipPath id="s"><rect width="60" height="30" rx="3"/></clipPath><g clip-path="url(#s)"><rect width="60" height="30" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#C8102E" stroke-width="4" clip-path="url(#s)"/><path d="M30,0 V30 M0,15 H60" stroke="#fff" stroke-width="10"/><path d="M30,0 V30 M0,15 H60" stroke="#C8102E" stroke-width="6"/></g></svg></span>
                            <span class="mp-settings-pill__label">English</span>
                        </button>
                    </div>
                </div>

                <!-- Appearance — Pill Toggle -->
                <div class="monkeypay-field">
                    <label data-i18n="dark_mode"><?php esc_html_e( 'Appearance', 'monkeypay' ); ?></label>
                    <?php $dm = get_option( 'monkeypay_dark_mode', 'light' ); ?>
                    <div class="mp-settings-pills" data-mp-field="monkeypay_dark_mode">
                        <button type="button" class="mp-settings-pill<?php echo $dm === 'light' ? ' mp-settings-pill--active' : ''; ?>"
                                data-mp-value="light">
                            <span class="mp-settings-pill__icon">☀️</span>
                            <span class="mp-settings-pill__label">Light</span>
                        </button>
                        <button type="button" class="mp-settings-pill<?php echo $dm === 'dark' ? ' mp-settings-pill--active' : ''; ?>"
                                data-mp-value="dark">
                            <span class="mp-settings-pill__icon">🌙</span>
                            <span class="mp-settings-pill__label">Dark</span>
                        </button>
                        <button type="button" class="mp-settings-pill<?php echo $dm === 'auto' ? ' mp-settings-pill--active' : ''; ?>"
                                data-mp-value="auto">
                            <span class="mp-settings-pill__icon">🔄</span>
                            <span class="mp-settings-pill__label">Auto</span>
                        </button>
                    </div>
                </div>
            </div>

            <div style="padding: 0 24px 24px;">
                <button type="button" class="monkeypay-btn monkeypay-btn--primary" id="monkeypay-save-display">
                    <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span data-i18n="save_settings"><?php esc_html_e( 'Save Settings', 'monkeypay' ); ?></span>
                </button>
            </div>
        </div>

    </div>
</div>
