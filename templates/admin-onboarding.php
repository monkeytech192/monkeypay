<?php
/**
 * MonkeyPay Onboarding Gate
 *
 * Full-page register / login screen with Google OAuth and 2FA support.
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

        <!-- ═══════════════════════════════════════════ -->
        <!-- Register Form                              -->
        <!-- ═══════════════════════════════════════════ -->
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
                    <input type="password" id="mp-reg-password" placeholder="Tối thiểu 8 ký tự (chữ + số)" minlength="8" required>
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
                <span class="monkeypay-onboarding-hint monkeypay-pw-strength" id="mp-reg-pw-strength"></span>
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

            <!-- Google OAuth Divider -->
            <div class="monkeypay-oauth-divider">
                <span>hoặc</span>
            </div>

            <!-- Google Sign-In Button -->
            <button type="button" id="mp-google-register-btn" class="monkeypay-google-btn">
                <svg class="monkeypay-google-icon" viewBox="0 0 24 24" width="20" height="20">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 0 0 1 12c0 1.77.42 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                <span>Đăng ký bằng Google</span>
            </button>
        </form>

        <!-- ═══════════════════════════════════════════ -->
        <!-- Login Form                                 -->
        <!-- ═══════════════════════════════════════════ -->
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

            <!-- Google OAuth Divider -->
            <div class="monkeypay-oauth-divider">
                <span>hoặc</span>
            </div>

            <!-- Google Sign-In Button -->
            <button type="button" id="mp-google-login-btn" class="monkeypay-google-btn">
                <svg class="monkeypay-google-icon" viewBox="0 0 24 24" width="20" height="20">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 0 0 1 12c0 1.77.42 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                <span>Đăng nhập bằng Google</span>
            </button>
        </form>

        <!-- ═══════════════════════════════════════════ -->
        <!-- 2FA Verification Form (shown after login)  -->
        <!-- ═══════════════════════════════════════════ -->
        <form id="monkeypay-2fa-form" class="monkeypay-onboarding-form" autocomplete="off" style="display:none;">
            <div class="monkeypay-2fa-header">
                <div class="monkeypay-2fa-icon">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        <circle cx="12" cy="16" r="1"/>
                    </svg>
                </div>
                <h3>Xác thực 2 bước</h3>
                <p>Nhập mã 6 số từ ứng dụng xác thực của bạn</p>
            </div>

            <div class="monkeypay-onboarding-field">
                <div class="monkeypay-otp-inputs" id="mp-2fa-otp-group">
                    <input type="text" class="monkeypay-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="0" autofocus>
                    <input type="text" class="monkeypay-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1">
                    <input type="text" class="monkeypay-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2">
                    <span class="monkeypay-otp-separator">—</span>
                    <input type="text" class="monkeypay-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3">
                    <input type="text" class="monkeypay-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4">
                    <input type="text" class="monkeypay-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5">
                </div>
                <input type="hidden" id="mp-2fa-otp" name="otp_code">
                <input type="hidden" id="mp-2fa-temp-token" name="temp_token">
                <input type="hidden" id="mp-2fa-email" name="email_2fa">
            </div>

            <button type="submit" id="mp-2fa-btn" class="monkeypay-onboarding-btn monkeypay-onboarding-btn--primary" disabled>
                Xác nhận
            </button>

            <div class="monkeypay-forgot-link">
                <a href="#" id="mp-2fa-back">← Quay lại đăng nhập</a>
            </div>
        </form>

        <!-- ═══════════════════════════════════════════ -->
        <!-- Forgot Password Form                       -->
        <!-- ═══════════════════════════════════════════ -->
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
