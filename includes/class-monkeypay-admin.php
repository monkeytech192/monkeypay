<?php
/**
 * MonkeyPay Admin
 *
 * Registers menu, submenu pages, and renders page routing.
 * Includes onboarding gate: shows register/login if no API key.
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_notices', [ $this, 'admin_notices' ] );
    }

    /**
     * Check if onboarding is needed (no API key configured).
     */
    private function needs_onboarding() {
        return empty( get_option( 'monkeypay_api_key', '' ) );
    }

    /**
     * Register admin menu and submenu pages.
     */
    public function register_menu() {
        // Main menu
        add_menu_page(
            __( 'MonkeyPay', 'monkeypay' ),
            __( 'MonkeyPay', 'monkeypay' ),
            'manage_options',
            'monkeypay',
            [ $this, 'render_dashboard_page' ],
            'dashicons-money-alt',
            56
        );

        // Dashboard submenu (first item = duplicate parent slug)
        add_submenu_page(
            'monkeypay',
            __( 'Dashboard', 'monkeypay' ),
            __( 'Dashboard', 'monkeypay' ),
            'manage_options',
            'monkeypay',
            [ $this, 'render_dashboard_page' ]
        );

        // Integrations
        add_submenu_page(
            'monkeypay',
            __( 'Tích Hợp', 'monkeypay' ),
            __( 'Tích Hợp', 'monkeypay' ),
            'manage_options',
            'monkeypay-integrations',
            [ $this, 'render_integrations_page' ]
        );

        // Connections
        add_submenu_page(
            'monkeypay',
            __( 'Kết Nối', 'monkeypay' ),
            __( 'Kết Nối', 'monkeypay' ),
            'manage_options',
            'monkeypay-connections',
            [ $this, 'render_connections_page' ]
        );

        // API Keys
        add_submenu_page(
            'monkeypay',
            __( 'API Keys', 'monkeypay' ),
            __( 'API Keys', 'monkeypay' ),
            'manage_options',
            'monkeypay-api-keys',
            [ $this, 'render_api_keys_page' ]
        );

        // API Docs
        add_submenu_page(
            'monkeypay',
            __( 'Tài Liệu API', 'monkeypay' ),
            __( 'Tài Liệu API', 'monkeypay' ),
            'manage_options',
            'monkeypay-api-docs',
            [ $this, 'render_api_docs_page' ]
        );

        // Payment Gateways
        add_submenu_page(
            'monkeypay',
            __( 'Cổng Thanh Toán', 'monkeypay' ),
            __( 'Cổng Thanh Toán', 'monkeypay' ),
            'manage_options',
            'monkeypay-gateways',
            [ $this, 'render_gateways_page' ]
        );

        // Account
        add_submenu_page(
            'monkeypay',
            __( 'Tài Khoản', 'monkeypay' ),
            __( 'Tài Khoản', 'monkeypay' ),
            'manage_options',
            'monkeypay-account',
            [ $this, 'render_account_page' ]
        );

        // Settings
        add_submenu_page(
            'monkeypay',
            __( 'Cài Đặt', 'monkeypay' ),
            __( 'Cài Đặt', 'monkeypay' ),
            'manage_options',
            'monkeypay-settings',
            [ $this, 'render_settings_page' ]
        );

        // Pricing — last item in sidebar
        add_submenu_page(
            'monkeypay',
            __( 'Bảng Giá', 'monkeypay' ),
            __( 'Bảng Giá', 'monkeypay' ),
            'manage_options',
            'monkeypay-pricing',
            [ $this, 'render_pricing_page' ]
        );
    }

    /* ─── Renderers (with onboarding gate) ───────────── */

    public function render_dashboard_page() {
        if ( $this->needs_onboarding() ) {
            include MONKEYPAY_PLUGIN_DIR . 'templates/admin-onboarding.php';
            return;
        }
        include MONKEYPAY_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    public function render_settings_page() {
        if ( $this->needs_onboarding() ) {
            include MONKEYPAY_PLUGIN_DIR . 'templates/admin-onboarding.php';
            return;
        }
        include MONKEYPAY_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    public function render_connections_page() {
        if ( $this->needs_onboarding() ) {
            include MONKEYPAY_PLUGIN_DIR . 'templates/admin-onboarding.php';
            return;
        }
        include MONKEYPAY_PLUGIN_DIR . 'templates/admin-connections.php';
    }

    public function render_integrations_page() {
        if ( $this->needs_onboarding() ) {
            include MONKEYPAY_PLUGIN_DIR . 'templates/admin-onboarding.php';
            return;
        }
        include MONKEYPAY_PLUGIN_DIR . 'templates/admin-integrations.php';
    }

    public function render_gateways_page() {
        if ( $this->needs_onboarding() ) {
            include MONKEYPAY_PLUGIN_DIR . 'templates/admin-onboarding.php';
            return;
        }
        include MONKEYPAY_PLUGIN_DIR . 'templates/admin-gateways.php';
    }

    public function render_account_page() {
        if ( $this->needs_onboarding() ) {
            include MONKEYPAY_PLUGIN_DIR . 'templates/admin-onboarding.php';
            return;
        }
        include MONKEYPAY_PLUGIN_DIR . 'templates/admin-account.php';
    }

    public function render_api_keys_page() {
        if ( $this->needs_onboarding() ) {
            include MONKEYPAY_PLUGIN_DIR . 'templates/admin-onboarding.php';
            return;
        }
        include MONKEYPAY_PLUGIN_DIR . 'templates/admin-api-keys.php';
    }

    public function render_api_docs_page() {
        if ( $this->needs_onboarding() ) {
            include MONKEYPAY_PLUGIN_DIR . 'templates/admin-onboarding.php';
            return;
        }
        include MONKEYPAY_PLUGIN_DIR . 'templates/admin-api-docs.php';
    }

    public function render_pricing_page() {
        if ( $this->needs_onboarding() ) {
            include MONKEYPAY_PLUGIN_DIR . 'templates/admin-onboarding.php';
            return;
        }
        include MONKEYPAY_PLUGIN_DIR . 'templates/admin-pricing.php';
    }

    /* ─── Notices ─────────────────────────────────────── */

    public function admin_notices() {
        $notice = get_transient( 'monkeypay_session_expired' );
        if ( $notice ) {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>MonkeyPay:</strong> ' . esc_html( $notice ) . '</p></div>';
            delete_transient( 'monkeypay_session_expired' );
        }
    }
}
