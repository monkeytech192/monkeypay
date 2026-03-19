<?php
/**
 * MonkeyPay Onboarding Gate
 *
 * Full-page register / login screen.
 * Shown when no API key is configured.
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$site_url    = home_url();
$admin_email = get_option( 'admin_email', '' );
?>

<div class="monkeypay-onboarding-wrapper">
    <div class="monkeypay-onboarding-card">
        <!-- Logo / Header -->
        <div class="monkeypay-onboarding-header">
            <div class="monkeypay-onboarding-logo">
                <span class="monkeypay-onboarding-logo__icon">$</span>
            </div>
            <h1 class="monkeypay-onboarding-header__title">MonkeyPay</h1>
            <p class="monkeypay-onboarding-header__desc">Cổng thanh toán chuyển khoản tự động</p>
        </div>

        <!-- Tab Switcher -->
        <div class="monkeypay-onboarding-tabs">
            <button type="button" class="monkeypay-onboarding-tab monkeypay-onboarding-tab--active" data-tab="register">Đăng Ký</button>
            <button type="button" class="monkeypay-onboarding-tab" data-tab="login">Đăng Nhập</button>
        </div>

        <!-- Messages -->
        <div id="monkeypay-onboarding-msg" style="display:none;"></div>

        <!-- Register Form -->
        <form id="monkeypay-register-form" class="monkeypay-onboarding-form monkeypay-onboarding-form--active" autocomplete="off">
            <div class="monkeypay-onboarding-field">
                <label for="mp-reg-name">Tên tổ chức / Cửa hàng <span class="required">*</span></label>
                <input type="text" id="mp-reg-name" placeholder="VD: Cửa hàng ABC" required>
            </div>

            <div class="monkeypay-onboarding-field-row">
                <div class="monkeypay-onboarding-field">
                    <label for="mp-reg-phone">Số điện thoại</label>
                    <input type="tel" id="mp-reg-phone" placeholder="0901 234 567">
                </div>
                <div class="monkeypay-onboarding-field">
                    <label for="mp-reg-email">Email <span class="required">*</span></label>
                    <input type="email" id="mp-reg-email" value="<?php echo esc_attr( $admin_email ); ?>" required>
                </div>
            </div>

            <div class="monkeypay-onboarding-field">
                <label for="mp-reg-password">Mật khẩu <span class="required">*</span></label>
                <div class="monkeypay-password-wrapper">
                    <input type="password" id="mp-reg-password" placeholder="Tối thiểu 6 ký tự" minlength="6" required>
                    <button type="button" class="monkeypay-pw-toggle" data-target="mp-reg-password" tabindex="-1">
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

            <div class="monkeypay-onboarding-field">
                <label for="mp-reg-site">Website</label>
                <input type="url" id="mp-reg-site" value="<?php echo esc_attr( $site_url ); ?>" readonly>
                <span class="monkeypay-onboarding-hint">Tự động lấy từ WordPress</span>
            </div>

            <div class="monkeypay-onboarding-plan-info">
                <div class="monkeypay-plan-badge">
                    <div>
                        <strong>Gói Free</strong>
                        <span>50 request/tháng · 1 cổng thanh toán</span>
                    </div>
                </div>
            </div>

            <button type="submit" id="mp-reg-btn" class="monkeypay-onboarding-btn monkeypay-onboarding-btn--primary">
                Tạo tổ chức — Dùng thử miễn phí
            </button>
        </form>

        <!-- Login Form -->
        <form id="monkeypay-login-form" class="monkeypay-onboarding-form" autocomplete="off">
            <div class="monkeypay-onboarding-field">
                <label for="mp-login-email">Email</label>
                <input type="email" id="mp-login-email" placeholder="Email đã đăng ký" required>
            </div>

            <div class="monkeypay-onboarding-field">
                <label for="mp-login-password">Mật khẩu</label>
                <div class="monkeypay-password-wrapper">
                    <input type="password" id="mp-login-password" placeholder="Mật khẩu của bạn" required>
                    <button type="button" class="monkeypay-pw-toggle" data-target="mp-login-password" tabindex="-1">
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

            <button type="submit" id="mp-login-btn" class="monkeypay-onboarding-btn monkeypay-onboarding-btn--primary">
                Đăng nhập
            </button>

            <div class="monkeypay-forgot-link">
                <a href="#" id="mp-forgot-toggle">Quên mật khẩu?</a>
            </div>
        </form>

        <!-- Forgot Password Form (hidden by default) -->
        <form id="monkeypay-forgot-form" class="monkeypay-onboarding-form" autocomplete="off">
            <p class="monkeypay-onboarding-field-desc" style="margin-bottom:12px;color:#94a3b8;font-size:13px;">
                Nhập email đã đăng ký. Hệ thống sẽ tạo mật khẩu mới cho bạn.
            </p>
            <div class="monkeypay-onboarding-field">
                <label for="mp-forgot-email">Email</label>
                <input type="email" id="mp-forgot-email" placeholder="Email đã đăng ký" required>
            </div>

            <div id="mp-forgot-result" style="display:none;"></div>

            <button type="submit" id="mp-forgot-btn" class="monkeypay-onboarding-btn monkeypay-onboarding-btn--primary">
                Đặt lại mật khẩu
            </button>
            <div class="monkeypay-forgot-link">
                <a href="#" id="mp-forgot-back">← Quay lại đăng nhập</a>
            </div>
        </form>

        <!-- Footer -->
        <div class="monkeypay-onboarding-footer">
            <span>© <?php echo date('Y'); ?> MonkeyPay by Monkey Tech 192</span>
        </div>
    </div>
</div>
