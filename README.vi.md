<div align="center">

# 🐵 MonkeyPay

**Cổng thanh toán chuyển khoản ngân hàng tự động cho WordPress**

[![Phiên bản](https://img.shields.io/badge/phiên_bản-3.3.0-6366f1.svg?style=flat-square)](./CHANGELOG.md)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759B.svg?style=flat-square&logo=wordpress)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg?style=flat-square&logo=php&logoColor=white)](https://php.net/)
[![Giấy phép](https://img.shields.io/badge/giấy_phép-GPL--2.0--or--later-green.svg?style=flat-square)](./LICENSE)

Cổng thanh toán chuyển khoản ngân hàng production-ready cho WordPress — đối soát tự động, tạo mã QR, webhook thông báo, và tích hợp WooCommerce.

🇻🇳 **Tiếng Việt**  |  🇬🇧 [English](./README.md)

</div>

---

## 📑 Mục lục

- [Tính năng](#-tính-năng)
- [Kiến trúc](#️-kiến-trúc)
- [Bắt đầu](#-bắt-đầu)
- [REST API](#-rest-api)
- [Xác thực API Key](#-xác-thực-api-key)
- [Nền tảng hỗ trợ](#-nền-tảng-hỗ-trợ)
- [Cấu trúc dự án](#-cấu-trúc-dự-án)
- [Nhật ký thay đổi](#-nhật-ký-thay-đổi)
- [Giấy phép](#-giấy-phép)
- [Đội ngũ](#-đội-ngũ)

---

## ✨ Tính năng

| Danh mục | Tính năng | Mô tả |
|----------|-----------|-------|
| 🏦 **Thanh toán** | Xác minh tự động | Khớp giao dịch ngân hàng real-time qua webhook BĐSD MB Bank |
| 🛒 **Thương mại** | WooCommerce Gateway | Cổng thanh toán native với tự động cập nhật trạng thái đơn hàng |
| 🔑 **API Keys** | Hệ thống key nội bộ | Key prefix `mkp_live_`, hash SHA-256, tạo/thu hồi/quản lý |
| 📖 **Tài liệu** | API Docs tích hợp | Tài liệu API REST tương tác với ví dụ code và nút sao chép |
| 🔔 **Thông báo** | Webhook Dispatcher | Thông báo đa nền tảng (Lark/Feishu, Slack, Telegram) |
| 🎨 **Card Builder** | Trình chỉnh sửa kéo thả | Thiết kế mẫu thẻ thông báo trực quan với xem trước live |
| 🔗 **Kết nối** | Đa nền tảng | Hệ thống kết nối mở rộng cho bất kỳ dịch vụ webhook nào |
| 🔒 **Bảo mật** | Cấp doanh nghiệp | Xác minh HMAC, nonce, giới hạn tốc độ, kiểm tra quyền |
| 🌐 **i18n** | Đa ngôn ngữ | Hỗ trợ tiếng Việt và tiếng Anh |

---

## 🏗️ Kiến trúc

MonkeyPay v3.3.0 sử dụng **kiến trúc module hóa** với các module REST API chuyên biệt:

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

> Xem [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) để biết chi tiết đầy đủ về kiến trúc kỹ thuật.

---

## 🚀 Bắt đầu

### Yêu cầu

- WordPress ≥ 5.8
- PHP ≥ 7.4
- Tài khoản Internet Banking MB Bank (để xác minh thanh toán)

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
| Cổng TT | `/gateways` | `CRUD` | Admin |
| Cổng TT | `/merchant-gateways` | `GET` | API Key |
| Cài đặt | `/settings` | `POST` | Admin |
| Ngân hàng | `/bank/summary` | `GET` | Admin |
| Ngân hàng | `/bank/history` | `GET` | Admin |
| Kết nối | `/connections` | `CRUD` | Admin |
| API Keys | `/api-keys` | `CRUD` | Admin |

---

## 🔐 Xác thực API Key

MonkeyPay sử dụng hệ thống API key nội bộ với prefix `mkp_live_`.

### Tạo Key

Vào **MonkeyPay > API Keys** trong admin panel để tạo và quản lý key.

### Sử dụng Key

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
│   │   ├── admin/            # 18 file CSS partial module hóa
│   │   │   ├── _tokens.css   # Design system tokens
│   │   │   ├── _dashboard.css
│   │   │   ├── _api-docs.css
│   │   │   └── ...
│   │   ├── admin.css         # @import dispatcher
│   │   └── payment.css       # Style frontend thanh toán
│   └── js/
│       └── admin/            # JS module theo trang
├── includes/
│   ├── api/                  # REST API modules (6 file)
│   ├── connections/          # Platform formatters (Lark, Slack)
│   ├── class-monkeypay.php   # Plugin bootstrap
│   └── ...
├── templates/                # Template trang admin
├── docs/
│   └── ARCHITECTURE.md       # Tài liệu kiến trúc kỹ thuật
├── CHANGELOG.md              # Lịch sử phát hành
├── VERSION                   # Nguồn phiên bản chính
└── monkeypay.php             # Entry point plugin
```

---

## 📝 Nhật ký thay đổi

Xem [CHANGELOG.md](./CHANGELOG.md) để biết lịch sử phát hành đầy đủ.

**Mới nhất: v3.3.0** — Google OAuth Popup, Đổi tài khoản Google, Quản lý Giao dịch, Structured Logging.

---

## 📄 Giấy phép

Plugin này được cấp phép theo [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).

---

## 🤝 Đội ngũ

Phát triển bởi **[Monkey Tech 192](https://monkeytech192.vn/)**

[⬆ Về đầu trang](#-monkeypay)
