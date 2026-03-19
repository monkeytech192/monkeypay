<?php
/**
 * Main MonkeyPay Plugin Class
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay {

    /** @var MonkeyPay|null Singleton instance */
    private static $instance = null;

    /** @var MonkeyPay_API_Client */
    public $api;

    /** @var MonkeyPay_Webhook */
    public $webhook;

    /** @var MonkeyPay_Admin */
    public $admin;

    /**
     * Get singleton instance.
     *
     * @return MonkeyPay
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load plugin dependencies.
     */
    private function load_dependencies() {
        $dir = MONKEYPAY_PLUGIN_DIR . 'includes/';

        require_once $dir . 'class-monkeypay-api-client.php';
        require_once $dir . 'class-monkeypay-webhook.php';
        require_once $dir . 'class-monkeypay-rest-api.php';
        require_once $dir . 'class-monkeypay-shortcodes.php';
        require_once $dir . 'connections/class-connections-manager.php';
        require_once $dir . 'connections/class-lark-formatter.php';
        require_once $dir . 'class-monkeypay-admin.php';

        // Integrations
        $this->load_integrations();

        // Instantiate core
        $this->api     = new MonkeyPay_API_Client();
        $this->webhook = new MonkeyPay_Webhook();

        // Admin — must be created early so admin_menu hook fires
        if ( is_admin() ) {
            $this->admin = new MonkeyPay_Admin();
        }
    }

    /**
     * Load integration modules based on active plugins.
     */
    private function load_integrations() {
        $dir = MONKEYPAY_PLUGIN_DIR . 'includes/integrations/';

        // WooCommerce integration
        if ( class_exists( 'WooCommerce' ) || in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
            require_once $dir . 'class-woocommerce.php';
        }

        // checkin-mkt192-wp bridge
        if ( class_exists( 'Checkin_MKT192_WP' ) || in_array( 'checkin-mkt192-wp/checkin-mkt192-wp.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
            require_once $dir . 'class-checkin-bridge.php';
        }
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Plugin activation/deactivation
        register_activation_hook( MONKEYPAY_PLUGIN_FILE, [ $this, 'activate' ] );
        register_deactivation_hook( MONKEYPAY_PLUGIN_FILE, [ $this, 'deactivate' ] );

        // Enqueue assets
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'public_assets' ] );

        // REST API
        add_action( 'rest_api_init', [ 'MonkeyPay_REST_API', 'register_routes' ] );

        // Shortcodes
        add_action( 'init', [ 'MonkeyPay_Shortcodes', 'register' ] );

        // Settings link on plugins page
        add_filter( 'plugin_action_links_' . MONKEYPAY_PLUGIN_BASENAME, [ $this, 'settings_link' ] );

        // WooCommerce gateway registration
        add_filter( 'woocommerce_payment_gateways', [ $this, 'register_wc_gateway' ] );

        // Self-hosted update checker
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_plugin_update' ] );
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Set default options
        $defaults = [
            'monkeypay_api_url'       => MONKEYPAY_API_URL,
            'monkeypay_api_key'       => '',
            'monkeypay_webhook_secret' => '',
            'monkeypay_enabled'       => '0',
            'monkeypay_wc_enabled'    => '0',
            'monkeypay_checkin_bridge' => '0',
        ];

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }

        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Enqueue admin assets.
     *
     * Loads the dispatcher bootstrap (admin.js), shared utilities (utils.js),
     * and conditionally loads page-specific modules from assets/js/admin/.
     *
     * @since 3.0.0
     */
    public function admin_assets( $hook ) {
        if ( strpos( $hook, 'monkeypay' ) === false ) {
            return;
        }

        $base_url = MONKEYPAY_PLUGIN_URL . 'assets/';
        $version  = MONKEYPAY_VERSION;

        // ── CSS ──
        wp_enqueue_style(
            'monkeypay-admin',
            $base_url . 'css/admin.css',
            [],
            $version
        );

        // ── JS — Bootstrap dispatcher ──
        wp_enqueue_script(
            'monkeypay-admin',
            $base_url . 'js/admin.js',
            [ 'jquery' ],
            $version,
            true
        );

        wp_localize_script( 'monkeypay-admin', 'monkeypayAdmin', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'restUrl'  => rest_url( 'monkeypay/v1/' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'i18n'     => [
                'connecting'   => __( 'Đang kết nối...', 'monkeypay' ),
                'connected'    => __( 'Đã kết nối', 'monkeypay' ),
                'disconnected' => __( 'Mất kết nối', 'monkeypay' ),
                'saved'        => __( 'Đã lưu cài đặt', 'monkeypay' ),
                'error'        => __( 'Có lỗi xảy ra', 'monkeypay' ),
            ],
        ] );

        // ── JS — Shared utilities (always loaded) ──
        wp_enqueue_script(
            'monkeypay-utils',
            $base_url . 'js/admin/utils.js',
            [ 'jquery', 'monkeypay-admin' ],
            $version,
            true
        );

        // ── JS — Page-specific modules ──
        $page_modules = $this->get_page_modules( $hook );
        foreach ( $page_modules as $handle => $file ) {
            wp_enqueue_script(
                'monkeypay-' . $handle,
                $base_url . 'js/admin/' . $file,
                [ 'jquery', 'monkeypay-admin', 'monkeypay-utils' ],
                $version,
                true
            );
        }
    }

    /**
     * Determine which JS modules to load based on the current admin page.
     *
     * @param  string $hook  WordPress admin hook suffix.
     * @return array  Associative array of handle => filename.
     */
    private function get_page_modules( $hook ) {
        $modules = [];

        // Detect page slug from the hook (e.g. "toplevel_page_monkeypay" or "monkeypay_page_monkeypay-settings")
        $page = '';
        if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        }

        // Map page slugs to module files
        $slug_map = [
            'monkeypay'              => [ 'dashboard' => 'dashboard.js' ],
            'monkeypay-settings'     => [ 'settings'  => 'settings.js' ],
            'monkeypay-gateways'     => [ 'gateways'  => 'gateways.js' ],
            'monkeypay-onboarding'   => [ 'onboarding' => 'onboarding.js' ],
            'monkeypay-account'      => [ 'account'   => 'account.js' ],
            'monkeypay-pricing'      => [ 'pricing'   => 'pricing.js' ],
            'monkeypay-connections'  => [
                'connections'  => 'connections.js',
                'card-builder' => 'card-builder.js',
            ],
        ];

        if ( isset( $slug_map[ $page ] ) ) {
            $modules = $slug_map[ $page ];
        }

        return $modules;
    }

    /**
     * Enqueue public assets (only on payment pages).
     */
    public function public_assets() {
        if ( ! $this->is_payment_page() ) {
            return;
        }

        wp_enqueue_style(
            'monkeypay-payment',
            MONKEYPAY_PLUGIN_URL . 'assets/css/payment.css',
            [],
            MONKEYPAY_VERSION
        );

        wp_enqueue_script(
            'monkeypay-payment',
            MONKEYPAY_PLUGIN_URL . 'assets/js/payment.js',
            [ 'jquery' ],
            MONKEYPAY_VERSION,
            true
        );

        wp_localize_script( 'monkeypay-payment', 'monkeypayPayment', [
            'restUrl' => rest_url( 'monkeypay/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        ] );
    }

    /**
     * Check if current page contains payment shortcode.
     */
    private function is_payment_page() {
        global $post;
        return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'monkeypay_payment' );
    }

    /**
     * Settings link on plugins page.
     */
    public function settings_link( $links ) {
        $url  = admin_url( 'admin.php?page=monkeypay' );
        $link = '<a href="' . esc_url( $url ) . '">' . __( 'Cài đặt', 'monkeypay' ) . '</a>';
        array_unshift( $links, $link );
        return $links;
    }

    /**
     * Register WooCommerce payment gateway.
     */
    public function register_wc_gateway( $gateways ) {
        if ( class_exists( 'MonkeyPay_WooCommerce_Gateway' ) && get_option( 'monkeypay_wc_enabled', '0' ) === '1' ) {
            $gateways[] = 'MonkeyPay_WooCommerce_Gateway';
        }
        return $gateways;
    }

    /**
     * Helper: Check if plugin is configured and enabled.
     */
    public function is_active() {
        return get_option( 'monkeypay_enabled', '0' ) === '1'
            && ! empty( get_option( 'monkeypay_api_key', '' ) );
    }

    /**
     * Check for plugin updates from remote server.
     *
     * @param object $transient The update_plugins transient object.
     * @return object
     */
    public function check_plugin_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote = wp_remote_get(
            MONKEYPAY_UPDATE_API,
            [
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) ) ) {
            return $transient;
        }

        $remote_data = json_decode( wp_remote_retrieve_body( $remote ) );

        if ( ! $remote_data || ! isset( $remote_data->version ) || ! isset( $remote_data->package ) ) {
            return $transient;
        }

        $plugin_slug = MONKEYPAY_PLUGIN_BASENAME;
        if ( version_compare( MONKEYPAY_VERSION, $remote_data->version, '<' ) ) {
            $update = [
                'slug'         => 'monkeypay',
                'plugin'       => $plugin_slug,
                'new_version'  => $remote_data->version,
                'url'          => $remote_data->homepage ?? 'https://monkeytech192.vn/',
                'package'      => $remote_data->package,
                'tested'       => $remote_data->tested       ?? '',
                'requires'     => $remote_data->requires     ?? '',
                'requires_php' => $remote_data->requires_php ?? '',
            ];
            $transient->response[ $plugin_slug ] = (object) $update;
        }

        return $transient;
    }
}
