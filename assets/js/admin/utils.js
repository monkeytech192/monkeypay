/**
 * MonkeyPay Admin — Shared Utilities
 *
 * Toast notifications, API helpers, and common functions.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

window.MonkeyPay = window.MonkeyPay || {};

(function ($) {
    'use strict';

    const MP = window.MonkeyPay;
    const config = window.monkeypayAdmin || {};

    MP.restUrl = config.restUrl || '';
    MP.nonce   = config.nonce || '';
    MP.i18n    = config.i18n || {};

    // ─── Toast ──────────────────────────────────────

    MP.showToast = function (message, type) {
        type = type || 'success';
        var existing = document.querySelector('.monkeypay-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.className = 'monkeypay-toast ' + type;
        toast.textContent = message;
        document.body.appendChild(toast);

        requestAnimationFrame(function () { toast.classList.add('show'); });

        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    };

    // ─── Copy to Clipboard ──────────────────────────

    MP.handleCopy = function (e) {
        var btn = e.target.closest('.monkeypay-btn-copy');
        if (!btn) return;

        var text = btn.dataset.copy || '';
        if (!text) return;

        navigator.clipboard.writeText(text).then(function () {
            MP.showToast('Đã sao chép!', 'success');
        });
    };

    // ─── HTML Escape ────────────────────────────────

    MP.escHtml = function (str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    // ─── VND Formatter ──────────────────────────────

    MP.formatVND = function (amount) {
        if (amount == null || isNaN(amount)) return '—';
        return new Intl.NumberFormat('vi-VN').format(Math.round(Number(amount))) + ' ₫';
    };

    // ─── Modal Helpers ──────────────────────────────

    MP.openModal = function ($modal) {
        $modal.addClass('is-open');
        $modal.attr('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    MP.closeModal = function ($modal) {
        $modal.removeClass('is-open');
        $modal.attr('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    // ─── Init shared handlers ───────────────────────

    $(document).ready(function () {
        // Copy to clipboard — global handler
        $(document).on('click', '.monkeypay-btn-copy', MP.handleCopy);

        // Secret toggle (API Key, Webhook Secret on settings page)
        $(document).on('click', '.monkeypay-secret-toggle', function () {
            var targetId = $(this).data('target');
            var input = document.getElementById(targetId);
            if (!input) return;
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            $(this).find('.mp-eye-show').toggle(!isPassword);
            $(this).find('.mp-eye-hide').toggle(isPassword);
        });
    });

})(jQuery);
