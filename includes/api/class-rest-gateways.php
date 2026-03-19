<?php
/**
 * MonkeyPay REST API — Payment Gateways
 *
 * Proxies gateway management requests to the MonkeyPay Server.
 * Handles admin gateway CRUD and merchant-facing gateway listing.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_REST_Gateways {

    /**
     * Register REST routes for gateways.
     */
    public static function register_routes() {
        // List gateways (admin — proxied to server)
        register_rest_route( 'monkeypay/v1', '/gateways', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'list_gateways' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Save gateway (admin — proxied to server)
        register_rest_route( 'monkeypay/v1', '/gateways', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'save_gateway' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Delete gateway (admin — proxied to server)
        register_rest_route( 'monkeypay/v1', '/gateways/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ __CLASS__, 'delete_gateway' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Merchant-facing: list enabled gateways (using API key)
        register_rest_route( 'monkeypay/v1', '/merchant-gateways', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'merchant_gateways' ],
            'permission_callback' => '__return_true',
            'args' => [
                'api_key' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );
    }

    /**
     * Helper: Get resolved API URL.
     *
     * @return string
     */
    private static function get_api_url() {
        $url = get_option( 'monkeypay_api_url', MONKEYPAY_API_URL );
        return rtrim( ! empty( $url ) ? $url : MONKEYPAY_API_URL, '/' );
    }

    /**
     * Admin proxy: list gateways for the current merchant.
     */
    public static function list_gateways() {
        $api_key = get_option( 'monkeypay_api_key', '' );

        $response = wp_remote_get( self::get_api_url() . '/api/gateways', [
            'timeout' => 15,
            'headers' => [
                'X-Api-Key' => $api_key,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $response->get_error_message(),
            ], 500 );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $data,
        ], 200 );
    }

    /**
     * Admin proxy: save a gateway.
     * Forwards bank account info to MonkeyPay server.
     */
    public static function save_gateway( $request ) {
        $params            = $request->get_json_params();
        $params['api_key'] = get_option( 'monkeypay_api_key', '' );
        $admin_secret      = get_option( 'monkeypay_admin_secret', '' );

        $response = wp_remote_post( self::get_api_url() . '/api/admin/gateways', [
            'timeout' => 15,
            'headers' => [
                'Content-Type'   => 'application/json',
                'X-Admin-Secret' => $admin_secret,
            ],
            'body' => wp_json_encode( $params ),
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $response->get_error_message(),
            ], 500 );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        return new WP_REST_Response( [
            'success' => $code < 400,
            'data'    => $data,
        ], $code );
    }

    /**
     * Admin proxy: delete a gateway.
     */
    public static function delete_gateway( $request ) {
        $id           = $request->get_param( 'id' );
        $admin_secret = get_option( 'monkeypay_admin_secret', '' );

        $response = wp_remote_request( self::get_api_url() . '/api/admin/gateways/' . intval( $id ), [
            'method'  => 'DELETE',
            'timeout' => 15,
            'headers' => [
                'X-Admin-Secret' => $admin_secret,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $response->get_error_message(),
            ], 500 );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $data,
        ], 200 );
    }

    /**
     * Merchant-facing: list enabled gateways using API key.
     * Called by Checkin plugin to fetch available gateways.
     */
    public static function merchant_gateways( $request ) {
        $api_key = $request->get_param( 'api_key' );

        $response = wp_remote_get( self::get_api_url() . '/api/gateways', [
            'timeout' => 15,
            'headers' => [
                'X-Api-Key' => $api_key,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $response->get_error_message(),
            ], 500 );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => isset( $data['error'] ) ? $data['error'] : 'Invalid API key',
            ], $code );
        }

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $data,
        ], 200 );
    }
}
