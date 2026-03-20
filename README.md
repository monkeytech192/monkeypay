<div align="center">

# 🐵 MonkeyPay

**Automated Bank Transfer Payment Gateway for WordPress**

[![Version](https://img.shields.io/badge/version-3.3.0-6366f1.svg?style=flat-square)](./CHANGELOG.md)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759B.svg?style=flat-square&logo=wordpress)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg?style=flat-square&logo=php&logoColor=white)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green.svg?style=flat-square)](./LICENSE)

Production-ready bank transfer payment gateway for WordPress — automated reconciliation, QR code generation, webhook notifications, and WooCommerce integration.

🇻🇳 [Tiếng Việt](./README.vi.md)  |  🇬🇧 **English**

</div>

---

## 📑 Table of Contents

- [Features](#-features)
- [Architecture](#️-architecture)
- [Getting Started](#-getting-started)
- [REST API](#-rest-api)
- [API Key Authentication](#-api-key-authentication)
- [Supported Platforms](#-supported-platforms)
- [Project Structure](#-project-structure)
- [Changelog](#-changelog)
- [License](#-license)
- [Credits](#-credits)

---

## ✨ Features

| Category | Feature | Description |
|----------|---------|-------------|
| 🏦 **Payment** | Automated Verification | Real-time bank transaction matching via MB Bank BDSD webhook |
| 🛒 **E-commerce** | WooCommerce Gateway | Native payment gateway with automatic order status updates |
| 🔑 **API Keys** | Self-hosted Key System | `mkp_live_` prefixed keys with SHA-256 hashing, create/revoke/manage |
| 📖 **Documentation** | Built-in API Docs | Interactive REST API reference with code examples and copy buttons |
| 🔔 **Notifications** | Webhook Dispatcher | Platform-agnostic notifications (Lark/Feishu, Slack, Telegram) |
| 🎨 **Card Builder** | Drag & Drop Editor | Visual notification card template designer with live preview |
| 🔗 **Connections** | Multi-platform | Extensible connection system for any webhook-capable service |
| 🔒 **Security** | Enterprise-grade | HMAC verification, nonce validation, rate limiting, capability checks |
| 🌐 **i18n** | Multi-language | Vietnamese and English support |

---

## 🏗️ Architecture

MonkeyPay v3.3.0 uses a **modular architecture** with specialized REST API modules:

```
┌─────────────────────────────────────────────────────────┐
│                    MonkeyPay Plugin                      │
│                                                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌────────┐  │
│  │ Settings │  │   API    │  │Gateways  │  │  Bank  │  │
│  │ Module   │  │  Keys    │  │ Module   │  │ Module │  │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └───┬────┘  │
│       │              │             │             │       │
│  ┌────▼──────────────▼─────────────▼─────────────▼────┐  │
│  │              REST API Router (Thin)                │  │
│  │           /wp-json/monkeypay/v1/*                  │  │
│  └────────────────────────────────────────────────────┘  │
│                                                         │
│  ┌──────────────┐  ┌─────────────┐  ┌───────────────┐   │
│  │ Connections  │  │ WooCommerce │  │  Checkin MKT  │   │
│  │  Dispatcher  │  │ Integration │  │    Bridge     │   │
│  └──────┬───────┘  └─────────────┘  └───────────────┘   │
│         │                                               │
│  ┌──────▼───────────────────────────────┐               │
│  │  Formatters (Lark · Slack · Telegram)│               │
│  └──────────────────────────────────────┘               │
└─────────────────────────────────────────────────────────┘
```

> See [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) for full technical documentation.

---

## 🚀 Getting Started

### Requirements

- WordPress ≥ 5.8
- PHP ≥ 7.4
- MB Bank Internet Banking account (for payment verification)

### Installation

#### From ZIP
```bash
# 1. Download the latest release
# 2. WordPress Admin > Plugins > Add New > Upload Plugin
# 3. Upload ZIP and activate
```

#### Manual
```bash
cd wp-content/plugins/
git clone https://github.com/monkeytech192/monkeypay.git
# Activate from WordPress Admin > Plugins
```

### Configuration

1. Go to **MonkeyPay** in the WordPress admin sidebar
2. Enter your **Organization API Key** from [monkeytech192.vn](https://monkeytech192.vn)
3. Configure payment gateways (MB Bank, TPBank, etc.)
4. _(Optional)_ Set up Lark/Feishu webhook for notifications
5. _(Optional)_ Enable WooCommerce payment gateway

---

## 🔌 REST API

All endpoints are under `/wp-json/monkeypay/v1/`:

| Module | Endpoint | Method | Auth |
|--------|----------|--------|------|
| Health | `/health` | `GET` | Public |
| Transactions | `/transactions/{tx_id}` | `GET` | API Key |
| Transactions | `/transactions` | `POST` | API Key |
| Gateways | `/gateways` | `CRUD` | Admin |
| Gateways | `/merchant-gateways` | `GET` | API Key |
| Settings | `/settings` | `POST` | Admin |
| Bank | `/bank/summary` | `GET` | Admin |
| Bank | `/bank/history` | `GET` | Admin |
| Connections | `/connections` | `CRUD` | Admin |
| API Keys | `/api-keys` | `CRUD` | Admin |

---

## 🔐 API Key Authentication

MonkeyPay uses self-hosted API keys with `mkp_live_` prefix format.

### Creating Keys

Navigate to **MonkeyPay > API Keys** in the admin panel to create and manage keys.

### Using Keys

**Recommended — Header:**
```bash
curl -X GET "https://yoursite.com/wp-json/monkeypay/v1/transactions/MKP_123" \
  -H "X-Api-Key: mkp_live_your_key_here"
```

**Alternative — Query Parameter:**
```bash
curl -X GET "https://yoursite.com/wp-json/monkeypay/v1/transactions/MKP_123?api_key=mkp_live_your_key_here"
```

> [!WARNING]
> Keep your API key secret. Never expose it in frontend code, public repositories, or URLs.

---

## 🔗 Supported Platforms

| Platform | Status | Description |
|----------|--------|-------------|
| MB Bank | ✅ Active | BDSD webhook for automated payment verification |
| TPBank | ✅ Active | Bank transfer gateway |
| Lark/Feishu | ✅ Active | Rich notification cards with drag-drop builder |
| WooCommerce | ✅ Active | Native payment gateway integration |
| Slack | 🔜 Planned | Payment notifications |
| Telegram | 🔜 Planned | Payment notifications |

---

## 📂 Project Structure

```
monkeypay/
├── assets/
│   ├── css/
│   │   ├── admin/            # 18 modular CSS partials
│   │   │   ├── _tokens.css   # Design system tokens
│   │   │   ├── _dashboard.css
│   │   │   ├── _api-docs.css
│   │   │   └── ...
│   │   ├── admin.css         # @import dispatcher
│   │   └── payment.css       # Frontend payment styles
│   └── js/
│       └── admin/            # Page-specific JS modules
├── includes/
│   ├── api/                  # REST API modules (6 files)
│   ├── connections/          # Platform formatters (Lark, Slack)
│   ├── class-monkeypay.php   # Plugin bootstrap
│   └── ...
├── templates/                # Admin page templates
├── docs/
│   └── ARCHITECTURE.md       # Technical architecture docs
├── CHANGELOG.md              # Release history
├── VERSION                   # Version source of truth
└── monkeypay.php             # Plugin entry point
```

---

## 📝 Changelog

See [CHANGELOG.md](./CHANGELOG.md) for full release history.

**Latest: v3.3.0** — Google OAuth Popup, Account Switching, Transaction Management, Structured Logging.

---

## 📄 License

This plugin is licensed under the [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).

---

## 🤝 Credits

Developed by **[Monkey Tech 192](https://monkeytech192.vn/)**

[⬆ Back to Top](#-monkeypay)
