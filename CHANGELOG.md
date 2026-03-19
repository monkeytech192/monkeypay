# Changelog

All notable changes to MonkeyPay will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.1.0] - 2025-03-19

### Added
- **Self-hosted Update Checker**: WordPress now auto-detects new plugin versions from `monkeytech192.vn`
- `MONKEYPAY_UPDATE_API` constant for remote version check endpoint

### Changed
- **CSS Modular Architecture**: Refactored monolithic `admin.css` (~4900 lines) into 18 partial files with dispatcher pattern
  - `assets/css/admin/` directory with `_tokens`, `_layout`, `_cards`, `_stats`, `_forms`, `_toggles`, `_buttons`, `_integrations`, `_toast`, `_spinner`, `_info-box`, `_header-nav`, `_onboarding`, `_pricing`, `_dashboard`, `_connections`, `_modals`, `_card-builder`
  - `admin.css` now serves as `@import` dispatcher (~40 lines)
- Updated `docs/ARCHITECTURE.md` with CSS architecture documentation

---

## [3.0.0] - 2025-03-19

### Changed
- **REST API Architecture**: Refactored monolithic REST API (1124 lines) into 6 specialized modules:
  - `class-rest-settings.php` — Plugin settings & health check
  - `class-rest-transactions.php` — Payment transaction management
  - `class-rest-gateways.php` — Gateway CRUD & merchant listing
  - `class-rest-auth.php` — Authentication, registration, password management
  - `class-rest-bank.php` — Bank summary, transaction history, BDSD webhook
  - `class-rest-connections.php` — Connection CRUD & test sending
- **Thin Router Pattern**: `class-monkeypay-rest-api.php` now delegates to modules (~40 lines)
- **Directory Organization**: Moved connection files into `includes/connections/`
- **Version Synchronization**: Unified plugin header and constant to `3.0.0`

### Added
- `includes/api/` directory — modular REST API architecture
- `includes/connections/` directory — platform connection handlers
- `VERSION` file — single source of truth for version management
- `docs/ARCHITECTURE.md` — enterprise-level architecture documentation
- `CHANGELOG.md` — standardized release history
- `README.md` — professional English documentation
- `README.vi.md` — Vietnamese documentation
- `readme.txt` — WordPress.org standard plugin page

### Fixed
- Version mismatch between plugin header (`1.0.0`) and constant (`2.0.2`)

---

## [2.0.2] - 2025-03-18

### Added
- Lark/Feishu webhook connection support
- Drag-drop card builder for Lark notification templates
- Live card preview with accurate Lark styling
- Connection events system (payment_success, payment_created)
- Template persistence — save/load custom card layouts

### Changed
- Backend `format_from_template()` — dynamic card building from JSON template
- Improved toast notification z-index above modals

### Fixed
- URL button preview order in card builder
- Card builder canvas overflow hiding variables
- Mobile responsive layout for template modal

---

## [2.0.1] - 2025-03-17

### Fixed
- MonkeyPay API URL fallback for existing installations
- Connection test reliability improvements

---

## [2.0.0] - 2025-03-15

### Added
- Webhook connection system for payment notifications
- Connections admin page with grid layout
- Platform meta system for extensible connection types
- `MonkeyPay_Connections` class with CRUD operations

### Changed
- Payment notification responsibility moved from checkin-mkt192-wp to MonkeyPay

---

## [1.0.0] - 2025-02-01

### Added
- Initial release
- MB Bank BDSD webhook integration
- WooCommerce payment gateway
- Admin dashboard with settings management
- Payment shortcode `[monkeypay_payment]`
- Checkin-MKT192-WP bridge integration
- REST API for transaction management
