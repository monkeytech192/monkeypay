# Changelog

All notable changes to MonkeyPay will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.3.0] - 2025-03-21

### Added
- **Google OAuth Popup Flow**: Google sign-in now opens in a centered popup window (500×600px) instead of full-page redirect, preserving form context
  - Automatic popup-to-parent communication via `postMessage`
  - Graceful fallback to redirect mode if popup is blocked
- **Account Switching**: "Đổi tài khoản Google" button on both the success banner and pre-filled form, allowing users to re-authenticate with a different Google account
- **Transaction Management Page**: New admin page at `MonkeyPay > Giao Dịch` with real-time transaction tracking
  - Status badge pills (Pending / Completed / Failed / Expired)
  - Amount formatting with VND currency
  - Search and filter by status, date range
  - Auto-refresh with configurable interval
  - Transaction detail modal with timeline view
- **Structured Logging System**: `MonkeyPay_Logger` class for file-based structured logging
  - Separate channels: `api.log`, `error.log`
  - Log rotation and configurable log level

### Changed
- **Google OAuth UX**: Improved post-authentication flow — pre-filled register form now clearly shows which Google account is linked with option to switch
- **Onboarding Form**: Email field locked (read-only) with visual indicator when pre-filled from Google, password field automatically hidden for OAuth users
- **Dashboard Layout**: Updated dashboard with direct link to Transaction Management page
- **Architecture Diagram**: README updated to reflect v3.3.0 module additions

### Fixed
- Google OAuth registration failing when password validation was triggered unnecessarily
- Form state not fully resetting when switching between manual and Google registration
- Edge case where popup callback could fire twice on slow connections

---

## [3.2.0] - 2025-03-20

### Added
- **API Key System**: Self-hosted API key generation with `mkp_live_` prefix format
  - Create, revoke, and manage multiple API keys per organization
  - Key prefix display with masked middle section for security
  - Quick-copy functionality on dashboard pills and API keys table
  - Editable key labels/notes inline
- **API Documentation Page**: Built-in REST API docs at `MonkeyPay > Tài Liệu API`
  - Sidebar navigation with grouped sections (Bắt đầu, Endpoints, Webhooks)
  - Dark code blocks with language headers and copy buttons
  - Endpoint badges (GET/POST) with color coding
  - Request/response examples in side-by-side grid layout
  - Security alert boxes (info/warning) for best practices
- **Public REST Endpoints**: API-key-authenticated endpoints for external integrations
  - `GET /transactions/{tx_id}` — Check transaction status
  - `GET /merchant-gateways` — List active payment gateways

### Changed
- **Dashboard API Key Pills**: Redesigned from heavy green badges to minimal transparent pills with status dot and hover-to-copy
- **API Keys Table**: Masked key display (`mkp_live_xxxx...****`) with one-click copy of full prefix
- **Toast Notifications**: Unified error/success toast design with consistent styling across all admin pages

### Fixed
- Code blocks overflowing outside content container on API docs page
- Toast notification z-index inconsistency between error and success states
- API key creation error when attempting server-side generation (switched to client-side)
- `line-height: true` CSS syntax error in API docs stylesheet

### Security
- API key authentication via `X-Api-Key` header (recommended) or `?api_key=` query parameter
- Keys stored as SHA-256 hashes — full key shown only once at creation
- Rate limiting on `POST /transactions` endpoint
- Maximum 10 active API keys per site enforcement

---

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
