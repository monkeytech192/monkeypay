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

        // Logger MUST load first
        require_once $dir . 'class-monkeypay-logger.php';

        // Database manager (before webhook — needed for insert)
        require_once $dir . 'class-monkeypay-settings.php';
        require_once $dir . 'class-monkeypay-db.php';
        require_once $dir . 'class-monkeypay-sync.php';

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

        // First-time webhook URL sync for existing merchants
        add_action( 'admin_init', [ $this, 'maybe_sync_webhook_url' ] );

        // Ensure DB tables are up to date (handles migrations)
        add_action( 'admin_init', [ 'MonkeyPay_DB', 'create_tables' ] );

        // Lazy sync gateways + merchant profile on MonkeyPay admin pages
        add_action( 'admin_init', [ $this, 'maybe_lazy_sync' ] );
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // 1. Create/upgrade custom database tables FIRST
        MonkeyPay_DB::create_tables();

        // 2. Set default settings (goes into monkeypay_settings table)
        $defaults = [
            'api_url'        => MONKEYPAY_API_URL,
            'api_key'        => '',
            'webhook_secret' => '',
            'enabled'        => '0',
            'wc_enabled'     => '0',
            'checkin_bridge'  => '0',
        ];

        foreach ( $defaults as $key => $value ) {
            if ( MonkeyPay_Settings::get( $key ) === null ) {
                MonkeyPay_Settings::set( $key, $value );
            }
        }

        // 3. Sync gateways + merchant profile from server on activation
        MonkeyPay_Sync::sync_all( true );

        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        // Clear sync cache so next activation triggers fresh sync
        delete_transient( MonkeyPay_Sync::LAST_SYNC_KEY );
        flush_rewrite_rules();
    }

    /**
     * Sync webhook URL to server once for merchants created before this feature.
     *
     * Runs on admin_init. Checks flag 'monkeypay_webhook_synced'.
     * If not synced yet AND api_key exists, calls PUT /merchants/webhook-url.
     *
     * @since 3.1.0
     */
    public function maybe_sync_webhook_url() {
        // Already synced — skip
        if ( MonkeyPay_Settings::get( 'webhook_synced' ) === '1' ) {
            return;
        }

        $api_key = MonkeyPay_Settings::get( 'api_key' );
        if ( empty( $api_key ) ) {
            return; // Not registered yet
        }

        $api_url     = rtrim( MonkeyPay_Settings::get( 'api_url', MONKEYPAY_API_URL ), '/' );
        $webhook_url = rest_url( 'monkeypay/v1/webhook' );

        $response = wp_remote_request( $api_url . '/api/merchants/webhook-url', [
            'method'  => 'PUT',
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Key'    => $api_key,
            ],
            'body' => wp_json_encode( [ 'webhook_url' => $webhook_url ] ),
        ] );

        if ( is_wp_error( $response ) ) {
            MonkeyPay_Logger::api( 'Webhook sync failed: ' . $response->get_error_message() );
            return; // Will retry next admin load
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code >= 200 && $code < 300 ) {
            MonkeyPay_Settings::set( 'webhook_synced', '1' );
            MonkeyPay_Logger::api( "Webhook URL synced successfully: {$webhook_url}" );
        } else {
            $body = wp_remote_retrieve_body( $response );
            MonkeyPay_Logger::api( "Webhook sync failed (HTTP {$code}): {$body}" );
            // Don't set flag — will retry on next admin load
        }
    }

    /**
     * Lazy sync gateways + merchant profile from server.
     *
     * Runs on admin_init. Only triggers on MonkeyPay admin pages
     * and only if the cache is stale (older than 5 minutes).
     *
     * @since 3.3.0
     */
    public function maybe_lazy_sync() {
        // Only run on MonkeyPay admin pages
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $is_monkeypay_page = false;

        if ( $screen && isset( $screen->id ) && strpos( $screen->id, 'monkeypay' ) !== false ) {
            $is_monkeypay_page = true;
        }

        // Also check via query param for early admin_init
        if ( ! $is_monkeypay_page && isset( $_GET['page'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'monkeypay' ) !== false ) { // phpcs:ignore WordPress.Security.NonceVerification
            $is_monkeypay_page = true;
        }

        if ( ! $is_monkeypay_page ) {
            return;
        }

        // Sync only if cache is stale
        MonkeyPay_Sync::sync_all();
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
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'adminUrl'       => admin_url(),
            'restUrl'        => rest_url( 'monkeypay/v1/' ),
            'pluginUrl'      => MONKEYPAY_PLUGIN_URL,
            'apiUrl'         => rtrim( MonkeyPay_Settings::get( 'api_url', MONKEYPAY_API_URL ), '/' ),
            'nonce'          => wp_create_nonce( 'wp_rest' ),
            'authProvider'   => MonkeyPay_Settings::get( 'auth_provider' ),
            'language'       => MonkeyPay_Settings::get( 'language' ),
            'timezone'       => MonkeyPay_Settings::get( 'timezone' ),
            'darkMode'       => MonkeyPay_Settings::get( 'dark_mode' ),
            'i18n'           => [
                'connecting'   => __( 'Đang kết nối...', 'monkeypay' ),
                'connected'    => __( 'Đã kết nối', 'monkeypay' ),
                'disconnected' => __( 'Mất kết nối', 'monkeypay' ),
                'saved'        => __( 'Đã lưu cài đặt', 'monkeypay' ),
                'error'        => __( 'Có lỗi xảy ra', 'monkeypay' ),
            ],
        ] );

        // ── JS — i18n dictionary + dark mode bootstrap (always loaded) ──
        wp_enqueue_script(
            'monkeypay-i18n',
            $base_url . 'js/admin/i18n.js',
            [ 'monkeypay-admin' ],
            $version,
            true
        );

        // ── JS — Shared utilities (always loaded) ──
        wp_enqueue_script(
            'monkeypay-utils',
            $base_url . 'js/admin/utils.js',
            [ 'jquery', 'monkeypay-admin', 'monkeypay-i18n' ],
            $version,
            true
        );

        // ── Onboarding gate: load onboarding.js (no client-side Google SDK needed — uses server redirect) ──
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $needs_onboarding = empty( MonkeyPay_Settings::get( 'api_key' ) );

        if ( $needs_onboarding && strpos( $page, 'monkeypay' ) === 0 ) {
            // Onboarding module
            wp_enqueue_script(
                'monkeypay-onboarding',
                $base_url . 'js/admin/onboarding.js',
                [ 'jquery', 'monkeypay-admin', 'monkeypay-utils' ],
                $version,
                true
            );
        } else {
            // ── JS — Chart.js (dashboard only) ──
            if ( 'monkeypay' === $page ) {
                wp_enqueue_script(
                    'chartjs',
                    'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js',
                    [],
                    '4.4.7',
                    true
                );
            }

            // ── JS — Page-specific modules (only when NOT in onboarding) ──
            $page_modules = $this->get_page_modules( $hook );
            foreach ( $page_modules as $handle => $file ) {
                $deps = [ 'jquery', 'monkeypay-admin', 'monkeypay-i18n', 'monkeypay-utils' ];
                if ( 'dashboard' === $handle ) {
                    $deps[] = 'chartjs';
                }
                wp_enqueue_script(
                    'monkeypay-' . $handle,
                    $base_url . 'js/admin/' . $file,
                    $deps,
                    $version,
                    true
                );
            }
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
            'monkeypay'              => [ 'dashboard' => 'dashboard.js', 'settings' => 'settings.js' ],
            'monkeypay-settings'     => [ 'settings'  => 'settings.js' ],
            'monkeypay-gateways'     => [ 'gateways'  => 'gateways.js' ],
            'monkeypay-onboarding'   => [ 'onboarding' => 'onboarding.js' ],
            'monkeypay-account'      => [ 'account'   => 'account.js' ],
            'monkeypay-pricing'      => [ 'pricing'   => 'pricing.js' ],
            'monkeypay-connections'  => [
                'connections'  => 'connections.js',
                'card-builder' => 'card-builder.js',
            ],
            'monkeypay-api-keys'    => [ 'api-keys' => 'api-keys.js' ],
            'monkeypay-api-docs'    => [ 'api-docs' => 'api-docs.js' ],
            'monkeypay-transactions' => [ 'transactions' => 'transactions.js' ],
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
        if ( class_exists( 'MonkeyPay_WooCommerce_Gateway' ) && MonkeyPay_Settings::get( 'wc_enabled' ) === '1' ) {
            $gateways[] = 'MonkeyPay_WooCommerce_Gateway';
        }
        return $gateways;
    }

    /**
     * Helper: Check if plugin is configured and enabled.
     */
    public function is_active() {
        return MonkeyPay_Settings::get( 'enabled' ) === '1'
            && ! empty( MonkeyPay_Settings::get( 'api_key' ) );
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
