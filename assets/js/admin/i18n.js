/**
 * MonkeyPay Admin — i18n Dictionary & Dark Mode Bootstrap
 *
 * Central translation module. All UI strings go through MP.__().
 * Loaded BEFORE all other page modules so they can use it immediately.
 *
 * @package MonkeyPay
 * @since   4.2.0
 */

window.MonkeyPay = window.MonkeyPay || {};

(function () {
    'use strict';

    var MP     = window.MonkeyPay;
    var config = window.monkeypayAdmin || {};

    // Current language — set from PHP option, fallback 'vi'
    MP.lang = config.language || 'vi';

    // Timezone — set from PHP option, fallback 'Asia/Ho_Chi_Minh'
    MP.timezone = config.timezone || 'Asia/Ho_Chi_Minh';

    // Dark mode — 'auto' | 'light' | 'dark'
    MP.darkMode = config.darkMode || 'light';

    // ─── Translation Dictionaries ─────────────────────

    var DICT = {
        vi: {
            // Dashboard — page header
            'dashboard':                  'Dashboard',
            'dashboard_desc':             'Tổng quan hoạt động thanh toán và trạng thái hệ thống',

            // Membership card
            'organization':               'TỔ CHỨC',
            'card_org':                   'TỔ CHỨC',
            'card_api_key':               'API KEY',
            'card_holder':                'CHỦ TÀI KHOẢN',
            'card_expiry':                'HẾT HẠN',
            'account_holder':             'CHỦ TÀI KHOẢN',
            'expires':                    'HẾT HẠN',
            'not_setup':                  'Chưa thiết lập',
            'forever':                    'Vĩnh viễn',
            'no_api_key':                 'Chưa có API Key',

            // Quick actions
            'quick_actions':              'Thao tác nhanh',
            'qa_connections':             'Kết nối',
            'qa_gateways':                'Cổng thanh toán',
            'connections':                'Kết nối',
            'payment_gateways':           'Cổng thanh toán',
            'loading':                    'Đang tải...',

            // Stats
            'money_in':                   'Tiền vào',
            'money_out':                  'Tiền ra',
            'total_transactions':         'Tổng giao dịch',
            'tx_unit':                    'GD',

            // Navigation tabs
            'nav_dashboard':              'Dashboard',
            'nav_integrations':           'Tích Hợp',
            'nav_connections':            'Kết Nối',
            'nav_overview':               'Tổng quan',
            'nav_transactions':           'Giao dịch',
            'nav_apikeys':                'API Keys',
            'nav_gateways':               'Cổng Thanh Toán',
            'nav_account':                'Tài Khoản',
            'nav_settings':               'Cài Đặt',
            'nav_api_keys':               'API Keys',
            'nav_pricing':                'Bảng Giá',

            // Connection flow
            'connection_status':          'Trạng thái kết nối',
            'recheck':                    'Kiểm tra lại',
            'copy':                       'Sao chép',
            'copied':                     'Đã sao chép!',

            // Chart
            'cashflow_chart':             'Biểu đồ dòng tiền',
            'cashflow_chart_title':       'Biểu đồ dòng tiền',
            'credit':                     'Tiền vào',
            'debit':                      'Tiền ra',
            'net_cashflow':               'Dòng tiền ròng',
            'apply':                      'Áp dụng',
            'date_range':                 'Khoảng thời gian',
            'date_custom':                'Khoảng thời gian',
            'refresh_data':               'Làm mới dữ liệu',

            // Date pills
            'today':                      'Hôm nay',
            'yesterday':                  'Hôm qua',
            '7days':                      '7 ngày',
            '30days':                     '30 ngày',
            'this_week':                  'Tuần này',
            'last_week':                  'Tuần trước',
            'this_month':                 'Tháng này',
            'last_month':                 'Tháng trước',
            'date_today':                 'Hôm nay',
            'date_yesterday':             'Hôm qua',
            'date_7days':                 '7 ngày',
            'date_30days':                '30 ngày',
            'date_this_week':             'Tuần này',
            'date_last_week':             'Tuần trước',
            'date_this_month':            'Tháng này',
            'date_last_month':            'Tháng trước',

            // Transactions table
            'recent_transactions':        'Giao dịch gần đây',
            'view_all':                   'Xem tất cả',
            'loading_transactions':       'Đang tải giao dịch...',
            'no_transactions':            'Không có giao dịch nào trong khoảng thời gian này',
            'col_time':                   'Thời gian',
            'col_desc':                   'Mô tả',
            'col_amount':                 'Số tiền',
            'col_balance':                'Số dư',

            // Transaction status
            'status_pending':             'Đang xử lý',
            'status_failed':              'Thất bại',
            'status_success':             'Thành công',

            // Modals
            'create_api_key':             'Tạo API Key mới',
            'modal_create_key':           'Tạo API Key mới',
            'key_label':                  'Tên key (tuỳ chọn)',
            'modal_key_label':            'Tên key (tuỳ chọn)',
            'key_label_placeholder':      'Ví dụ: Website chính',
            'modal_key_placeholder':      'Ví dụ: Website chính',
            'cancel':                     'Huỷ',
            'create_key':                 'Tạo Key',
            'key_created':                'API Key đã tạo',
            'modal_key_created':          'API Key đã tạo',
            'copy_key_warning':           'Hãy sao chép key này ngay. Bạn sẽ không thể xem lại.',
            'modal_key_warning':          'Hãy sao chép key này ngay. Bạn sẽ không thể xem lại.',
            'close':                      'Đóng',
            'modal_add_gateway':          'Thêm cổng thanh toán',
            'add_gateway':                'Thêm cổng',
            'bank':                       'Ngân hàng',
            'select_bank':                'Chọn ngân hàng...',
            'account_number':             'Số tài khoản',
            'enter_account_number':       'Nhập số tài khoản',
            'account_number_placeholder': 'Nhập số tài khoản',
            'account_name':               'Chủ tài khoản',
            'account_holder_placeholder': 'VD: NGUYEN VAN A',
            'account_name_placeholder':   'VD: NGUYEN VAN A',
            'add_gateway_btn':            'Thêm cổng',

            // Settings page
            'api_config':                 'Cấu Hình API',
            'server_url':                 'MonkeyPay Server URL',
            'server_url_hint':            'Server được cấu hình tự động. Không cần thay đổi.',
            'api_key':                    'API Key',
            'api_key_hint':               'API Key được tạo tự động khi đăng ký tổ chức',
            'webhook_secret':             'Webhook Secret',
            'webhook_secret_hint':        'Dùng để verify HMAC-SHA256 của incoming webhook',
            'admin_secret':               'Admin Secret',
            'admin_secret_hint':          'Được lấy tự động từ server khi đăng ký/đăng nhập.',
            'save_settings':              'Lưu Cài Đặt',
            'usage_title':                'Thông Tin Gói & Sử Dụng',
            'loading_info':               'Đang tải thông tin...',

            // Display settings
            'display_settings':           'Tùy Chọn Hiển Thị',
            'display_settings_desc':      'Cấu hình múi giờ, ngôn ngữ và giao diện',
            'timezone':                   'Múi giờ',
            'language':                   'Ngôn ngữ',
            'dark_mode':                  'Chế độ giao diện',
            'dark_mode_light':            'Sáng',
            'dark_mode_dark':             'Tối',
            'dark_mode_auto':             'Tự động',

            // Quick Display (Dashboard sidebar)
            'quick_display':              'Giao diện',
            'light_mode':                 'Sáng',
            'dark_mode_short':            'Tối',

            // Usage stats strings
            'requests_used':              'Request sử dụng',
            'remaining':                  'Còn lại',
            'request_unit':               'request',
            'unlimited':                  'Không giới hạn',
            'payment_gateways_stat':      'Cổng thanh toán',
            'plan_price':                 'Giá gói',
            'free':                       'Miễn phí',
            'per_month':                  'đ/tháng',
            'billing_cycle':              'Chu kỳ',

            // Integration toggles
            'integration_on':             'Đang bật',
            'integration_off':            'Đang tắt',
            'integration_enabled':        'Tích hợp đã bật',
            'integration_disabled':       'Tích hợp đã tắt',
            'connected':                  'Đã kết nối',
            'not_connected':              'Chưa kết nối',
            'save_error':                 'Lỗi lưu cài đặt',

            // Toast / common
            'connecting':                 'Đang kết nối...',
            'disconnected':               'Mất kết nối',
            'saved':                      'Đã lưu cài đặt',
            'error':                      'Có lỗi xảy ra',
            'enter_org_name':             'Vui lòng nhập tên tổ chức',
            'enter_api_url':              'Vui lòng nhập và lưu MonkeyPay Server URL trước',
            'org_created':                'Tổ chức đã được tạo thành công!',
            'org_create_error':           'Lỗi tạo tổ chức',
            'load_usage_error':           'Không thể tải thông tin sử dụng',
        },

        en: {
            'dashboard':                  'Dashboard',
            'dashboard_desc':             'Payment activity overview and system status',

            'organization':               'ORGANIZATION',
            'card_org':                   'ORGANIZATION',
            'card_api_key':               'API KEY',
            'card_holder':                'ACCOUNT HOLDER',
            'card_expiry':                'EXPIRES',
            'account_holder':             'Account Holder',
            'expires':                    'EXPIRES',
            'not_setup':                  'Not set up',
            'forever':                    'Lifetime',
            'no_api_key':                 'No API Key',

            'quick_actions':              'Quick Actions',
            'qa_connections':             'Connections',
            'qa_gateways':                'Payment Gateways',
            'connections':                'Connections',
            'payment_gateways':           'Payment Gateways',
            'loading':                    'Loading...',

            'money_in':                   'Money In',
            'money_out':                  'Money Out',
            'total_transactions':         'Total Transactions',
            'tx_unit':                    'txn',

            // Navigation tabs
            'nav_dashboard':              'Dashboard',
            'nav_integrations':           'Integrations',
            'nav_connections':            'Connections',
            'nav_overview':               'Overview',
            'nav_transactions':           'Transactions',
            'nav_apikeys':                'API Keys',
            'nav_gateways':               'Payment Gateways',
            'nav_account':                'Account',
            'nav_settings':               'Settings',
            'nav_api_keys':               'API Keys',
            'nav_pricing':                'Pricing',

            'connection_status':          'Connection Status',
            'recheck':                    'Re-check',
            'copy':                       'Copy',
            'copied':                     'Copied!',

            'cashflow_chart':             'Cash Flow Chart',
            'cashflow_chart_title':       'Cash Flow Chart',
            'credit':                     'Credit',
            'debit':                      'Debit',
            'net_cashflow':               'Net Cash Flow',
            'apply':                      'Apply',
            'date_range':                 'Date Range',
            'date_custom':                'Date Range',
            'refresh_data':               'Refresh Data',

            'today':                      'Today',
            'yesterday':                  'Yesterday',
            '7days':                      '7 days',
            '30days':                     '30 days',
            'this_week':                  'This Week',
            'last_week':                  'Last Week',
            'this_month':                 'This Month',
            'last_month':                 'Last Month',
            'date_today':                 'Today',
            'date_yesterday':             'Yesterday',
            'date_7days':                 '7 days',
            'date_30days':                '30 days',
            'date_this_week':             'This Week',
            'date_last_week':             'Last Week',
            'date_this_month':            'This Month',
            'date_last_month':            'Last Month',

            'recent_transactions':        'Recent Transactions',
            'view_all':                   'View All',
            'loading_transactions':       'Loading transactions...',
            'no_transactions':            'No transactions in this time period',
            'col_time':                   'Time',
            'col_desc':                   'Description',
            'col_amount':                 'Amount',
            'col_balance':                'Balance',

            // Transaction status
            'status_pending':             'Pending',
            'status_failed':              'Failed',
            'status_success':             'Success',

            'create_api_key':             'Create New API Key',
            'modal_create_key':           'Create New API Key',
            'key_label':                  'Key name (optional)',
            'modal_key_label':            'Key name (optional)',
            'key_label_placeholder':      'e.g. Main Website',
            'modal_key_placeholder':      'e.g. Main Website',
            'cancel':                     'Cancel',
            'create_key':                 'Create Key',
            'key_created':                'API Key Created',
            'modal_key_created':          'API Key Created',
            'copy_key_warning':           'Copy this key now. You won\'t be able to view it again.',
            'modal_key_warning':          'Copy this key now. You won\'t be able to view it again.',
            'close':                      'Close',
            'modal_add_gateway':          'Add Payment Gateway',
            'add_gateway':                'Add Gateway',
            'bank':                       'Bank',
            'select_bank':                'Select bank...',
            'account_number':             'Account Number',
            'enter_account_number':       'Enter account number',
            'account_number_placeholder': 'Enter account number',
            'account_name':               'Account Name',
            'account_holder_placeholder': 'e.g. NGUYEN VAN A',
            'account_name_placeholder':   'e.g. NGUYEN VAN A',
            'add_gateway_btn':            'Add Gateway',

            'api_config':                 'API Configuration',
            'server_url':                 'MonkeyPay Server URL',
            'server_url_hint':            'Auto-configured server. No change needed.',
            'api_key':                    'API Key',
            'api_key_hint':               'API Key auto-created when registering organization',
            'webhook_secret':             'Webhook Secret',
            'webhook_secret_hint':        'Used to verify HMAC-SHA256 of incoming webhooks',
            'admin_secret':               'Admin Secret',
            'admin_secret_hint':          'Auto-fetched from server on registration/login.',
            'save_settings':              'Save Settings',
            'usage_title':                'Plan & Usage Info',
            'loading_info':               'Loading info...',

            'display_settings':           'Display Settings',
            'display_settings_desc':      'Configure timezone, language, and appearance',
            'timezone':                   'Timezone',
            'language':                   'Language',
            'dark_mode':                  'Appearance',
            'dark_mode_light':            'Light',
            'dark_mode_dark':             'Dark',
            'dark_mode_auto':             'Auto',

            'quick_display':              'Display',
            'light_mode':                 'Light',
            'dark_mode_short':            'Dark',

            'requests_used':              'Requests Used',
            'remaining':                  'Remaining',
            'request_unit':               'requests',
            'unlimited':                  'Unlimited',
            'payment_gateways_stat':      'Payment Gateways',
            'plan_price':                 'Plan Price',
            'free':                       'Free',
            'per_month':                  '/month',
            'billing_cycle':              'Billing Cycle',

            'integration_on':             'Enabled',
            'integration_off':            'Disabled',
            'integration_enabled':        'Integration enabled',
            'integration_disabled':       'Integration disabled',
            'connected':                  'Connected',
            'not_connected':              'Not connected',
            'save_error':                 'Failed to save settings',

            'connecting':                 'Connecting...',
            'disconnected':               'Disconnected',
            'saved':                      'Settings saved',
            'error':                      'An error occurred',
            'enter_org_name':             'Please enter organization name',
            'enter_api_url':              'Please enter and save MonkeyPay Server URL first',
            'org_created':                'Organization created successfully!',
            'org_create_error':           'Failed to create organization',
            'load_usage_error':           'Could not load usage information',
        },
    };

    // ─── Translate Function ──────────────────────────

    /**
     * Get translated string for current language.
     * @param {string} key - dictionary key
     * @param {Object} [replacements] - optional {token: value} for interpolation
     * @returns {string}
     */
    MP.__ = function (key, replacements) {
        var lang = MP.lang || 'vi';
        var dict = DICT[lang] || DICT.vi;
        var str  = dict[key] || DICT.vi[key] || key;

        if (replacements) {
            Object.keys(replacements).forEach(function (token) {
                str = str.replace(new RegExp('\\{' + token + '\\}', 'g'), replacements[token]);
            });
        }
        return str;
    };

    // ─── Timezone-Aware Date Helpers ─────────────────

    /**
     * Get current date/time in configured timezone.
     * Returns a Date-like string formatter.
     */
    MP.tzNow = function () {
        var now = new Date();
        var formatter = new Intl.DateTimeFormat('en-CA', {
            timeZone: MP.timezone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        });
        return formatter.format(now);
    };

    /**
     * Get today's date in yyyy-mm-dd using configured timezone.
     */
    MP.localToday = function () {
        var now = new Date();
        var parts = new Intl.DateTimeFormat('en-CA', {
            timeZone: MP.timezone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        }).formatToParts(now);

        var y = '', m = '', d = '';
        parts.forEach(function (p) {
            if (p.type === 'year')  y = p.value;
            if (p.type === 'month') m = p.value;
            if (p.type === 'day')   d = p.value;
        });
        return y + '-' + m + '-' + d;
    };

    /**
     * Format a raw transaction time with configured timezone.
     * Output: dd/mm/yy hh:mm:ss
     */
    MP.formatTxTime = function (raw) {
        if (!raw) return '—';

        // If already in dd/mm/yyyy format from API, shorten year
        if (/^\d{2}\/\d{2}\/\d{4}/.test(raw)) {
            var slashParts = raw.split(' ');
            var dateBits = slashParts[0].split('/');
            var shortYear = dateBits[2].substring(2);
            var time = slashParts[1] || '';
            return dateBits[0] + '/' + dateBits[1] + '/' + shortYear + (time ? ' ' + time : '');
        }

        try {
            var d = new Date(raw);
            if (isNaN(d.getTime())) return raw;

            var formatted = new Intl.DateTimeFormat(MP.lang === 'en' ? 'en-GB' : 'vi-VN', {
                timeZone: MP.timezone,
                day: '2-digit',
                month: '2-digit',
                year: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            }).format(d);

            return formatted;
        } catch (e) {
            return raw;
        }
    };

    // ─── Dark Mode Bootstrap ─────────────────────────

    function applyDarkMode() {
        var mode = MP.darkMode;
        var root = document.documentElement;

        if (mode === 'auto') {
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            root.setAttribute('data-mp-theme', prefersDark ? 'dark' : 'light');
        } else {
            root.setAttribute('data-mp-theme', mode);
        }
    }

    // Apply immediately (before DOM ready) so no flash
    applyDarkMode();

    // Listen for system preference changes in auto mode
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
            if (MP.darkMode === 'auto') applyDarkMode();
        });
    }

    // Expose for settings toggle
    MP.applyDarkMode = applyDarkMode;

    // ─── i18n: Apply language to PHP-rendered elements ─

    /**
     * Apply i18n to data-i18n attributes on existing DOM elements.
     * Call after DOM ready.
     */
    MP.applyI18n = function () {
        var elements = document.querySelectorAll('[data-i18n]');
        elements.forEach(function (el) {
            var key = el.getAttribute('data-i18n');
            if (key) {
                el.textContent = MP.__(key);
            }
        });
        // Also handle data-i18n-placeholder
        var placeholders = document.querySelectorAll('[data-i18n-placeholder]');
        placeholders.forEach(function (el) {
            var key = el.getAttribute('data-i18n-placeholder');
            if (key) {
                el.placeholder = MP.__(key);
            }
        });
        // Also handle data-i18n-title
        var titles = document.querySelectorAll('[data-i18n-title]');
        titles.forEach(function (el) {
            var key = el.getAttribute('data-i18n-title');
            if (key) {
                el.title = MP.__(key);
            }
        });
    };

    // ─── Nav Prefetch + Smooth Transition ─────────────

    /**
     * Prefetch nav links on hover for near-instant page loads.
     * Add smooth fade-out transition on nav click.
     */
    function initNavPrefetch() {
        var prefetchedUrls = {};
        var navItems = document.querySelectorAll('.monkeypay-nav-item');

        navItems.forEach(function (link) {
            // Prefetch on hover (only once per URL)
            link.addEventListener('mouseenter', function () {
                var href = link.getAttribute('href');
                if (!href || prefetchedUrls[href]) return;
                prefetchedUrls[href] = true;

                var prefetchLink = document.createElement('link');
                prefetchLink.rel = 'prefetch';
                prefetchLink.href = href;
                prefetchLink.as = 'document';
                document.head.appendChild(prefetchLink);
            });

            // Smooth page exit on click (skip if already active)
            link.addEventListener('click', function (e) {
                if (link.classList.contains('active')) {
                    e.preventDefault();
                    return;
                }

                var href = link.getAttribute('href');
                if (!href) return;

                e.preventDefault();

                // Add exit animation class to main content
                var wrapper = document.querySelector('.monkeypay-admin-wrap');
                if (wrapper) {
                    wrapper.classList.add('mp-page-exit');
                }

                // Navigate after short animation
                setTimeout(function () {
                    window.location.href = href;
                }, 150);
            });
        });
    }

    // Init when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavPrefetch);
    } else {
        initNavPrefetch();
    }

})();

