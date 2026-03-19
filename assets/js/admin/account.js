/**
 * MonkeyPay Admin — Account Page
 *
 * Profile data, change password, API key toggle, logout.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

(function ($) {
    'use strict';

    const MP = window.MonkeyPay;

    // ─── Load Account Data ──────────────────────────

    async function loadAccountData() {
        try {
            const res = await fetch(`${MP.restUrl}usage`, {
                headers: { 'X-WP-Nonce': MP.nonce },
            });
            const data = await res.json();

            if (data.success && data.data) {
                const d = data.data;
                const merchant = d.merchant || {};
                const plan = d.plan || {};
                const usage = d.usage || {};

                const el = (id) => document.getElementById(id);

                // Profile header
                if (el('mp-account-name')) el('mp-account-name').textContent = merchant.name || '—';
                if (el('mp-account-email-display')) el('mp-account-email-display').textContent = merchant.email || '—';
                if (el('mp-account-plan')) el('mp-account-plan').textContent = (plan.name || 'Free').toUpperCase();

                // Avatar letter
                const avatarEl = el('mp-account-avatar');
                if (avatarEl && merchant.name) {
                    avatarEl.querySelector('span').textContent = merchant.name.charAt(0).toUpperCase();
                }

                // Detail fields
                if (el('mp-acc-name')) el('mp-acc-name').textContent = merchant.name || '—';
                if (el('mp-acc-email')) el('mp-acc-email').textContent = merchant.email || '—';
                if (el('mp-acc-phone')) el('mp-acc-phone').textContent = merchant.phone || '—';
                if (el('mp-acc-site')) el('mp-acc-site').textContent = merchant.site_url || '—';

                // API Key — masked
                const apiKeyEl = el('mp-acc-apikey');
                if (apiKeyEl) {
                    const realKey = merchant.api_key || '';
                    apiKeyEl.setAttribute('data-real-key', realKey);
                    apiKeyEl.textContent = realKey ? '•'.repeat(Math.min(realKey.length, 32)) : '—';
                    apiKeyEl.setAttribute('data-masked', 'true');
                }

                // Usage stats
                if (el('mp-acc-requests')) el('mp-acc-requests').textContent = `${usage.request_count ?? 0} / ${usage.request_limit ?? '—'}`;
                if (el('mp-acc-gateways')) el('mp-acc-gateways').textContent = `${usage.gateway_count ?? 0} / ${usage.gateway_limit ?? '—'}`;
                if (el('mp-acc-period')) el('mp-acc-period').textContent = usage.period_start ? new Date(usage.period_start).toLocaleDateString('vi-VN') : '—';
            }
        } catch (err) {
            MP.showToast('Không thể tải thông tin tài khoản', 'error');
        }
    }

    // ─── Change Password ────────────────────────────

    async function handleChangePassword(e) {
        e.preventDefault();

        const btn = document.getElementById('mp-chpw-btn');
        const msgDiv = document.getElementById('mp-chpw-msg');
        const oldPw = document.getElementById('mp-chpw-old').value;
        const newPw = document.getElementById('mp-chpw-new').value;
        const confirmPw = document.getElementById('mp-chpw-confirm').value;
        const emailEl = document.getElementById('mp-acc-email');
        const email = emailEl ? emailEl.textContent.trim() : '';

        // Validate
        if (!oldPw || !newPw) {
            if (msgDiv) {
                msgDiv.style.display = 'block';
                msgDiv.className = 'monkeypay-onboarding-msg--error';
                msgDiv.textContent = 'Vui lòng nhập đầy đủ mật khẩu cũ và mới';
            }
            return;
        }
        if (newPw.length < 6) {
            if (msgDiv) {
                msgDiv.style.display = 'block';
                msgDiv.className = 'monkeypay-onboarding-msg--error';
                msgDiv.textContent = 'Mật khẩu mới phải có ít nhất 6 ký tự';
            }
            return;
        }
        if (newPw !== confirmPw) {
            if (msgDiv) {
                msgDiv.style.display = 'block';
                msgDiv.className = 'monkeypay-onboarding-msg--error';
                msgDiv.textContent = 'Mật khẩu xác nhận không khớp';
            }
            return;
        }
        if (!email || email === '—') {
            if (msgDiv) {
                msgDiv.style.display = 'block';
                msgDiv.className = 'monkeypay-onboarding-msg--error';
                msgDiv.textContent = 'Không tìm thấy email tài khoản. Vui lòng tải lại trang.';
            }
            return;
        }

        btn.disabled = true;
        const origText = btn.innerHTML;
        btn.textContent = 'Đang xử lý...';

        try {
            const res = await fetch(`${MP.restUrl}change-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': MP.nonce,
                },
                body: JSON.stringify({ email, old_password: oldPw, new_password: newPw }),
            });

            const data = await res.json();

            if (data.success) {
                if (msgDiv) {
                    msgDiv.style.display = 'block';
                    msgDiv.className = 'monkeypay-onboarding-msg--success';
                    msgDiv.textContent = data.message || 'Đổi mật khẩu thành công!';
                }
                // Clear form
                document.getElementById('mp-chpw-old').value = '';
                document.getElementById('mp-chpw-new').value = '';
                document.getElementById('mp-chpw-confirm').value = '';
                MP.showToast('Đổi mật khẩu thành công!', 'success');
            } else {
                if (msgDiv) {
                    msgDiv.style.display = 'block';
                    msgDiv.className = 'monkeypay-onboarding-msg--error';
                    msgDiv.textContent = data.message || 'Đổi mật khẩu thất bại';
                }
            }
        } catch (err) {
            if (msgDiv) {
                msgDiv.style.display = 'block';
                msgDiv.className = 'monkeypay-onboarding-msg--error';
                msgDiv.textContent = 'Lỗi: ' + err.message;
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    }

    // ─── Logout ─────────────────────────────────────

    async function handleLogout() {
        if (!confirm('Bạn có chắc muốn đăng xuất tổ chức? API Key sẽ bị xoá.')) return;

        try {
            const res = await fetch(`${MP.restUrl}settings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': MP.nonce,
                },
                body: JSON.stringify({
                    monkeypay_api_key: '',
                    monkeypay_webhook_secret: '',
                }),
            });

            const data = await res.json();
            if (data.success) {
                MP.showToast('Đã đăng xuất tổ chức. Đang chuyển hướng...', 'success');
                setTimeout(() => { window.location.reload(); }, 1500);
            } else {
                MP.showToast('Đăng xuất thất bại', 'error');
            }
        } catch (err) {
            MP.showToast('Lỗi: ' + err.message, 'error');
        }
    }

    // ─── Init ───────────────────────────────────────

    function initAccount() {
        const card = document.getElementById('monkeypay-account-card');
        if (!card) return;

        // Load data
        loadAccountData();

        // Logout
        const logoutBtn = card.querySelector('.monkeypay-account-logout-btn');
        if (logoutBtn) logoutBtn.addEventListener('click', handleLogout);

        // Change password form
        const chpwForm = document.getElementById('mp-change-password-form');
        if (chpwForm) chpwForm.addEventListener('submit', handleChangePassword);

        // Password toggles
        const pwToggles = card.querySelectorAll('.monkeypay-pw-toggle');
        pwToggles.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                const closedEye = btn.querySelector('.mp-eye-closed');
                const openEye = btn.querySelector('.mp-eye-open');
                if (closedEye) closedEye.style.display = isPassword ? 'none' : '';
                if (openEye) openEye.style.display = isPassword ? '' : 'none';
            });
        });

        // API Key Toggle
        const toggleBtn = document.getElementById('mp-apikey-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const apiKeyEl = document.getElementById('mp-acc-apikey');
                if (!apiKeyEl) return;
                const isMasked = apiKeyEl.getAttribute('data-masked') === 'true';
                const realKey = apiKeyEl.getAttribute('data-real-key');
                if (!realKey) return;

                if (isMasked) {
                    apiKeyEl.textContent = realKey;
                    apiKeyEl.setAttribute('data-masked', 'false');
                    toggleBtn.querySelector('.mp-eye-closed').style.display = 'none';
                    toggleBtn.querySelector('.mp-eye-open').style.display = '';
                } else {
                    apiKeyEl.textContent = '•'.repeat(Math.min(realKey.length, 32));
                    apiKeyEl.setAttribute('data-masked', 'true');
                    toggleBtn.querySelector('.mp-eye-closed').style.display = '';
                    toggleBtn.querySelector('.mp-eye-open').style.display = 'none';
                }
            });
        }

        // Copy API Key
        const copyBtn = document.getElementById('mp-apikey-copy');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                const apiKeyEl = document.getElementById('mp-acc-apikey');
                if (!apiKeyEl) return;
                const realKey = apiKeyEl.getAttribute('data-real-key') || apiKeyEl.textContent;
                navigator.clipboard.writeText(realKey).then(() => {
                    MP.showToast('Đã copy API Key', 'success');
                }).catch(() => {
                    MP.showToast('Không thể copy', 'error');
                });
            });
        }
    }

    // ─── Boot ───────────────────────────────────────

    $(document).ready(function () {
        initAccount();
    });

})(jQuery);
