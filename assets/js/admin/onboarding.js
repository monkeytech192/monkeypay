/**
 * MonkeyPay Admin — Onboarding Gate
 *
 * Tab switching, register, login, forgot password,
 * Google OAuth, 2FA OTP, password strength meter.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

(function ($) {
    'use strict';

    const MP = window.MonkeyPay;

    // ─── Cached DOM ────────────────────────────────
    let $wrapper, $msgDiv;
    let $registerForm, $loginForm, $forgotForm, $twofaForm;

    // ─── State ─────────────────────────────────────
    let twofaTempToken = '';
    let twofaEmail     = '';
    let pendingGoogleUser = null; // Deferred: prefill after init

    // ═══════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════

    function showMsg(type, text) {
        if (!$msgDiv) return;
        $msgDiv.className = type === 'error'
            ? 'monkeypay-onboarding-msg--error'
            : 'monkeypay-onboarding-msg--success';
        $msgDiv.textContent = text;
        $msgDiv.style.display = 'block';
        // Auto-hide success after 6s
        if (type === 'success') {
            setTimeout(() => { if ($msgDiv) $msgDiv.style.display = 'none'; }, 6000);
        }
    }

    function hideMsg() {
        if ($msgDiv) {
            $msgDiv.style.display = 'none';
            $msgDiv.innerHTML = '';
        }
    }

    function hideAllForms() {
        [$registerForm, $loginForm, $forgotForm, $twofaForm].forEach(f => {
            if (f) {
                f.classList.remove('monkeypay-onboarding-form--active');
                f.style.display = '';
            }
        });
    }

    function showForm(form) {
        hideAllForms();
        if (form) {
            form.classList.add('monkeypay-onboarding-form--active');
            form.style.display = '';
        }
    }

    // ═══════════════════════════════════════════════════
    // Password Strength
    // ═══════════════════════════════════════════════════

    function getPasswordStrength(pw) {
        if (!pw) return { label: '', cls: '', score: 0 };
        let score = 0;
        if (pw.length >= 8)  score++;
        if (pw.length >= 12) score++;
        if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^a-zA-Z0-9]/.test(pw)) score++;

        if (score <= 1) return { label: 'Yếu', cls: 'pw-weak', score };
        if (score <= 2) return { label: 'Trung bình', cls: 'pw-medium', score };
        if (score <= 3) return { label: 'Khá', cls: 'pw-good', score };
        return { label: 'Mạnh', cls: 'pw-strong', score };
    }

    function updatePasswordStrength(inputId, hintId) {
        const input = document.getElementById(inputId);
        const hint  = document.getElementById(hintId);
        if (!input || !hint) return;

        input.addEventListener('input', () => {
            const { label, cls } = getPasswordStrength(input.value);
            hint.textContent = label ? `Độ mạnh: ${label}` : '';
            hint.className = 'monkeypay-onboarding-hint monkeypay-pw-strength ' + (cls || '');
        });
    }

    // ═══════════════════════════════════════════════════
    // Register
    // ═══════════════════════════════════════════════════

    async function handleRegister(e) {
        e.preventDefault();

        const form     = document.getElementById('monkeypay-register-form');
        const btn      = document.getElementById('mp-reg-btn');
        const name     = document.getElementById('mp-reg-name').value.trim();
        const phone    = (document.getElementById('mp-reg-phone')?.value || '').trim();
        const email    = document.getElementById('mp-reg-email').value.trim();
        const password = document.getElementById('mp-reg-password')?.value || '';

        // Check if this is a Google-auth registration
        const isGoogleAuth = form?.dataset.authProvider === 'google';
        const googleId     = form?.querySelector('input[name="google_id"]')?.value || '';

        if (!name)  { showMsg('error', 'Vui lòng nhập tên tổ chức'); return; }
        if (!email) { showMsg('error', 'Vui lòng nhập email'); return; }

        // Password required only for non-Google registration
        if (!isGoogleAuth) {
            if (!password || password.length < 8) {
                showMsg('error', 'Mật khẩu phải có ít nhất 8 ký tự');
                return;
            }
            if (!/[a-zA-Z]/.test(password) || !/[0-9]/.test(password)) {
                showMsg('error', 'Mật khẩu phải chứa ít nhất 1 chữ cái và 1 chữ số');
                return;
            }
        }

        btn.disabled = true;
        btn.textContent = 'Đang tạo tổ chức...';

        // Build request body
        const body = { name, phone, email };
        if (isGoogleAuth) {
            body.google_id     = googleId;
            body.auth_provider = 'google';
        } else {
            body.password = password;
        }

        try {
            const res = await fetch(`${MP.restUrl}register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': MP.nonce,
                },
                body: JSON.stringify(body),
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

    // ═══════════════════════════════════════════════════
    // Login
    // ═══════════════════════════════════════════════════

    async function handleLogin(e) {
        e.preventDefault();

        const btn      = document.getElementById('mp-login-btn');
        const email    = document.getElementById('mp-login-email').value.trim();
        const password = document.getElementById('mp-login-password').value;

        if (!email || !password) {
            showMsg('error', 'Vui lòng nhập email và mật khẩu');
            return;
        }

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
                // Check 2FA required
                if (data.requires_2fa) {
                    twofaTempToken = data.temp_token || '';
                    twofaEmail     = email;
                    show2FAForm();
                    return;
                }

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

    // ═══════════════════════════════════════════════════
    // Forgot Password
    // ═══════════════════════════════════════════════════

    async function handleForgotPassword(e) {
        e.preventDefault();

        const btn       = document.getElementById('mp-forgot-btn');
        const email     = document.getElementById('mp-forgot-email').value.trim();
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

    /**
     * Handle Google button click — open popup window for OAuth.
     * No full-page redirect. Popup closes itself after callback.
     */
    function handleGoogleBtnClick() {
        // OAuth handler is on monkeytech192.vn (same domain as Google redirect URI)
        const oauthBaseUrl = 'https://monkeytech192.vn/oauth/google';

        // Build callback URL: WP admin page with popup flag
        const callbackUrl = MP.adminUrl + 'admin.php?page=monkeypay&mp_google_callback=1&mp_popup=1';

        // Random state for CSRF protection
        const state = Math.random().toString(36).substring(2, 15);
        sessionStorage.setItem('mp_oauth_state', state);

        // Build redirect URL
        const redirectUrl = oauthBaseUrl
            + '/redirect'
            + '?callback_url=' + encodeURIComponent(callbackUrl)
            + '&state=' + encodeURIComponent(state);

        // Open popup window centered on screen
        const w = 500, h = 600;
        const left = window.screenX + (window.outerWidth - w) / 2;
        const top  = window.screenY + (window.outerHeight - h) / 2;
        const popup = window.open(
            redirectUrl,
            'mp_google_oauth',
            `width=${w},height=${h},left=${left},top=${top},scrollbars=yes,resizable=yes`
        );

        if (!popup || popup.closed) {
            showMsg('error', 'Trình duyệt đã chặn popup. Vui lòng cho phép popup và thử lại.');
            return;
        }

        showMsg('success', 'Đang chờ xác thực Google...');

        // Listen for postMessage from popup
        function onMessage(event) {
            // Accept message from any origin since callback goes through different domains
            if (!event.data || event.data.type !== 'mp_google_oauth_callback') return;

            window.removeEventListener('message', onMessage);
            const params = event.data.params || {};

            // Close popup
            if (popup && !popup.closed) popup.close();

            // Process callback data
            processGoogleCallback(params);
        }
        window.addEventListener('message', onMessage);

        // Fallback: Poll popup for URL changes (in case postMessage fails due to cross-origin)
        const pollTimer = setInterval(() => {
            if (popup.closed) {
                clearInterval(pollTimer);
                window.removeEventListener('message', onMessage);
                // Check if we already processed via postMessage
                if (!sessionStorage.getItem('mp_oauth_processed')) {
                    hideMsg();
                }
                sessionStorage.removeItem('mp_oauth_processed');
            }
        }, 500);
    }

    /**
     * Process Google OAuth callback data (from postMessage or URL params).
     */
    function processGoogleCallback(params) {
        sessionStorage.setItem('mp_oauth_processed', '1');

        // Handle error
        if (params.mp_error) {
            showMsg('error', decodeURIComponent(params.mp_error));
            return;
        }

        if (!params.mp_id_token) {
            showMsg('error', 'Không nhận được token từ Google.');
            return;
        }

        const idToken    = params.mp_id_token;
        const state      = params.state;
        const savedState = sessionStorage.getItem('mp_oauth_state');

        // CSRF check
        if (state && savedState && state !== savedState) {
            showMsg('error', 'Phiên đăng nhập không hợp lệ. Vui lòng thử lại.');
            return;
        }

        sessionStorage.removeItem('mp_oauth_state');

        // Forward id_token to WP REST API for verification
        showMsg('success', 'Đang xác thực với Google...');

        fetch(`${MP.restUrl}google-auth`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': MP.nonce,
            },
            body: JSON.stringify({ id_token: idToken }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // ── Already linked merchant → go to dashboard ──
                if (data.already_linked) {
                    showMsg('success', 'Đăng nhập Google thành công! Đang chuyển hướng...');
                    setTimeout(() => {
                        window.location.href = MP.adminUrl + 'admin.php?page=monkeypay';
                    }, 1500);
                    return;
                }

                // ── New merchant → pre-fill register form ──
                if (data.needs_registration && data.google_user) {
                    showMsg('success', data.message || 'Vui lòng hoàn tất thông tin tổ chức.');
                    prefillGoogleRegister(data.google_user);
                    return;
                }

                // Fallback: reload
                showMsg('success', 'Đăng nhập Google thành công!');
                setTimeout(() => {
                    window.location.href = MP.adminUrl + 'admin.php?page=monkeypay';
                }, 1500);
            } else {
                showMsg('error', data.message || 'Đăng nhập Google thất bại');
            }
        })
        .catch(err => {
            showMsg('error', 'Lỗi kết nối: ' + err.message);
        });
    }

    /**
     * Check if we're returning from a Google OAuth redirect (fallback for non-popup).
     * callback.php redirects back with ?mp_id_token=...
     */
    function checkGoogleCallback() {
        const params = new URLSearchParams(window.location.search);

        if (!params.has('mp_google_callback')) {
            return false;
        }

        // If this is a popup window, send postMessage to opener and close
        if (params.has('mp_popup') && window.opener) {
            const msgData = {
                type: 'mp_google_oauth_callback',
                params: {}
            };
            // Collect relevant params
            ['mp_id_token', 'mp_token', 'mp_merchant_id', 'mp_error', 'state'].forEach(key => {
                if (params.has(key)) msgData.params[key] = params.get(key);
            });
            window.opener.postMessage(msgData, '*');
            // Show brief message then close
            document.body.innerHTML = '<div style="text-align:center;padding:40px;font-family:sans-serif;"><p>Xác thực thành công! Đang đóng...</p></div>';
            setTimeout(() => window.close(), 1000);
            return true;
        }

        // ── Fallback: handle as full-page redirect (same as before) ──
        if (params.has('mp_error')) {
            showMsg('error', decodeURIComponent(params.get('mp_error')));
            cleanCallbackUrl();
            return true;
        }

        if (!params.has('mp_id_token')) {
            return false;
        }

        // Process directly (non-popup fallback)
        const callbackParams = {};
        ['mp_id_token', 'mp_token', 'mp_merchant_id', 'mp_error', 'state'].forEach(key => {
            if (params.has(key)) callbackParams[key] = params.get(key);
        });
        cleanCallbackUrl();
        processGoogleCallback(callbackParams);

        return true;
    }

    /**
     * Pre-fill register form with Google user data.
     * Shows a success banner at step 1, user clicks "Tiếp tục" to see the form.
     * Hides Google buttons + password field.
     */
    function prefillGoogleRegister(googleUser) {
        cleanCallbackUrl();

        // Switch to register tab
        const registerTab = document.querySelector('[data-tab="register"]');
        if (registerTab) {
            registerTab.click();
        }

        // Hide Google OAuth buttons + dividers in BOTH forms
        const googleBtns = document.querySelectorAll('.monkeypay-google-btn');
        const oauthDividers = document.querySelectorAll('.monkeypay-oauth-divider');
        googleBtns.forEach(b => { b.style.display = 'none'; });
        oauthDividers.forEach(d => { d.style.display = 'none'; });

        // Hide the register form content initially, show success banner
        const regForm = document.getElementById('monkeypay-register-form');
        if (!regForm) return;

        /**
         * Reset form back to initial state (before Google prefill).
         * Re-shows Google buttons, password field, clears auth data.
         */
        function resetGooglePrefill() {
            // Remove banner + back button if they exist
            const existingBanner = document.getElementById('mp-google-success-banner');
            const existingBack   = document.getElementById('mp-google-back-row');
            if (existingBanner) existingBanner.remove();
            if (existingBack) existingBack.remove();

            // Show all form children back
            formChildren.forEach(child => {
                child.style.display = child.dataset.originalDisplay || '';
                delete child.dataset.originalDisplay;
            });

            // Re-show Google buttons + dividers
            googleBtns.forEach(b => { b.style.display = ''; });
            oauthDividers.forEach(d => { d.style.display = ''; });

            // Reset email field
            const emailField = document.getElementById('mp-reg-email');
            if (emailField) {
                emailField.value    = '';
                emailField.readOnly = false;
                emailField.style.opacity = '';
                emailField.style.cursor  = '';
            }

            // Reset name field
            const nameField = document.getElementById('mp-reg-name');
            if (nameField) nameField.value = '';

            // Re-show password field + restore validation
            const pwField = document.getElementById('mp-reg-password');
            if (pwField) {
                const pwContainer = pwField.closest('.monkeypay-onboarding-field');
                if (pwContainer) pwContainer.style.display = '';
                pwField.setAttribute('required', '');
                pwField.setAttribute('minlength', '8');
                pwField.value = '';
            }
            const pwStrength = document.getElementById('mp-reg-pw-strength');
            if (pwStrength) { pwStrength.style.display = ''; pwStrength.textContent = ''; }

            // Remove google_id hidden input + auth provider flag
            const form = document.getElementById('monkeypay-register-form');
            if (form) {
                const hiddenGid = form.querySelector('input[name="google_id"]');
                if (hiddenGid) hiddenGid.remove();
                delete form.dataset.authProvider;
            }

            hideMsg();
        }

        // Hide all children of register form temporarily
        const formChildren = Array.from(regForm.children);
        formChildren.forEach(child => {
            child.dataset.originalDisplay = child.style.display || '';
            child.style.display = 'none';
        });

        // Create step-1 success banner
        const banner = document.createElement('div');
        banner.id = 'mp-google-success-banner';
        banner.innerHTML = `
            <div style="text-align:center; padding: 24px 0;">
                <div style="width:56px; height:56px; border-radius:50%; background:linear-gradient(135deg,#34a853,#10b981); margin:0 auto 16px; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h3 style="margin:0 0 8px; font-size:18px; font-weight:600; color:#1e293b;">Xác thực Google thành công!</h3>
                <p style="margin:0 0 4px; color:#64748b; font-size:14px;">Tài khoản: <strong style="color:#334155;">${googleUser.email || ''}</strong></p>
                <p style="margin:0 0 20px; color:#94a3b8; font-size:13px;">Nhấn "Tiếp tục" để hoàn tất thông tin tổ chức</p>
                <button type="button" id="mp-google-continue-btn" class="monkeypay-onboarding-btn monkeypay-onboarding-btn--primary" style="max-width:280px; margin:0 auto;">
                    Tiếp tục đăng ký →
                </button>
                <div style="margin-top:12px;">
                    <a href="#" id="mp-google-change-account-banner" style="color:#64748b; font-size:13px; text-decoration:underline; cursor:pointer;">
                        ← Đổi tài khoản Google
                    </a>
                </div>
            </div>
        `;
        regForm.prepend(banner);

        // "Đổi tài khoản" on banner
        document.getElementById('mp-google-change-account-banner').addEventListener('click', (e) => {
            e.preventDefault();
            resetGooglePrefill();
        });

        // "Tiếp tục" button click → show the form fields
        document.getElementById('mp-google-continue-btn').addEventListener('click', () => {
            // Remove banner
            banner.remove();

            // Show form children back
            formChildren.forEach(child => {
                child.style.display = child.dataset.originalDisplay || '';
                delete child.dataset.originalDisplay;
            });

            // Pre-fill fields
            const nameField  = document.getElementById('mp-reg-name');
            const emailField = document.getElementById('mp-reg-email');
            const pwField    = document.getElementById('mp-reg-password');
            const pwStrength = document.getElementById('mp-reg-pw-strength');

            if (emailField) {
                emailField.value    = googleUser.email || '';
                emailField.readOnly = true;
                emailField.style.opacity = '0.7';
                emailField.style.cursor  = 'not-allowed';
            }

            if (nameField && googleUser.name) {
                nameField.value = googleUser.name;
                nameField.focus();
            }

            // Hide password field for Google auth
            // IMPORTANT: also remove 'required' + 'minlength' to prevent HTML5 validation blocking submit
            if (pwField) {
                const pwContainer = pwField.closest('.monkeypay-onboarding-field');
                if (pwContainer) pwContainer.style.display = 'none';
                pwField.removeAttribute('required');
                pwField.removeAttribute('minlength');
            }
            if (pwStrength) pwStrength.style.display = 'none';

            // Keep Google buttons + dividers hidden
            googleBtns.forEach(b => { b.style.display = 'none'; });
            oauthDividers.forEach(d => { d.style.display = 'none'; });

            // Store google_id for registration
            const form = document.getElementById('monkeypay-register-form');
            if (form) {
                const existing = form.querySelector('input[name="google_id"]');
                if (existing) existing.remove();

                const hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'google_id';
                hidden.value = googleUser.google_id || '';
                form.appendChild(hidden);

                form.dataset.authProvider = 'google';
            }

            // Add "Đổi tài khoản" back button above submit
            const backRow = document.createElement('div');
            backRow.id = 'mp-google-back-row';
            backRow.style.cssText = 'text-align:center; margin-bottom:12px;';
            backRow.innerHTML = `
                <a href="#" id="mp-google-change-account-form" style="color:#64748b; font-size:13px; text-decoration:underline; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m0 0l7 7m-7-7l7-7"/></svg>
                    Đổi tài khoản Google
                </a>
            `;
            // Insert before the submit button
            const submitBtn = regForm.querySelector('.monkeypay-onboarding-btn--primary, button[type="submit"]');
            if (submitBtn) {
                submitBtn.parentNode.insertBefore(backRow, submitBtn);
            } else {
                regForm.appendChild(backRow);
            }

            document.getElementById('mp-google-change-account-form').addEventListener('click', (e) => {
                e.preventDefault();
                resetGooglePrefill();
            });

            hideMsg();
        });
    }

    /**
     * Remove OAuth callback params from URL without reload.
     */
    function cleanCallbackUrl() {
        const url = new URL(window.location.href);
        url.searchParams.delete('mp_google_callback');
        url.searchParams.delete('mp_id_token');
        url.searchParams.delete('mp_token');
        url.searchParams.delete('mp_merchant_id');
        url.searchParams.delete('mp_error');
        url.searchParams.delete('state');
        window.history.replaceState({}, '', url.toString());
    }

    // ═══════════════════════════════════════════════════
    // 2FA OTP Form
    // ═══════════════════════════════════════════════════

    function show2FAForm() {
        hideMsg();
        showForm($twofaForm);
        if ($twofaForm) $twofaForm.style.display = '';

        const tokenEl = document.getElementById('mp-2fa-temp-token');
        const emailEl = document.getElementById('mp-2fa-email');
        if (tokenEl) tokenEl.value = twofaTempToken;
        if (emailEl) emailEl.value = twofaEmail;

        const firstDigit = $twofaForm?.querySelector('.monkeypay-otp-digit');
        if (firstDigit) setTimeout(() => firstDigit.focus(), 100);
    }

    function initOTPInputs() {
        const group  = document.getElementById('mp-2fa-otp-group');
        if (!group) return;

        const digits = group.querySelectorAll('.monkeypay-otp-digit');
        const btn    = document.getElementById('mp-2fa-btn');

        function getOTPValue() {
            return Array.from(digits).map(d => d.value).join('');
        }

        function updateSubmitBtn() {
            const otp = getOTPValue();
            if (btn) btn.disabled = otp.length < 6;
            const otpHidden = document.getElementById('mp-2fa-otp');
            if (otpHidden) otpHidden.value = otp;
        }

        digits.forEach((input, idx) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/\D/g, '');
                e.target.value = val.charAt(0) || '';

                if (val && idx < digits.length - 1) {
                    digits[idx + 1].focus();
                }
                updateSubmitBtn();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && idx > 0) {
                    digits[idx - 1].focus();
                    digits[idx - 1].value = '';
                    updateSubmitBtn();
                }
                if (e.key === 'ArrowLeft' && idx > 0) {
                    digits[idx - 1].focus();
                }
                if (e.key === 'ArrowRight' && idx < digits.length - 1) {
                    digits[idx + 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasted = (e.clipboardData.getData('text') || '').replace(/\D/g, '');
                pasted.split('').slice(0, 6).forEach((ch, i) => {
                    if (digits[i]) digits[i].value = ch;
                });
                const lastIdx = Math.min(pasted.length, 6) - 1;
                if (lastIdx >= 0 && digits[lastIdx]) digits[lastIdx].focus();
                updateSubmitBtn();
            });
        });
    }

    async function handle2FAVerify(e) {
        e.preventDefault();

        const btn       = document.getElementById('mp-2fa-btn');
        const otpCode   = document.getElementById('mp-2fa-otp')?.value || '';
        const tempToken = document.getElementById('mp-2fa-temp-token')?.value || '';
        const email     = document.getElementById('mp-2fa-email')?.value || '';

        if (otpCode.length < 6) {
            showMsg('error', 'Vui lòng nhập đủ 6 số');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Đang xác thực...';

        try {
            const res = await fetch(`${MP.restUrl}2fa/verify`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': MP.nonce,
                },
                body: JSON.stringify({
                    otp_code: otpCode,
                    temp_token: tempToken,
                    email: email,
                }),
            });

            const data = await res.json();

            if (data.success) {
                showMsg('success', data.message || 'Xác thực thành công! Đang chuyển hướng...');
                setTimeout(() => { window.location.reload(); }, 1500);
            } else {
                showMsg('error', data.message || 'Mã OTP không đúng');
                const digits = document.querySelectorAll('.monkeypay-otp-digit');
                digits.forEach(d => { d.value = ''; });
                if (digits[0]) digits[0].focus();
                btn.disabled = true;
                btn.textContent = 'Xác nhận';
            }
        } catch (err) {
            showMsg('error', 'Lỗi kết nối: ' + err.message);
            btn.disabled = false;
            btn.textContent = 'Xác nhận';
        }
    }

    // ═══════════════════════════════════════════════════
    // Init
    // ═══════════════════════════════════════════════════

    function initOnboarding() {
        $wrapper      = document.querySelector('.monkeypay-onboarding-wrapper');
        if (!$wrapper) return;

        $msgDiv       = document.getElementById('monkeypay-onboarding-msg');
        $registerForm = document.getElementById('monkeypay-register-form');
        $loginForm    = document.getElementById('monkeypay-login-form');
        $forgotForm   = document.getElementById('monkeypay-forgot-form');
        $twofaForm    = document.getElementById('monkeypay-2fa-form');

        // ── Check Google OAuth callback (returning from redirect) ──
        // NOTE: Don't return early! We must bind events below.
        const isProcessingCallback = checkGoogleCallback();

        // ── Tab Switching ──
        const tabs = $wrapper.querySelectorAll('.monkeypay-onboarding-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;

                tabs.forEach(t => t.classList.remove('monkeypay-onboarding-tab--active'));
                tab.classList.add('monkeypay-onboarding-tab--active');

                hideMsg();
                if (target === 'register') {
                    showForm($registerForm);
                } else {
                    showForm($loginForm);
                }
            });
        });

        // ── Form Submits ──
        if ($registerForm) $registerForm.addEventListener('submit', handleRegister);
        if ($loginForm)    $loginForm.addEventListener('submit', handleLogin);
        if ($forgotForm)   $forgotForm.addEventListener('submit', handleForgotPassword);
        if ($twofaForm)    $twofaForm.addEventListener('submit', handle2FAVerify);

        // ── Google OAuth — bind click IMMEDIATELY, check SDK inside handler ──
        const googleBtns = $wrapper.querySelectorAll('#mp-google-register-btn, #mp-google-login-btn');
        googleBtns.forEach(btn => {
            btn.addEventListener('click', handleGoogleBtnClick);
        });

        // ── Forgot Password Toggle ──
        const forgotToggle = document.getElementById('mp-forgot-toggle');
        if (forgotToggle) {
            forgotToggle.addEventListener('click', (e) => {
                e.preventDefault();
                hideMsg();
                showForm($forgotForm);
            });
        }

        // ── Back to Login (from forgot) ──
        const forgotBack = document.getElementById('mp-forgot-back');
        if (forgotBack) {
            forgotBack.addEventListener('click', (e) => {
                e.preventDefault();
                const forgotResult = document.getElementById('mp-forgot-result');
                if (forgotResult) { forgotResult.style.display = 'none'; forgotResult.innerHTML = ''; }
                showForm($loginForm);
            });
        }

        // ── Back to Login (from 2FA) ──
        const twofaBack = document.getElementById('mp-2fa-back');
        if (twofaBack) {
            twofaBack.addEventListener('click', (e) => {
                e.preventDefault();
                hideMsg();
                twofaTempToken = '';
                twofaEmail = '';
                showForm($loginForm);
                const digits = document.querySelectorAll('.monkeypay-otp-digit');
                digits.forEach(d => { d.value = ''; });
            });
        }

        // ── Password Visibility Toggle ──
        const pwToggles = $wrapper.querySelectorAll('.monkeypay-pw-toggle');
        pwToggles.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.querySelector('.mp-eye-closed').style.display = isPassword ? 'none' : '';
                btn.querySelector('.mp-eye-open').style.display   = isPassword ? '' : 'none';
            });
        });

        // ── Password Strength Meter ──
        updatePasswordStrength('mp-reg-password', 'mp-reg-pw-strength');

        // ── OTP Inputs ──
        initOTPInputs();

        // ── Pre-initialize Google if SDK already available ──
        if (typeof google !== 'undefined' && google.accounts) {
            ensureGoogleInit();
        }

        // ── Execute deferred Google prefill (if any) ──
        if (pendingGoogleUser) {
            const user = pendingGoogleUser;
            pendingGoogleUser = null;
            // Small delay to ensure DOM is ready after events are bound
            setTimeout(() => prefillGoogleRegister(user), 100);
        }
    }

    // ─── Boot ───────────────────────────────────────

    $(document).ready(function () {
        initOnboarding();
    });

})(jQuery);
