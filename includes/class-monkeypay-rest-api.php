<?php
/**
 * MonkeyPay REST API — Router
 *
 * Thin router that delegates route registration to specialized API modules.
 * Each module handles its own route definitions and callbacks.
 *
 * @package MonkeyPay
 * @since   3.0.0
 * @see     includes/api/ for individual API modules
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load API modules
require_once MONKEYPAY_PLUGIN_DIR . 'includes/api/class-rest-settings.php';
require_once MONKEYPAY_PLUGIN_DIR . 'includes/api/class-rest-transactions.php';
require_once MONKEYPAY_PLUGIN_DIR . 'includes/api/class-rest-gateways.php';
require_once MONKEYPAY_PLUGIN_DIR . 'includes/api/class-rest-auth.php';
require_once MONKEYPAY_PLUGIN_DIR . 'includes/api/class-rest-bank.php';
require_once MONKEYPAY_PLUGIN_DIR . 'includes/api/class-rest-connections.php';
require_once MONKEYPAY_PLUGIN_DIR . 'includes/api/class-rest-api-keys.php';

class MonkeyPay_REST_API {

    /**
     * Register all REST API routes by delegating to specialized modules.
     *
     * Called via: add_action( 'rest_api_init', [ 'MonkeyPay_REST_API', 'register_routes' ] );
     */
    public static function register_routes() {
        MonkeyPay_REST_Settings::register_routes();
        MonkeyPay_REST_Transactions::register_routes();
        MonkeyPay_REST_Gateways::register_routes();
        MonkeyPay_REST_Auth::register_routes();
        MonkeyPay_REST_Bank::register_routes();
        MonkeyPay_REST_Connections::register_routes();
        MonkeyPay_REST_API_Keys::register_routes();
    }
}
