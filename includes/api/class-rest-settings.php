<?php
/**
 * MonkeyPay REST API — Settings & Health
 *
 * Handles health check proxy and plugin settings management.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_REST_Settings {

    /**
     * Register REST routes for settings.
     */
    public static function register_routes() {
        // Health check
        register_rest_route( 'monkeypay/v1', '/health', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'health_check' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Save settings (admin only)
        register_rest_route( 'monkeypay/v1', '/settings', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'save_settings' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    /**
     * Health check — proxy to MonkeyPay Server.
     */
    public static function health_check() {
        $api    = new MonkeyPay_API_Client();
        $result = $api->health_check();

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 503 );
        }

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $result,
        ], 200 );
    }

    /**
     * Save plugin settings.
     */
    public static function save_settings( $request ) {
        $params = $request->get_json_params();

        // Map full option keys → short keys for MonkeyPay_Settings
        $allowed = [
            'monkeypay_api_url'        => 'api_url',
            'monkeypay_api_key'        => 'api_key',
            'monkeypay_webhook_secret' => 'webhook_secret',
            'monkeypay_admin_secret'   => 'admin_secret',
            'monkeypay_enabled'        => 'enabled',
            'monkeypay_wc_enabled'     => 'wc_enabled',
            'monkeypay_checkin_bridge'  => 'checkin_bridge',
            'monkeypay_language'       => 'language',
            'monkeypay_timezone'       => 'timezone',
            'monkeypay_dark_mode'      => 'dark_mode',
        ];

        foreach ( $allowed as $full_key => $short_key ) {
            if ( isset( $params[ $full_key ] ) ) {
                MonkeyPay_Settings::set( $short_key, $params[ $full_key ] );
            }
        }

        return new WP_REST_Response( [
            'success' => true,
            'message' => __( 'Cài đặt đã được lưu.', 'monkeypay' ),
        ], 200 );
    }
}
