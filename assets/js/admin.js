/**
 * MonkeyPay Admin — Bootstrap Dispatcher
 *
 * Sets up the shared MonkeyPay global namespace and loads page-specific
 * modules via separate script files enqueued by PHP.
 *
 * Module files (assets/js/admin/):
 *   utils.js        – shared utilities (toast, clipboard, modal helpers…)
 *   settings.js     – settings page
 *   dashboard.js    – dashboard / transactions
 *   gateways.js     – payment gateways CRUD
 *   onboarding.js   – onboarding gate (login / register)
 *   account.js      – account page
 *   pricing.js      – pricing page
 *   connections.js  – platform connections grid & modal
 *   card-builder.js – Lark card template drag-and-drop builder
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

(function ($) {
    'use strict';

    // ── Shared namespace ─────────────────────────────
    var admin = window.monkeypayAdmin || {};

    window.MonkeyPay = window.MonkeyPay || {
        restUrl        : admin.restUrl        || '',
        nonce          : admin.nonce          || '',
        ajaxUrl        : admin.ajaxUrl        || '',
        adminUrl       : admin.adminUrl       || '',
        pluginUrl      : admin.pluginUrl      || '',
        apiUrl         : admin.apiUrl         || '',
        authProvider   : admin.authProvider   || 'password',
        i18n           : admin.i18n           || {},
    };

    // All page-specific logic is handled by individual module files
    // that are conditionally enqueued by class-monkeypay.php.

})(jQuery);
