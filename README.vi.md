<div align="center">

# 🐵 MonkeyPay

**Cổng thanh toán chuyển khoản ngân hàng tự động cho WordPress**

[![Phiên bản](https://img.shields.io/badge/phiên_bản-3.4.0-6366f1.svg?style=for-the-badge)](./CHANGELOG.md)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759B.svg?style=for-the-badge&logo=wordpress)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![Giấy phép](https://img.shields.io/badge/giấy_phép-GPL--2.0-green.svg?style=for-the-badge)](./LICENSE)

Cổng thanh toán chuyển khoản ngân hàng production-ready cho WordPress — đối soát tự động, tạo mã QR, webhook thông báo real-time, và tích hợp WooCommerce.

<img src="https://flagcdn.com/24x18/vn.png" alt="Tiếng Việt" width="24" height="18"> **Tiếng Việt** · <img src="https://flagcdn.com/24x18/gb.png" alt="English" width="24" height="18"> [English](./README.md)

</div>

---

## ✨ Tính năng

| Danh mục | Tính năng | Mô tả |
|----------|-----------|-------|
| 🏦 **Thanh toán** | Xác minh tự động | Khớp giao dịch ngân hàng real-time qua webhook BĐSD MB Bank |
| 🛒 **Thương mại** | WooCommerce Gateway | Cổng thanh toán native với tự động cập nhật trạng thái đơn |
| 🔑 **API Keys** | Hệ thống key nội bộ | Key prefix `mkp_live_`, hash SHA-256, tạo/thu hồi/quản lý |
| 📖 **Tài liệu** | API Docs tích hợp | Tài liệu API REST tương tác với ví dụ code |
| 🔔 **Thông báo** | Webhook Dispatcher | Đa nền tảng (Lark/Feishu, Slack, Telegram) |
| 🎨 **Card Builder** | Kéo thả trực quan | Thiết kế mẫu thẻ thông báo với xem trước live |
| 🔄 **Đồng bộ** | Sync Engine | Đồng bộ 2 chiều với write-through caching & tự đối soát |
| 🗄️ **Cơ sở dữ liệu** | Custom Tables | 7 bảng chuyên dụng — không gây rác `wp_options` |
| 🔒 **Bảo mật** | Cấp doanh nghiệp | Xác minh HMAC, nonce, giới hạn tốc độ |
| 🌐 **i18n** | Đa ngôn ngữ | Tiếng Việt & Tiếng Anh |

---

## 🏗️ Kiến trúc

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

> Xem [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) để biết chi tiết đầy đủ về kiến trúc.

---

## 🚀 Bắt đầu

### Yêu cầu

| # | Cần có | Cách lấy |
|---|--------|----------|
| 1 | **WordPress ≥ 5.8** | [wordpress.org](https://wordpress.org/) |
| 2 | **PHP ≥ 7.4** | Có sẵn trên hầu hết hosting |
| 3 | **Tài khoản MB Bank** | Dùng để xác minh thanh toán |
| 4 | **API Key tổ chức** | Đăng ký tại [monkeytech192.vn](https://monkeytech192.vn) |

### Cài đặt

#### Từ file ZIP
```bash
# 1. Tải phiên bản mới nhất
# 2. WordPress Admin > Plugins > Add New > Upload Plugin
# 3. Tải lên file ZIP và kích hoạt
```

#### Thủ công
```bash
cd wp-content/plugins/
git clone https://github.com/monkeytech192/monkeypay.git
# Kích hoạt từ WordPress Admin > Plugins
```

### Cấu hình

1. Vào **MonkeyPay** trong thanh bên admin WordPress
2. Nhập **API Key tổ chức** từ [monkeytech192.vn](https://monkeytech192.vn)
3. Cấu hình cổng thanh toán (MB Bank, TPBank, v.v.)
4. _(Tùy chọn)_ Thiết lập Lark/Feishu webhook cho thông báo
5. _(Tùy chọn)_ Bật cổng thanh toán WooCommerce

---

## 🔌 REST API

Tất cả endpoint nằm dưới `/wp-json/monkeypay/v1/`:

| Module | Endpoint | Phương thức | Xác thực |
|--------|----------|-------------|----------|
| Health | `/health` | `GET` | Công khai |
| Giao dịch | `/transactions/{tx_id}` | `GET` | API Key |
| Giao dịch | `/transactions` | `POST` | API Key |
| BĐSD | `/bdsd-transactions` | `GET` | Admin |
| Đối soát | `/reconcile` | `POST` | Admin |
| Cổng TT | `/gateways` | `CRUD` | Admin |
| Cổng TT | `/merchant-gateways` | `GET` | API Key |
| Đồng bộ | `/gateways/sync` | `POST` | Admin |
| Cài đặt | `/settings` | `POST` | Admin |
| Ngân hàng | `/bank/summary` | `GET` | Admin |
| Ngân hàng | `/bank/history` | `GET` | Admin |
| Kết nối | `/connections` | `CRUD` | Admin |
| API Keys | `/api-keys` | `CRUD` | Admin |

---

## 🔐 Xác thực API Key

MonkeyPay sử dụng hệ thống API key nội bộ với prefix `mkp_live_`.

**Khuyến nghị — Header:**
```bash
curl -X GET "https://yoursite.com/wp-json/monkeypay/v1/transactions/MKP_123" \
  -H "X-Api-Key: mkp_live_your_key_here"
```

**Thay thế — Query Parameter:**
```bash
curl -X GET "https://yoursite.com/wp-json/monkeypay/v1/transactions/MKP_123?api_key=mkp_live_your_key_here"
```

> [!WARNING]
> Bảo mật API key của bạn. Không bao giờ để lộ key trong frontend code, repository công khai, hoặc URL.

---

## 🔗 Nền tảng hỗ trợ

| Nền tảng | Trạng thái | Mô tả |
|----------|------------|-------|
| MB Bank | ✅ Hoạt động | Webhook BĐSD xác minh thanh toán tự động |
| TPBank | ✅ Hoạt động | Cổng thanh toán chuyển khoản |
| Lark/Feishu | ✅ Hoạt động | Thẻ thông báo rich với công cụ kéo thả |
| WooCommerce | ✅ Hoạt động | Tích hợp cổng thanh toán native |
| Slack | 🔜 Sắp ra | Thông báo thanh toán |
| Telegram | 🔜 Sắp ra | Thông báo thanh toán |

---

## 📂 Cấu trúc dự án

```
monkeypay/
├── assets/
│   ├── css/
│   │   ├── admin/              # 18+ file CSS partial module hóa
│   │   │   ├── _tokens.css     # Design system tokens
│   │   │   └── ...
│   │   ├── admin.css           # @import dispatcher
│   │   └── payment.css         # Style frontend thanh toán
│   └── js/
│       └── admin/              # JS module theo trang
├── includes/
│   ├── api/                    # REST API modules (7 file)
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
├── templates/                  # Template trang admin
├── docs/
│   └── ARCHITECTURE.md
├── CHANGELOG.md
├── VERSION                     # Nguồn phiên bản chính
└── monkeypay.php               # Entry point plugin
```

---

## ❓ Câu hỏi thường gặp

<details>
<summary><b>Sync engine hoạt động thế nào?</b></summary>
MonkeyPay dùng mô hình write-through caching. Khi lưu gateway, dữ liệu ghi đồng thời lên server VÀ DB nội bộ. Sync nền chạy mỗi 5 phút như lưới an toàn.
</details>

<details>
<summary><b>Khi gỡ cài đặt sẽ như thế nào?</b></summary>
Tất cả 7 bảng tùy chỉnh bị xóa, toàn bộ options plugin được dọn sạch. Không còn dữ liệu rác.
</details>

<details>
<summary><b>Có an toàn dùng cho production không?</b></summary>
Có. MonkeyPay sử dụng xác minh webhook HMAC, hash key SHA-256, giới hạn tốc độ, và WordPress nonce validation. Mọi settings đều được sanitize qua typed schema callbacks.
</details>

---

## 📝 Nhật ký thay đổi

Xem [CHANGELOG.md](./CHANGELOG.md) để biết lịch sử phát hành đầy đủ.

**Mới nhất: v3.4.0** — Custom DB schema, centralized settings engine, server sync, BDSD REST API, đối soát giao dịch.

---

## 📄 Giấy phép

Plugin này được cấp phép theo [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).

---

<div align="center">

Phát triển bởi 🐵 [Monkey Tech 192](https://monkeytech192.vn/)

[⬆ Về đầu trang](#-monkeypay)

</div>
