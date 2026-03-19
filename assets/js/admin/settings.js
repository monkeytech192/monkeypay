/**
 * MonkeyPay Admin — Settings Page
 *
 * Test connection, save settings, integration toggles.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

(function ($) {
    'use strict';

    const MP = window.MonkeyPay;

    // ─── Test Connection ────────────────────────────

    async function testConnection() {
        const badge = document.getElementById('monkeypay-status-badge');
        const btn = document.getElementById('monkeypay-test-connection');

        if (!badge || !btn) return;

        badge.className = 'monkeypay-header__badge';
        badge.querySelector('.monkeypay-status-text').textContent = MP.i18n.connecting;
        btn.classList.add('loading');

        try {
            const res = await fetch(`${MP.restUrl}health`, {
                headers: { 'X-WP-Nonce': MP.nonce },
            });

            const data = await res.json();

            if (data.success && data.data) {
                badge.classList.add('connected');
                badge.querySelector('.monkeypay-status-text').textContent = MP.i18n.connected;

                const el = (id) => document.getElementById(id);
                if (el('stat-server')) el('stat-server').textContent = '✅';

                // Load merchant usage stats for dashboard
                try {
                    const usageRes = await fetch(`${MP.restUrl}usage`, {
                        headers: { 'X-WP-Nonce': MP.nonce },
                    });
                    const usageData = await usageRes.json();
                    if (usageData.success && usageData.data) {
                        const u = usageData.data.usage || {};
                        const p = usageData.data.plan || {};
                        if (el('stat-gateways')) el('stat-gateways').textContent = `${u.gateway_count ?? 0} / ${u.gateway_limit ?? '—'}`;
                        if (el('stat-requests')) el('stat-requests').textContent = `${u.request_count ?? 0} / ${u.request_limit ?? '—'}`;
                        if (el('stat-plan')) el('stat-plan').textContent = (p.name || '—').toUpperCase();
                    }
                } catch (_) { /* usage fetch failed, keep dashes */ }

                MP.showToast(MP.i18n.connected, 'success');
            } else {
                throw new Error(data.message || 'Failed');
            }
        } catch (err) {
            badge.classList.add('disconnected');
            badge.querySelector('.monkeypay-status-text').textContent = MP.i18n.disconnected;

            const el = (id) => document.getElementById(id);
            if (el('stat-server')) el('stat-server').textContent = '❌';
            MP.showToast(err.message || MP.i18n.error, 'error');
        } finally {
            btn.classList.remove('loading');
        }
    }

    // ─── Save Settings ──────────────────────────────

    async function saveSettings(e) {
        e.preventDefault();

        const btn = document.getElementById('monkeypay-save-btn');
        btn.classList.add('loading');

        const settings = {
            monkeypay_api_url: document.getElementById('monkeypay_api_url').value,
            monkeypay_api_key: document.getElementById('monkeypay_api_key').value,
            monkeypay_webhook_secret: document.getElementById('monkeypay_webhook_secret').value,
            monkeypay_admin_secret: document.getElementById('monkeypay_admin_secret')?.value || '',
        };

        try {
            const res = await fetch(`${MP.restUrl}settings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': MP.nonce,
                },
                body: JSON.stringify(settings),
            });

            const data = await res.json();

            if (data.success) {
                MP.showToast(MP.i18n.saved, 'success');
            } else {
                throw new Error(data.message || 'Save failed');
            }
        } catch (err) {
            MP.showToast(err.message || MP.i18n.error, 'error');
        } finally {
            btn.classList.remove('loading');
        }
    }

    // ─── Integration Toggles ────────────────────────

    async function handleIntegrationToggle(e) {
        const toggle = e.target;
        const card = toggle.closest('.monkeypay-connection-card');
        const optionKey = toggle.dataset.option;
        const value = toggle.checked ? '1' : '0';

        // Update UI immediately
        const statusText = card.querySelector('.monkeypay-toggle-status-text');
        const badge = card.querySelector('.monkeypay-badge');

        if (toggle.checked) {
            card.classList.add('enabled');
            card.classList.remove('disabled');
            if (statusText) statusText.textContent = 'Đang bật';
            if (badge) {
                badge.className = 'monkeypay-badge monkeypay-badge-success';
                badge.textContent = 'Đã kết nối';
            }
        } else {
            card.classList.remove('enabled');
            card.classList.add('disabled');
            if (statusText) statusText.textContent = 'Đang tắt';
            if (badge) {
                badge.className = 'monkeypay-badge monkeypay-badge-gray';
                badge.textContent = 'Chưa kết nối';
            }
        }

        // Persist via REST
        try {
            const settings = {};
            settings[optionKey] = value;

            await fetch(`${MP.restUrl}settings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': MP.nonce,
                },
                body: JSON.stringify(settings),
            });

            MP.showToast(toggle.checked ? 'Tích hợp đã bật' : 'Tích hợp đã tắt', 'success');
        } catch (err) {
            MP.showToast(err.message || 'Lỗi lưu cài đặt', 'error');
            // Revert on error
            toggle.checked = !toggle.checked;
        }
    }

    // ─── Organization Registration (legacy) ─────────

    async function handleRegistration(e) {
        e.preventDefault();

        const btn = document.getElementById('monkeypay-register-btn');
        const resultDiv = document.getElementById('monkeypay-register-result');
        const orgName = document.getElementById('org_name').value.trim();
        const orgEmail = document.getElementById('org_email')?.value?.trim() || '';

        if (!orgName) {
            MP.showToast('Vui lòng nhập tên tổ chức', 'error');
            return;
        }

        // Check API URL is saved
        const apiUrl = document.getElementById('monkeypay_api_url')?.value?.trim();
        if (!apiUrl) {
            MP.showToast('Vui lòng nhập và lưu MonkeyPay Server URL trước', 'error');
            return;
        }

        btn.classList.add('loading');
        btn.disabled = true;

        try {
            const res = await fetch(`${MP.restUrl}register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': MP.nonce,
                },
                body: JSON.stringify({ name: orgName, email: orgEmail }),
            });

            const data = await res.json();

            if (data.success && data.data) {
                const merchant = data.data.merchant;
                const plan = data.data.plan;

                resultDiv.style.display = '';
                resultDiv.innerHTML = `
                    <div class="monkeypay-register-success">
                        <div class="monkeypay-register-success__icon">✅</div>
                        <div>
                            <strong>${data.data.message || 'Tổ chức đã được tạo!'}</strong>
                            <p>API Key: <code>${merchant.api_key}</code></p>
                            <p>Gói: ${plan.name} (${plan.request_limit} request/tháng)</p>
                        </div>
                    </div>
                `;

                MP.showToast('Tổ chức đã được tạo thành công!', 'success');

                // Reload page after 2 seconds to show usage stats
                setTimeout(() => window.location.reload(), 2000);
            } else {
                throw new Error(data.message || 'Registration failed');
            }
        } catch (err) {
            resultDiv.style.display = '';
            resultDiv.innerHTML = `<div class="monkeypay-register-error">❌ ${err.message}</div>`;
            MP.showToast(err.message || 'Lỗi tạo tổ chức', 'error');
        } finally {
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    }

    // ─── Usage Stats ────────────────────────────────

    async function loadUsageStats() {
        const card = document.getElementById('monkeypay-usage-card');
        if (!card) return;

        const content = document.getElementById('monkeypay-usage-content');
        const subtitle = document.getElementById('monkeypay-usage-subtitle');
        const planPill = document.getElementById('monkeypay-plan-pill');

        try {
            const res = await fetch(`${MP.restUrl}usage`, {
                headers: { 'X-WP-Nonce': MP.nonce },
            });

            const data = await res.json();

            if (data.success && data.data) {
                const { merchant, plan, usage } = data.data;
                const isUnlimited = plan.unlimited || plan.request_limit === -1;

                // Update plan pill
                const planColors = {
                    free: '#6b7280',
                    basic: '#3b82f6',
                    advanced: '#8b5cf6',
                    pro: '#f59e0b',
                };
                planPill.textContent = plan.name;
                planPill.style.background = planColors[plan.id] || '#6b7280';
                planPill.style.color = '#fff';

                // Update subtitle
                subtitle.textContent = merchant.name;

                // Calculate usage percentage
                const usagePercent = isUnlimited ? 0 : Math.min(100, Math.round((usage.request_count / usage.request_limit) * 100));
                const barColor = usagePercent > 80 ? '#ef4444' : usagePercent > 50 ? '#f59e0b' : '#10b981';

                content.innerHTML = `
                    <div class="monkeypay-usage-grid">
                        <div class="monkeypay-usage-stat">
                            <div class="monkeypay-usage-stat__label">Request sử dụng</div>
                            <div class="monkeypay-usage-stat__value">
                                ${usage.request_count} / ${isUnlimited ? '∞' : usage.request_limit}
                            </div>
                            ${!isUnlimited ? `
                            <div class="monkeypay-usage-bar">
                                <div class="monkeypay-usage-bar__fill" style="width: ${usagePercent}%; background: ${barColor};"></div>
                            </div>
                            <div class="monkeypay-usage-stat__hint">Còn lại: ${usage.request_remaining} request</div>
                            ` : '<div class="monkeypay-usage-stat__hint">Không giới hạn</div>'}
                        </div>
                        <div class="monkeypay-usage-stat">
                            <div class="monkeypay-usage-stat__label">Cổng thanh toán</div>
                            <div class="monkeypay-usage-stat__value">
                                ${usage.gateway_count} / ${usage.gateway_limit === -1 ? '∞' : usage.gateway_limit}
                            </div>
                        </div>
                        <div class="monkeypay-usage-stat">
                            <div class="monkeypay-usage-stat__label">Giá gói</div>
                            <div class="monkeypay-usage-stat__value">
                                ${plan.price === 0 ? 'Miễn phí' : new Intl.NumberFormat('vi-VN').format(plan.price) + 'đ/tháng'}
                            </div>
                        </div>
                        <div class="monkeypay-usage-stat">
                            <div class="monkeypay-usage-stat__label">Chu kỳ</div>
                            <div class="monkeypay-usage-stat__value monkeypay-usage-stat__value--small">
                                ${new Date(usage.period_start).toLocaleDateString('vi-VN')} — ${new Date(usage.period_end).toLocaleDateString('vi-VN')}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                throw new Error(data.message || 'Failed to load usage');
            }
        } catch (err) {
            content.innerHTML = `
                <div class="monkeypay-usage-error">
                    <span class="dashicons dashicons-warning"></span>
                    ${err.message || 'Không thể tải thông tin sử dụng'}
                </div>
            `;
        }
    }

    // ─── Init ───────────────────────────────────────

    $(document).ready(function () {
        // Dashboard — test connection
        $('#monkeypay-test-connection').on('click', testConnection);

        // Settings page — save form
        $('#monkeypay-settings-form').on('submit', saveSettings);

        // Registration form (settings page — legacy)
        $('#monkeypay-register-form').on('submit', handleRegistration);

        // Integrations page — toggle handlers
        $(document).on('change', '.monkeypay-integration-toggle', handleIntegrationToggle);

        // Auto-test on Dashboard if on that page
        if ($('#monkeypay-status-badge').length) {
            setTimeout(testConnection, 800);
        }

        // Load usage stats on settings page
        if ($('#monkeypay-usage-card').length) {
            loadUsageStats();
        }
    });

})(jQuery);
