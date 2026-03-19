/**
 * MonkeyPay Payment Page JS
 *
 * Loads transaction data, displays QR, polls for status, handles success/expiry.
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

(function () {
    'use strict';

    const { restUrl, nonce, txId, orderId } = window.monkeypayPayment || {};

    let pollTimer = null;
    let countdownTimer = null;
    let expiresAt = null;

    // ─── DOM Elements ───────────────────────────────

    const els = {
        qrImg: document.getElementById('monkeypay-qr-img'),
        qrLoading: document.getElementById('monkeypay-qr-loading'),
        accountNumber: document.getElementById('monkeypay-account-number'),
        accountName: document.getElementById('monkeypay-account-name'),
        amount: document.getElementById('monkeypay-amount'),
        paymentNote: document.getElementById('monkeypay-payment-note'),
        polling: document.getElementById('monkeypay-polling'),
        success: document.getElementById('monkeypay-success'),
        expired: document.getElementById('monkeypay-expired'),
        countdown: document.getElementById('monkeypay-countdown'),
        timer: document.getElementById('monkeypay-timer'),
    };

    // ─── Format Currency ────────────────────────────

    function formatVND(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
            maximumFractionDigits: 0,
        }).format(amount);
    }

    // ─── Load Transaction ───────────────────────────

    async function loadTransaction() {
        const id = txId || document.getElementById('monkeypay-payment')?.dataset.txId;
        if (!id) return;

        try {
            const res = await fetch(`${restUrl}transactions/${id}`, {
                headers: nonce ? { 'X-WP-Nonce': nonce } : {},
            });

            const data = await res.json();

            if (!data.success || !data.data) {
                showExpired();
                return;
            }

            const tx = data.data;

            // Check if already completed
            if (tx.status === 'completed') {
                showSuccess();
                return;
            }

            if (tx.status === 'expired') {
                showExpired();
                return;
            }

            // Populate payment info
            populatePaymentInfo(tx);

            // Start polling
            startPolling(id);
        } catch (err) {
            console.error('[MonkeyPay] Error loading transaction:', err);
        }
    }

    // ─── Populate Payment Info ──────────────────────

    function populatePaymentInfo(tx) {
        // QR
        if (tx.qr_url) {
            els.qrImg.src = tx.qr_url;
            els.qrImg.style.display = 'block';
            if (els.qrLoading) els.qrLoading.style.display = 'none';
        }

        // Bank info
        if (tx.bank_info) {
            if (els.accountNumber) {
                els.accountNumber.dataset.copy = tx.bank_info.account_number || '';
                els.accountNumber.childNodes[0].textContent = tx.bank_info.account_number || '—';
            }
            if (els.accountName) {
                els.accountName.textContent = tx.bank_info.account_name || '—';
            }
        }

        // Amount
        if (els.amount) {
            els.amount.textContent = formatVND(tx.amount);
        }

        // Payment note
        if (els.paymentNote) {
            els.paymentNote.dataset.copy = tx.payment_note || '';
            els.paymentNote.childNodes[0].textContent = tx.payment_note || '—';
        }

        // Countdown
        if (tx.expires_at) {
            expiresAt = new Date(tx.expires_at);
            startCountdown();
        }
    }

    // ─── Polling ────────────────────────────────────

    function startPolling(id) {
        if (pollTimer) clearInterval(pollTimer);

        pollTimer = setInterval(async () => {
            try {
                const res = await fetch(`${restUrl}transactions/${id}`, {
                    headers: nonce ? { 'X-WP-Nonce': nonce } : {},
                });

                const data = await res.json();
                if (!data.success) return;

                const tx = data.data;

                if (tx.status === 'completed') {
                    stopPolling();
                    showSuccess();
                } else if (tx.status === 'expired') {
                    stopPolling();
                    showExpired();
                }
            } catch (err) {
                console.warn('[MonkeyPay] Polling error:', err);
            }
        }, 5000); // Poll every 5 seconds
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    // ─── Countdown ──────────────────────────────────

    function startCountdown() {
        if (countdownTimer) clearInterval(countdownTimer);

        countdownTimer = setInterval(() => {
            const now = new Date();
            const diff = expiresAt - now;

            if (diff <= 0) {
                stopPolling();
                showExpired();
                return;
            }

            const mins = Math.floor(diff / 60000);
            const secs = Math.floor((diff % 60000) / 1000);
            els.countdown.textContent =
                String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        }, 1000);
    }

    // ─── State Changes ──────────────────────────────

    function showSuccess() {
        if (els.polling) els.polling.style.display = 'none';
        if (els.success) els.success.style.display = 'block';
        if (els.expired) els.expired.style.display = 'none';
        if (els.timer) els.timer.style.display = 'none';

        // If WooCommerce, redirect after 3s
        if (orderId) {
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        }
    }

    function showExpired() {
        if (els.polling) els.polling.style.display = 'none';
        if (els.success) els.success.style.display = 'none';
        if (els.expired) els.expired.style.display = 'block';
        if (els.timer) els.timer.style.display = 'none';
    }

    // ─── Copy to Clipboard ──────────────────────────

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.monkeypay-btn-copy');
        if (!btn) return;

        const parent = btn.closest('.monkeypay-payment__copyable') || btn.parentElement;
        const text = parent?.dataset?.copy || btn.dataset?.copy || '';
        if (!text) return;

        navigator.clipboard.writeText(text).then(() => {
            const orig = btn.textContent;
            btn.textContent = '✅';
            setTimeout(() => (btn.textContent = orig), 1500);
        });
    });

    // ─── Init ───────────────────────────────────────

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadTransaction);
    } else {
        loadTransaction();
    }
})();
