<div align="center">

# 🐵 MonkeyPay

**Automated Bank Transfer Payment Gateway for WordPress**

[![Version](https://img.shields.io/badge/version-3.4.0-6366f1.svg?style=for-the-badge)](./CHANGELOG.md)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759B.svg?style=for-the-badge&logo=wordpress)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-green.svg?style=for-the-badge)](./LICENSE)

Production-ready bank transfer payment gateway for WordPress — automated reconciliation, QR code generation, real-time webhooks, and WooCommerce integration.

<img src="https://flagcdn.com/24x18/gb.png" alt="English" width="24" height="18"> **English** · <img src="https://flagcdn.com/24x18/vn.png" alt="Tiếng Việt" width="24" height="18"> [Tiếng Việt](./README.vi.md)

</div>

---

## ✨ Features

| Category | Feature | Description |
|----------|---------|-------------|
| 🏦 **Payment** | Automated Verification | Real-time bank transaction matching via MB Bank BDSD webhook |
| 🛒 **E-commerce** | WooCommerce Gateway | Native payment gateway with automatic order status updates |
| 🔑 **API Keys** | Self-hosted Key System | `mkp_live_` prefixed keys with SHA-256 hashing |
| 📖 **Docs** | Built-in API Reference | Interactive REST API docs with code examples |
| 🔔 **Notifications** | Webhook Dispatcher | Multi-platform notifications (Lark/Feishu, Slack, Telegram) |
| 🎨 **Card Builder** | Drag & Drop Editor | Visual notification card template designer with live preview |
| 🔄 **Sync** | Server Sync Engine | 2-way sync with write-through caching & auto-reconciliation |
| 🗄️ **Database** | Custom Tables | 7 dedicated tables — no `wp_options` pollution |
| 🔒 **Security** | Enterprise-grade | HMAC verification, nonce validation, rate limiting |
| 🌐 **i18n** | Multi-language | Vietnamese & English |

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     MonkeyPay Plugin (v3.4.0)                │
│                                                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌────────────┐  │
│  │ Settings │  │   API    │  │ Gateways │  │   Bank     │  │
│  │ Engine   │  │  Keys    │  │  Module  │  │  + BDSD    │  │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └─────┬──────┘  │
│       │              │             │               │         │
│  ┌────▼──────────────▼─────────────▼───────────────▼──────┐  │
│  │               REST API Router (Thin)                   │  │
│  │            /wp-json/monkeypay/v1/*                     │  │
│  └────────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────┐  ┌─────────────┐  ┌───────────┐  ┌────────┐  │
│  │  Sync    │  │ WooCommerce │  │  Checkin  │  │ Custom │  │
│  │  Engine  │  │ Integration │  │  Bridge   │  │   DB   │  │
│  └────┬─────┘  └─────────────┘  └───────────┘  └────────┘  │
│       │                                                     │
│  ┌────▼────────────────────────────────┐                    │
│  │  Formatters (Lark · Slack · Tele)   │                    │
│  └─────────────────────────────────────┘                    │
└─────────────────────────────────────────────────────────────┘
```

> See [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) for full technical documentation.

---

## 🚀 Getting Started

### Prerequisites

| # | What | How to get |
|---|------|------------|
| 1 | **WordPress ≥ 5.8** | [wordpress.org](https://wordpress.org/) |
| 2 | **PHP ≥ 7.4** | Included with most hosting |
| 3 | **MB Bank Account** | Required for payment verification |
| 4 | **Organization API Key** | Register at [monkeytech192.vn](https://monkeytech192.vn) |

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
| BDSD | `/bdsd-transactions` | `GET` | Admin |
| Reconcile | `/reconcile` | `POST` | Admin |
| Gateways | `/gateways` | `CRUD` | Admin |
| Gateways | `/merchant-gateways` | `GET` | API Key |
| Sync | `/gateways/sync` | `POST` | Admin |
| Settings | `/settings` | `POST` | Admin |
| Bank | `/bank/summary` | `GET` | Admin |
| Bank | `/bank/history` | `GET` | Admin |
| Connections | `/connections` | `CRUD` | Admin |
| API Keys | `/api-keys` | `CRUD` | Admin |

---

## 🔐 API Key Authentication

MonkeyPay uses self-hosted API keys with `mkp_live_` prefix format.

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
│   │   ├── admin/              # 18+ modular CSS partials
│   │   │   ├── _tokens.css     # Design system tokens
│   │   │   └── ...
│   │   ├── admin.css           # @import dispatcher
│   │   └── payment.css         # Frontend payment styles
│   └── js/
│       └── admin/              # Page-specific JS modules
├── includes/
│   ├── api/                    # REST API modules (7 files)
│   │   ├── class-rest-settings.php
│   │   ├── class-rest-gateways.php
│   │   ├── class-rest-bank.php
│   │   ├── class-rest-auth.php
│   │   ├── class-rest-api-keys.php
│   │   ├── class-rest-connections.php
│   │   └── class-rest-bdsd.php
│   ├── connections/            # Platform formatters
│   ├── integrations/           # WooCommerce, Checkin Bridge
│   ├── class-monkeypay.php     # Plugin bootstrap
│   ├── class-monkeypay-db.php  # Database schema & migrations
│   ├── class-monkeypay-settings.php  # Centralized settings
│   └── class-monkeypay-sync.php      # Server sync engine
├── templates/                  # Admin page templates
├── docs/
│   └── ARCHITECTURE.md
├── CHANGELOG.md
├── VERSION                     # Version source of truth
└── monkeypay.php               # Plugin entry point
```

---

## ❓ FAQ

<details>
<summary><b>How does the sync engine work?</b></summary>
MonkeyPay uses a write-through caching pattern. When you save a gateway, it writes to the server AND local DB simultaneously. Background sync runs every 5 minutes as a safety net.
</details>

<details>
<summary><b>What happens on uninstall?</b></summary>
All 7 custom tables are dropped, all plugin options are removed. No orphaned data is left behind.
</details>

<details>
<summary><b>Is it safe to use in production?</b></summary>
Yes. MonkeyPay uses HMAC webhook verification, SHA-256 key hashing, rate limiting, and WordPress nonce validation. All settings are sanitized via typed schema callbacks.
</details>

---

## 📝 Changelog

See [CHANGELOG.md](./CHANGELOG.md) for full release history.

**Latest: v3.4.0** — Custom DB schema, centralized settings engine, server sync, BDSD REST API, transaction reconciliation.

---

## 📄 License

This plugin is licensed under the [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).

---

<div align="center">

Made with 🐵 by [Monkey Tech 192](https://monkeytech192.vn/)

[⬆ Back to Top](#-monkeypay)

</div>
