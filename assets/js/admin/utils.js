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

    MP.restUrl  = config.restUrl || '';
    MP.adminUrl = config.adminUrl || '';
    MP.nonce    = config.nonce || '';
    MP.i18n     = config.i18n || {};

    // ─── Toast ──────────────────────────────────────

    var TOAST_ICONS = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        info:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'
    };

    MP.showToast = function (message, type) {
        type = type || 'success';
        var existing = document.querySelector('.monkeypay-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.className = 'monkeypay-toast ' + type;

        // Icon
        var iconWrap = document.createElement('span');
        iconWrap.className = 'monkeypay-toast__icon';
        iconWrap.innerHTML = TOAST_ICONS[type] || TOAST_ICONS.info;
        toast.appendChild(iconWrap);

        // Body
        var body = document.createElement('span');
        body.className = 'monkeypay-toast__body';
        body.textContent = message;
        toast.appendChild(body);

        // Close button
        var closeBtn = document.createElement('button');
        closeBtn.className = 'monkeypay-toast__close';
        closeBtn.type = 'button';
        closeBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
        closeBtn.onclick = function () { dismissToast(toast); };
        toast.appendChild(closeBtn);

        // Progress bar
        var progress = document.createElement('div');
        progress.className = 'monkeypay-toast__progress';
        toast.appendChild(progress);

        document.body.appendChild(toast);

        requestAnimationFrame(function () { toast.classList.add('show'); });

        var timer = setTimeout(function () { dismissToast(toast); }, 3200);
        toast._timer = timer;
    };

    function dismissToast(toast) {
        if (!toast || !toast.parentNode) return;
        clearTimeout(toast._timer);
        toast.classList.remove('show');
        setTimeout(function () { if (toast.parentNode) toast.remove(); }, 350);
    }

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
