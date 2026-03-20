=== MonkeyPay - Cổng Thanh Toán Tự Động ===
Contributors: monkeytech192
Tags: payment, bank-transfer, woocommerce, vietnam, mb-bank, webhook, lark
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 3.3.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automated bank transfer payment gateway for WordPress. Real-time verification via MB Bank BDSD webhook.

== Description ==

MonkeyPay automates bank transfer payment processing for Vietnamese WordPress sites. It integrates with MB Bank BDSD (Biến Động Số Dư) webhooks to instantly verify incoming payments — no manual checking required.

**Key Features:**

* 🏦 Automated payment verification via MB Bank webhook
* 🛒 Native WooCommerce payment gateway
* 🔔 Lark/Feishu notification cards with drag-drop builder
* 🔗 Extensible webhook connection system
* 🔒 Enterprise-grade security (HMAC, nonce, capability checks)
* 🌐 Vietnamese and English support

**Supported Platforms:**

* MB Bank (BDSD webhook)
* WooCommerce
* Lark/Feishu
* Slack (coming soon)
* Telegram (coming soon)

== Installation ==

1. Upload the `monkeypay` folder to `wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **MonkeyPay** in the admin sidebar
4. Enter your API Key from [monkeytech192.vn](https://monkeytech192.vn)
5. Configure your MB Bank connection

== Frequently Asked Questions ==

= Which banks are supported? =

Currently, MonkeyPay supports MB Bank through the BDSD (Biến Động Số Dư) webhook system. Additional banks may be added in future versions.

= Does it work with WooCommerce? =

Yes! MonkeyPay includes a native WooCommerce payment gateway. Enable it in the plugin settings.

= Can I customize notification cards? =

Yes. The Lark connection includes a drag-drop card builder that lets you customize the notification card layout with text, notes, dividers, and URL buttons.

= Is it secure? =

MonkeyPay implements multiple security layers: HMAC webhook verification, WordPress nonce validation, capability checks, input sanitization, and output escaping.

== Changelog ==

= 3.3.0 =
* Google OAuth popup flow for seamless registration
* Account switching for Google-authenticated users
* Transaction Management admin page with real-time tracking
* Structured logging system with log rotation
* Dashboard layout improvements

= 3.2.0 =
* Self-hosted API key system with create/revoke/manage
* Built-in REST API documentation page
* Public API endpoints for external integrations

= 3.1.0 =
* Self-hosted update checker for plugin auto-update
* CSS modular architecture (18 partials)

= 3.0.0 =
* Major architecture refactor — modular REST API (6 specialized modules)
* Reorganized connections into dedicated directory
* Unified version management with VERSION file
* Added enterprise-level architecture documentation
* Added bilingual README documentation

= 2.0.2 =
* Lark/Feishu webhook connection support
* Drag-drop card builder for notification templates
* Template persistence for custom card layouts

= 2.0.0 =
* Webhook connection system for payment notifications
* Connections admin page with grid layout

= 1.0.0 =
* Initial release
* MB Bank BDSD webhook integration
* WooCommerce payment gateway
* Admin dashboard

== Upgrade Notice ==

= 3.0.0 =
Major internal refactor. All public API URLs remain unchanged. Please deactivate and reactivate the plugin after updating.
