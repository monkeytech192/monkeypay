<div align="center">

# 🐵 MonkeyPay

**Cổng Thanh Toán Chuyển Khoản Tự Động cho WordPress**

[![Phiên bản](https://img.shields.io/badge/phiên_bản-3.0.0-blue.svg)](./CHANGELOG.md)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759B.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://php.net/)
[![Giấy phép](https://img.shields.io/badge/giấy_phép-GPL--2.0--or--later-green.svg)](./LICENSE)

[🇬🇧 English](./README.md)

</div>

---

## 📋 Mô tả

MonkeyPay tự động hóa quy trình xác minh thanh toán chuyển khoản ngân hàng cho WordPress. Plugin tích hợp với **MB Bank** BDSD (Biến Động Số Dư) webhook để xác nhận giao dịch tức thì — không cần kiểm tra thủ công.

### Tính năng chính

- 🏦 **Xác minh tự động** — Đối soát giao dịch thời gian thực qua webhook MB Bank
- 🛒 **Tích hợp WooCommerce** — Cổng thanh toán WC với tự động hoàn tất đơn hàng
- 🔔 **Thông báo webhook** — Gửi thông báo Lark/Feishu với mẫu card tùy chỉnh
- 🎨 **Kéo-thả Card Builder** — Trình soạn thảo trực quan cho giao diện thông báo
- 🔗 **Kết nối mở rộng** — Dispatcher webhook đa nền tảng (Slack, Telegram sắp ra mắt)
- 🔒 **Bảo mật cấp doanh nghiệp** — HMAC webhook, nonce validation, capability checks
- 🌐 **Đa ngôn ngữ** — Hỗ trợ tiếng Việt và tiếng Anh

---

## 🏗️ Kiến trúc

MonkeyPay v3.0.0 sử dụng **kiến trúc modular**:

```
REST API Router → 6 module chuyên biệt (Settings, Transactions, Gateways, Auth, Bank, Connections)
Connections → Dispatcher đa nền tảng → Formatters (Lark, Slack*, Telegram*)
Integrations → Tải có điều kiện (WooCommerce, Checkin Bridge)
```

> Xem [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) để biết chi tiết kỹ thuật đầy đủ.

---

## 📦 Cài đặt

### Từ file ZIP
1. Tải phiên bản mới nhất
2. Vào **WordPress Admin > Plugin > Thêm mới > Tải lên Plugin**
3. Upload file ZIP và kích hoạt

### Thủ công
1. Clone/copy vào `wp-content/plugins/monkeypay/`
2. Kích hoạt từ **WordPress Admin > Plugin**

---

## ⚙️ Cấu hình

1. Vào **MonkeyPay** trong thanh bên quản trị WordPress
2. Nhập **API Key** từ [monkeytech192.vn](https://monkeytech192.vn)
3. Cấu hình kết nối MB Bank cho xác minh thanh toán
4. (Tùy chọn) Thiết lập webhook Lark cho thông báo
5. (Tùy chọn) Bật cổng thanh toán WooCommerce

---

## 🔌 REST API

Tất cả endpoint nằm dưới `/wp-json/monkeypay/v1/`:

| Module | Endpoints | Xác thực |
|--------|-----------|----------|
| Health | `GET /health` | Công khai |
| Settings | `POST /settings` | Admin |
| Transactions | `POST /transactions`, `GET /transactions/{id}` | API Key |
| Gateways | `CRUD /gateways`, `GET /merchant-gateways` | Admin/Công khai |
| Bank | `GET /bank/summary`, `GET /bank/history` | Admin |
| Connections | `CRUD /connections`, `POST /connections/{id}/test` | Admin |

---

## 🔗 Nền tảng hỗ trợ

| Nền tảng | Trạng thái | Mô tả |
|----------|------------|-------|
| MB Bank | ✅ Hoạt động | BDSD webhook xác minh thanh toán |
| Lark/Feishu | ✅ Hoạt động | Card thông báo thanh toán |
| WooCommerce | ✅ Hoạt động | Tích hợp cổng thanh toán |
| Slack | 🔜 Sắp ra mắt | Thông báo thanh toán |
| Telegram | 🔜 Sắp ra mắt | Thông báo thanh toán |

---

## 📝 Lịch sử thay đổi

Xem [CHANGELOG.md](./CHANGELOG.md) để biết toàn bộ lịch sử phát hành.

---

## 📄 Giấy phép

Plugin được phân phối theo giấy phép [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).

---

## 🤝 Tác giả

Phát triển bởi **[Monkey Tech 192](https://monkeytech192.vn/)**
