/**
 * MonkeyPay Admin — Onboarding Gate
 *
 * Tab switching, register, login, forgot password.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

(function ($) {
    'use strict';

    const MP = window.MonkeyPay;

    // ─── Message Helper ─────────────────────────────

    function showMsg(type, text) {
        const msgDiv = document.getElementById('monkeypay-onboarding-msg');
        if (!msgDiv) return;
        msgDiv.className = type === 'error'
            ? 'monkeypay-onboarding-msg--error'
            : 'monkeypay-onboarding-msg--success';
        msgDiv.textContent = text;
        msgDiv.style.display = 'block';
    }

    // ─── Register ───────────────────────────────────

    async function handleRegister(e) {
        e.preventDefault();

        const btn = document.getElementById('mp-reg-btn');
        const name = document.getElementById('mp-reg-name').value.trim();
        const phone = document.getElementById('mp-reg-phone')?.value?.trim() || '';
        const email = document.getElementById('mp-reg-email').value.trim();
        const password = document.getElementById('mp-reg-password').value;

        if (!name) { showMsg('error', 'Vui lòng nhập tên tổ chức'); return; }
        if (!email) { showMsg('error', 'Vui lòng nhập email'); return; }
        if (!password || password.length < 6) { showMsg('error', 'Mật khẩu phải có ít nhất 6 ký tự'); return; }

        btn.disabled = true;
        btn.textContent = 'Đang tạo tổ chức...';

        try {
            const res = await fetch(`${MP.restUrl}register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': MP.nonce,
                },
                body: JSON.stringify({ name, phone, email, password }),
            });

            const data = await res.json();

            if (data.success) {
                showMsg('success', `Tạo tổ chức "${name}" thành công! Đang chuyển hướng...`);
                setTimeout(() => { window.location.reload(); }, 1500);
            } else {
                showMsg('error', data.message || 'Đăng ký thất bại');
                btn.disabled = false;
                btn.textContent = 'Tạo tổ chức — Dùng thử miễn phí';
            }
        } catch (err) {
            showMsg('error', 'Lỗi kết nối server: ' + err.message);
            btn.disabled = false;
            btn.textContent = 'Tạo tổ chức — Dùng thử miễn phí';
        }
    }

    // ─── Login ──────────────────────────────────────

    async function handleLogin(e) {
        e.preventDefault();

        const btn = document.getElementById('mp-login-btn');
        const email = document.getElementById('mp-login-email').value.trim();
        const password = document.getElementById('mp-login-password').value;

        if (!email || !password) { showMsg('error', 'Vui lòng nhập email và mật khẩu'); return; }

        btn.disabled = true;
        btn.textContent = 'Đang đăng nhập...';

        try {
            const res = await fetch(`${MP.restUrl}login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': MP.nonce,
                },
                body: JSON.stringify({ email, password }),
            });

            const data = await res.json();

            if (data.success) {
                showMsg('success', data.data?.message || 'Đăng nhập thành công! Đang chuyển hướng...');
                setTimeout(() => { window.location.reload(); }, 1500);
            } else {
                showMsg('error', data.message || 'Đăng nhập thất bại');
                btn.disabled = false;
                btn.textContent = 'Đăng nhập';
            }
        } catch (err) {
            showMsg('error', 'Lỗi kết nối server: ' + err.message);
            btn.disabled = false;
            btn.textContent = 'Đăng nhập';
        }
    }

    // ─── Forgot Password ────────────────────────────

    async function handleForgotPassword(e) {
        e.preventDefault();

        const btn = document.getElementById('mp-forgot-btn');
        const email = document.getElementById('mp-forgot-email').value.trim();
        const resultDiv = document.getElementById('mp-forgot-result');

        if (!email) { showMsg('error', 'Vui lòng nhập email'); return; }

        btn.disabled = true;
        btn.textContent = 'Đang xử lý...';

        try {
            const res = await fetch(`${MP.restUrl}forgot-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': MP.nonce,
                },
                body: JSON.stringify({ email }),
            });

            const data = await res.json();

            if (data.success) {
                const newPw = data.new_password || '';
                if (resultDiv) {
                    resultDiv.style.display = 'block';
                    resultDiv.className = 'monkeypay-onboarding-msg--success';
                    resultDiv.innerHTML = `<strong>Mật khẩu mới:</strong> <code style="user-select:all;padding:4px 8px;background:rgba(16,185,129,.15);border-radius:4px;font-size:15px;letter-spacing:1px;">${newPw}</code><br><small style="color:#94a3b8;">Vui lòng lưu lại và sử dụng mật khẩu này để đăng nhập.</small>`;
                }
                showMsg('success', data.message || 'Đã đặt lại mật khẩu!');
            } else {
                showMsg('error', data.message || 'Không thể đặt lại mật khẩu');
            }
        } catch (err) {
            showMsg('error', 'Lỗi kết nối server: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Đặt lại mật khẩu';
        }
    }

    // ─── Init ───────────────────────────────────────

    function initOnboarding() {
        const wrapper = document.querySelector('.monkeypay-onboarding-wrapper');
        if (!wrapper) return;

        // Tab switching
        const tabs = wrapper.querySelectorAll('.monkeypay-onboarding-tab');
        const registerForm = document.getElementById('monkeypay-register-form');
        const loginForm = document.getElementById('monkeypay-login-form');
        const msgDiv = document.getElementById('monkeypay-onboarding-msg');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;

                // Update tabs
                tabs.forEach(t => t.classList.remove('monkeypay-onboarding-tab--active'));
                tab.classList.add('monkeypay-onboarding-tab--active');

                // Switch forms
                const forgotForm = document.getElementById('monkeypay-forgot-form');
                if (target === 'register') {
                    registerForm.classList.add('monkeypay-onboarding-form--active');
                    loginForm.classList.remove('monkeypay-onboarding-form--active');
                    if (forgotForm) forgotForm.classList.remove('monkeypay-onboarding-form--active');
                } else {
                    loginForm.classList.add('monkeypay-onboarding-form--active');
                    registerForm.classList.remove('monkeypay-onboarding-form--active');
                    if (forgotForm) forgotForm.classList.remove('monkeypay-onboarding-form--active');
                }

                // Clear messages
                if (msgDiv) {
                    msgDiv.style.display = 'none';
                    msgDiv.innerHTML = '';
                }
            });
        });

        // Register submit
        if (registerForm) registerForm.addEventListener('submit', handleRegister);

        // Login submit
        if (loginForm) loginForm.addEventListener('submit', handleLogin);

        // Forgot password toggle
        const forgotToggle = document.getElementById('mp-forgot-toggle');
        const forgotForm = document.getElementById('monkeypay-forgot-form');
        if (forgotToggle && forgotForm) {
            forgotToggle.addEventListener('click', (e) => {
                e.preventDefault();
                loginForm.classList.remove('monkeypay-onboarding-form--active');
                forgotForm.classList.add('monkeypay-onboarding-form--active');
                if (msgDiv) { msgDiv.style.display = 'none'; }
            });
        }

        // Back to login
        const forgotBack = document.getElementById('mp-forgot-back');
        if (forgotBack && forgotForm) {
            forgotBack.addEventListener('click', (e) => {
                e.preventDefault();
                forgotForm.classList.remove('monkeypay-onboarding-form--active');
                loginForm.classList.add('monkeypay-onboarding-form--active');
                const forgotResult = document.getElementById('mp-forgot-result');
                if (forgotResult) { forgotResult.style.display = 'none'; forgotResult.innerHTML = ''; }
            });
        }

        // Forgot form submit
        if (forgotForm) forgotForm.addEventListener('submit', handleForgotPassword);

        // Password visibility toggle
        const pwToggles = wrapper.querySelectorAll('.monkeypay-pw-toggle');
        pwToggles.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.querySelector('.mp-eye-closed').style.display = isPassword ? 'none' : '';
                btn.querySelector('.mp-eye-open').style.display = isPassword ? '' : 'none';
            });
        });
    }

    // ─── Boot ───────────────────────────────────────

    $(document).ready(function () {
        initOnboarding();
    });

})(jQuery);
