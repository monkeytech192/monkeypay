/**
 * MonkeyPay Admin — Payment Gateways
 *
 * Load, save, delete payment gateways.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

(function ($) {
    'use strict';

    const MP = window.MonkeyPay;

    // ─── Load Gateways ──────────────────────────────

    async function loadGateways() {
        const grid = document.getElementById('monkeypay-gateways-grid');
        if (!grid) return;

        try {
            const res = await fetch(`${MP.restUrl}gateways`, {
                headers: { 'X-WP-Nonce': MP.nonce },
            });
            const result = await res.json();

            if (result.success && result.data && result.data.gateways) {
                const gateways = result.data.gateways;
                gateways.forEach((gw) => {
                    const card = grid.querySelector(`[data-bank-code="${gw.bank_code}"]`);
                    if (!card) return;

                    // Populate form fields
                    const form = card.querySelector('.monkeypay-gateway-form');
                    form.querySelector('[name="account_number"]').value = gw.account_number || '';
                    form.querySelector('[name="account_name"]').value = gw.account_name || '';

                    // Populate config fields
                    const autoAmountEl = form.querySelector('[name="auto_amount"]');
                    if (autoAmountEl) autoAmountEl.checked = gw.auto_amount !== 0;

                    const notePrefixEl = form.querySelector('[name="note_prefix"]');
                    if (notePrefixEl && gw.note_prefix) notePrefixEl.value = gw.note_prefix;

                    const noteSyntaxEl = form.querySelector('[name="note_syntax"]');
                    if (noteSyntaxEl && gw.note_syntax) noteSyntaxEl.value = gw.note_syntax;

                    const pollingEl = form.querySelector('[name="polling_interval"]');
                    if (pollingEl && gw.polling_interval) pollingEl.value = gw.polling_interval;

                    // Store gateway ID
                    card.dataset.gatewayId = gw.id;

                    // Update status badge
                    const badge = card.querySelector('.monkeypay-gateway-status');
                    if (badge) {
                        badge.className = 'monkeypay-badge monkeypay-badge-success monkeypay-gateway-status';
                        badge.textContent = 'Đã cấu hình';
                    }

                    // Show delete button
                    const deleteBtn = card.querySelector('.monkeypay-gateway-delete-btn');
                    if (deleteBtn) deleteBtn.style.display = '';
                });
            }
        } catch (err) {
            console.error('Failed to load gateways:', err);
        }
    }

    // ─── Toggle Expand ──────────────────────────────

    function handleGatewayToggle(e) {
        const btn = e.target.closest('.monkeypay-gateway-toggle-btn');
        if (!btn) return;

        const card = btn.closest('.monkeypay-gateway-card');
        const form = card.querySelector('.monkeypay-gateway-form');
        const isVisible = form.style.display !== 'none';

        form.style.display = isVisible ? 'none' : '';
        card.classList.toggle('expanded', !isVisible);
    }

    // ─── Save Gateway ───────────────────────────────

    async function handleGatewaySave(e) {
        const btn = e.target.closest('.monkeypay-gateway-save-btn');
        if (!btn) return;

        const card = btn.closest('.monkeypay-gateway-card');
        const bankCode = card.dataset.bankCode;
        const form = card.querySelector('.monkeypay-gateway-form');

        const accountNumber = form.querySelector('[name="account_number"]').value.trim();
        if (!accountNumber) {
            MP.showToast('Vui lòng nhập số tài khoản', 'error');
            return;
        }

        const bankNames = { mbbank: 'MB Bank' };

        // Collect config fields
        const autoAmountEl = form.querySelector('[name="auto_amount"]');
        const notePrefixEl = form.querySelector('[name="note_prefix"]');
        const noteSyntaxEl = form.querySelector('[name="note_syntax"]');
        const pollingEl    = form.querySelector('[name="polling_interval"]');

        const payload = {
            bank_code: bankCode,
            bank_name: bankNames[bankCode] || bankCode,
            account_number: accountNumber,
            account_name: form.querySelector('[name="account_name"]').value.trim().toUpperCase(),
            auto_amount: autoAmountEl ? (autoAmountEl.checked ? 1 : 0) : 1,
            note_prefix: notePrefixEl ? notePrefixEl.value.trim().toUpperCase() : 'MP',
            note_syntax: noteSyntaxEl ? noteSyntaxEl.value.trim() : '{prefix}{random:6}',
            polling_interval: pollingEl ? parseInt(pollingEl.value, 10) || 5 : 5,
        };

        btn.classList.add('loading');

        try {
            const res = await fetch(`${MP.restUrl}gateways`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': MP.nonce,
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (data.success) {
                MP.showToast('Đã lưu cổng thanh toán!', 'success');

                // Update badge
                const badge = card.querySelector('.monkeypay-gateway-status');
                if (badge) {
                    badge.className = 'monkeypay-badge monkeypay-badge-success monkeypay-gateway-status';
                    badge.textContent = 'Đã cấu hình';
                }

                // Store ID and show delete button
                if (data.data && data.data.id) {
                    card.dataset.gatewayId = data.data.id;
                }
                const deleteBtn = card.querySelector('.monkeypay-gateway-delete-btn');
                if (deleteBtn) deleteBtn.style.display = '';
            } else {
                throw new Error(data.data?.error || data.message || 'Save failed');
            }
        } catch (err) {
            MP.showToast(err.message || 'Lỗi lưu cổng thanh toán', 'error');
        } finally {
            btn.classList.remove('loading');
        }
    }

    // ─── Delete Gateway ─────────────────────────────

    async function handleGatewayDelete(e) {
        const btn = e.target.closest('.monkeypay-gateway-delete-btn');
        if (!btn) return;

        if (!confirm('Bạn có chắc muốn xoá cổng thanh toán này?')) return;

        const card = btn.closest('.monkeypay-gateway-card');
        const gatewayId = card.dataset.gatewayId;

        if (!gatewayId) return;

        btn.classList.add('loading');

        try {
            const res = await fetch(`${MP.restUrl}gateways/${gatewayId}`, {
                method: 'DELETE',
                headers: { 'X-WP-Nonce': MP.nonce },
            });

            const data = await res.json();

            if (data.success) {
                MP.showToast('Đã xoá cổng thanh toán', 'success');

                // Reset form
                const form = card.querySelector('.monkeypay-gateway-form');
                form.querySelectorAll('input').forEach((input) => (input.value = ''));
                delete card.dataset.gatewayId;

                // Update badge
                const badge = card.querySelector('.monkeypay-gateway-status');
                if (badge) {
                    badge.className = 'monkeypay-badge monkeypay-badge-gray monkeypay-gateway-status';
                    badge.textContent = 'Chưa cấu hình';
                }

                btn.style.display = 'none';
            } else {
                throw new Error(data.message || 'Delete failed');
            }
        } catch (err) {
            MP.showToast(err.message || 'Lỗi xoá cổng thanh toán', 'error');
        } finally {
            btn.classList.remove('loading');
        }
    }

    // ─── Init ───────────────────────────────────────

    $(document).ready(function () {
        if ($('#monkeypay-gateways-grid').length) {
            loadGateways();

            $(document).on('click', '.monkeypay-gateway-toggle-btn', handleGatewayToggle);
            $(document).on('click', '.monkeypay-gateway-save-btn', handleGatewaySave);
            $(document).on('click', '.monkeypay-gateway-delete-btn', handleGatewayDelete);
        }
    });

})(jQuery);
