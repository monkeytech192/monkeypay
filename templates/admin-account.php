<?php
/**
 * MonkeyPay Account Page
 *
 * Organization profile with usage, API key, and logout.
 *
 * @package MonkeyPay
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap monkeypay-admin-wrap">
    <?php include MONKEYPAY_PLUGIN_DIR . 'templates/partials/global-header.php'; ?>

    <div class="monkeypay-admin-page">

        <div class="monkeypay-page-header">
            <div>
                <h2 class="monkeypay-page-title">
                    <svg viewBox="0 0 24 24" style="width:22px;height:22px;vertical-align:middle;margin-right:8px;color:var(--mp-primary);fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <?php esc_html_e( 'Tài Khoản', 'monkeypay' ); ?>
                </h2>
                <p class="monkeypay-page-desc"><?php esc_html_e( 'Thông tin tổ chức và quản lý tài khoản MonkeyPay.', 'monkeypay' ); ?></p>
            </div>
        </div>

        <!-- Account Card — full width, auto-expand, auto-load -->
        <div class="mp-account-container" id="monkeypay-account-card" data-loaded="false">

            <!-- Profile header -->
            <div class="mp-account-profile">
                <div class="mp-account-avatar" id="mp-account-avatar">
                    <span>$</span>
                </div>
                <div class="mp-account-profile-info">
                    <h3 class="mp-account-name" id="mp-account-name"><?php esc_html_e( 'Đang tải...', 'monkeypay' ); ?></h3>
                    <span class="mp-account-email" id="mp-account-email-display">—</span>
                </div>
                <span class="mp-account-plan-badge" id="mp-account-plan">—</span>
                <a href="<?php echo admin_url( 'admin.php?page=monkeypay-pricing' ); ?>" class="mp-account-upgrade-btn" id="mp-account-upgrade">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <polyline points="18 15 12 9 6 15"/>
                    </svg>
                    Nâng cấp
                </a>
            </div>

            <!-- Usage Stats Bar -->
            <div class="mp-account-stats">
                <div class="mp-account-stat-card">
                    <div class="mp-account-stat-icon mp-account-stat-icon--requests">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                    </div>
                    <div class="mp-account-stat-content">
                        <span class="mp-account-stat-label"><?php esc_html_e( 'Requests', 'monkeypay' ); ?></span>
                        <span class="mp-account-stat-value" id="mp-acc-requests">—</span>
                    </div>
                </div>
                <div class="mp-account-stat-card">
                    <div class="mp-account-stat-icon mp-account-stat-icon--gateways">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                    </div>
                    <div class="mp-account-stat-content">
                        <span class="mp-account-stat-label"><?php esc_html_e( 'Cổng thanh toán', 'monkeypay' ); ?></span>
                        <span class="mp-account-stat-value" id="mp-acc-gateways">—</span>
                    </div>
                </div>
                <div class="mp-account-stat-card">
                    <div class="mp-account-stat-icon mp-account-stat-icon--period">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="mp-account-stat-content">
                        <span class="mp-account-stat-label"><?php esc_html_e( 'Chu kỳ thanh toán', 'monkeypay' ); ?></span>
                        <span class="mp-account-stat-value" id="mp-acc-period">—</span>
                    </div>
                </div>
            </div>

            <!-- Detail Section -->
            <div class="mp-account-section">
                <h4 class="mp-account-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <?php esc_html_e( 'Thông tin tổ chức', 'monkeypay' ); ?>
                </h4>
                <div class="mp-account-detail-grid">
                    <div class="mp-account-field">
                        <label><?php esc_html_e( 'Tên tổ chức', 'monkeypay' ); ?></label>
                        <div class="mp-account-field-value" id="mp-acc-name">—</div>
                    </div>
                    <div class="mp-account-field">
                        <label><?php esc_html_e( 'Email', 'monkeypay' ); ?></label>
                        <div class="mp-account-field-value" id="mp-acc-email">—</div>
                    </div>
                    <div class="mp-account-field">
                        <label><?php esc_html_e( 'Số điện thoại', 'monkeypay' ); ?></label>
                        <div class="mp-account-field-value" id="mp-acc-phone">—</div>
                    </div>
                    <div class="mp-account-field">
                        <label><?php esc_html_e( 'Website', 'monkeypay' ); ?></label>
                        <div class="mp-account-field-value" id="mp-acc-site">—</div>
                    </div>
                </div>
            </div>

            <!-- API Key Section -->
            <div class="mp-account-section">
                <h4 class="mp-account-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                    </svg>
                    <?php esc_html_e( 'API Key', 'monkeypay' ); ?>
                </h4>
                <div class="mp-account-apikey-row">
                    <code class="mp-account-apikey-value" id="mp-acc-apikey" data-masked="true">••••••••</code>
                    <button type="button" class="mp-account-toggle-btn" id="mp-apikey-toggle" title="Hiện/Ẩn API Key">
                        <!-- Monkey eye closed (default) -->
                        <svg class="mp-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                        <!-- Monkey eye open -->
                        <svg class="mp-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                    <button type="button" class="mp-account-copy-btn" id="mp-apikey-copy" title="Copy API Key">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Change Password Section -->
            <div class="mp-account-section">
                <h4 class="mp-account-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <?php esc_html_e( 'Đổi mật khẩu', 'monkeypay' ); ?>
                </h4>
                <form id="mp-change-password-form" autocomplete="off">
                    <div class="mp-account-detail-grid">
                        <div class="mp-account-field">
                            <label><?php esc_html_e( 'Mật khẩu cũ', 'monkeypay' ); ?></label>
                            <div class="monkeypay-password-wrapper">
                                <input type="password" id="mp-chpw-old" placeholder="Nhập mật khẩu hiện tại" required>
                                <button type="button" class="monkeypay-pw-toggle" data-target="mp-chpw-old" tabindex="-1">
                                    <svg class="mp-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                    <svg class="mp-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="mp-account-field">
                            <label><?php esc_html_e( 'Mật khẩu mới', 'monkeypay' ); ?></label>
                            <div class="monkeypay-password-wrapper">
                                <input type="password" id="mp-chpw-new" placeholder="Tối thiểu 6 ký tự" minlength="6" required>
                                <button type="button" class="monkeypay-pw-toggle" data-target="mp-chpw-new" tabindex="-1">
                                    <svg class="mp-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                    <svg class="mp-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="mp-account-field">
                            <label><?php esc_html_e( 'Xác nhận mật khẩu mới', 'monkeypay' ); ?></label>
                            <div class="monkeypay-password-wrapper">
                                <input type="password" id="mp-chpw-confirm" placeholder="Nhập lại mật khẩu mới" required>
                            </div>
                        </div>
                    </div>
                    <div id="mp-chpw-msg" style="display:none;margin-top:12px;"></div>
                    <div style="margin-top:16px;">
                        <button type="submit" class="mp-account-chpw-btn" id="mp-chpw-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/>
                                <polyline points="7 3 7 8 15 8"/>
                            </svg>
                            <?php esc_html_e( 'Đổi mật khẩu', 'monkeypay' ); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Danger zone -->
            <div class="mp-account-danger-zone">
                <div class="mp-account-danger-info">
                    <strong><?php esc_html_e( 'Vùng nguy hiểm', 'monkeypay' ); ?></strong>
                    <p><?php esc_html_e( 'Đăng xuất sẽ xoá API Key khỏi WordPress. Bạn cần đăng nhập lại để sử dụng.', 'monkeypay' ); ?></p>
                </div>
                <button type="button" class="mp-account-logout-btn monkeypay-account-logout-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <?php esc_html_e( 'Đăng xuất', 'monkeypay' ); ?>
                </button>
            </div>

        </div>

    </div>
</div>
