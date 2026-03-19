<div align="center">

# 🐵 MonkeyPay

**Automated Bank Transfer Payment Gateway for WordPress**

[![Version](https://img.shields.io/badge/version-3.0.0-blue.svg)](./CHANGELOG.md)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759B.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green.svg)](./LICENSE)

[🇻🇳 Tiếng Việt](./README.vi.md)

</div>

---

## 📋 Description

MonkeyPay automates bank transfer payment processing for WordPress sites. It integrates with **MB Bank** BDSD (Biến Động Số Dư) webhooks to instantly verify incoming payments — no manual checking required.

### Key Features

- 🏦 **Automated Payment Verification** — Real-time bank transaction matching via MB Bank webhook
- 🛒 **WooCommerce Integration** — Native WC payment gateway with automatic order completion
- 🔔 **Notification System** — Lark/Feishu webhook notifications with customizable card templates
- 🎨 **Drag-Drop Card Builder** — Visual editor for notification card layouts
- 🔗 **Extensible Connections** — Platform-agnostic webhook dispatcher (Slack, Telegram coming soon)
- 🔒 **Enterprise Security** — HMAC webhook verification, nonce validation, capability checks
- 🌐 **Multi-language** — Vietnamese and English support

---

## 🏗️ Architecture

MonkeyPay v3.0.0 uses a **modular architecture**:

```
REST API Router → 6 specialized modules (Settings, Transactions, Gateways, Auth, Bank, Connections)
Connections → Platform-agnostic dispatcher → Formatters (Lark, Slack*, Telegram*)
Integrations → Conditional loading (WooCommerce, Checkin Bridge)
```

> See [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) for full technical documentation.

---

## 📦 Installation

### From ZIP
1. Download the latest release
2. Go to **WordPress Admin > Plugins > Add New > Upload Plugin**
3. Upload the ZIP file and activate

### Manual
1. Clone/copy to `wp-content/plugins/monkeypay/`
2. Activate from **WordPress Admin > Plugins**

---

## ⚙️ Configuration

1. Go to **MonkeyPay** in the WordPress admin sidebar
2. Enter your **API Key** from [monkeytech192.vn](https://monkeytech192.vn)
3. Configure MB Bank connection for payment verification
4. (Optional) Set up Lark webhook for notifications
5. (Optional) Enable WooCommerce gateway

---

## 🔌 REST API

All endpoints are under `/wp-json/monkeypay/v1/`:

| Module | Endpoints | Auth |
|--------|-----------|------|
| Health | `GET /health` | Public |
| Settings | `POST /settings` | Admin |
| Transactions | `POST /transactions`, `GET /transactions/{id}` | API Key |
| Gateways | `CRUD /gateways`, `GET /merchant-gateways` | Admin/Public |
| Bank | `GET /bank/summary`, `GET /bank/history` | Admin |
| Connections | `CRUD /connections`, `POST /connections/{id}/test` | Admin |

---

## 🔗 Supported Platforms

| Platform | Status | Description |
|----------|--------|-------------|
| MB Bank | ✅ Active | BDSD webhook for payment verification |
| Lark/Feishu | ✅ Active | Payment notification cards |
| WooCommerce | ✅ Active | Payment gateway integration |
| Slack | 🔜 Planned | Payment notifications |
| Telegram | 🔜 Planned | Payment notifications |

---

## 📝 Changelog

See [CHANGELOG.md](./CHANGELOG.md) for full release history.

---

## 📄 License

This plugin is licensed under the [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).

---

## 🤝 Credits

Developed by **[Monkey Tech 192](https://monkeytech192.vn/)**
