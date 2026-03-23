<?php
/**
 * MonkeyPay REST API — Payment Gateways
 *
 * Admin: Proxies CRUD requests to MonkeyPay Server, then syncs local cache.
 * Merchant: Reads from local cache (auto-synced) for fast response.
 *
 * @package MonkeyPay
 * @since   3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_REST_Gateways {

    /**
     * Register REST routes for gateways.
     */
    public static function register_routes() {
        // List gateways (admin — proxied to server + local cache)
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

        // Manual sync trigger (admin)
        register_rest_route( 'monkeypay/v1', '/gateways/sync', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'manual_sync' ],
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
        $url = MonkeyPay_Settings::get( 'api_url', MONKEYPAY_API_URL );
        return rtrim( ! empty( $url ) ? $url : MONKEYPAY_API_URL, '/' );
    }

    /**
     * Admin: List gateways from local cache.
     * Forces sync if cache is stale or if ?force_sync=1 is passed.
     */
    public static function list_gateways( $request ) {
        $force = $request->get_param( 'force_sync' ) === '1';

        // Sync from server if needed
        if ( $force || MonkeyPay_Sync::is_cache_stale() ) {
            MonkeyPay_Sync::sync_gateways();
        }

        // Read from local cache
        global $wpdb;
        $table = MonkeyPay_DB::gateways_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $gateways = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A );

        return new WP_REST_Response( [
            'success'  => true,
            'data'     => [
                'gateways' => is_array( $gateways ) ? $gateways : [],
            ],
        ], 200 );
    }

    /**
     * Admin proxy: Save a gateway to server, then sync local cache.
     */
    public static function save_gateway( $request ) {
        $params            = $request->get_json_params();
        $params['api_key'] = MonkeyPay_Settings::get( 'api_key' );
        $admin_secret      = MonkeyPay_Settings::get( 'admin_secret' );

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

        // On success: sync local cache + checkin options
        if ( $code < 400 ) {
            // Get server-assigned ID from response
            $server_id = isset( $data['gateway']['id'] )
                ? $data['gateway']['id']
                : ( isset( $data['id'] ) ? $data['id'] : null );

            if ( $server_id ) {
                MonkeyPay_Sync::cache_gateway( $server_id, $params );
            }

            // Sync gateway config to WP options for Checkin plugin integration
            self::sync_gateway_options( $params );
        }

        return new WP_REST_Response( [
            'success' => $code < 400,
            'data'    => $data,
        ], $code );
    }

    /**
     * Sync gateway config to WP options for Checkin plugin integration.
     *
     * @param array $params Gateway params from the save request.
     */
    private static function sync_gateway_options( $params ) {
        if ( ! empty( $params['note_prefix'] ) ) {
            update_option( 'checkin_monkeypay_note_prefix', sanitize_text_field( $params['note_prefix'] ) );
        }
        if ( ! empty( $params['note_syntax'] ) ) {
            update_option( 'checkin_monkeypay_note_syntax', sanitize_text_field( $params['note_syntax'] ) );
        }
        if ( isset( $params['polling_interval'] ) ) {
            update_option( 'checkin_monkeypay_polling_interval', absint( $params['polling_interval'] ) );
        }
        if ( ! empty( $params['account_number'] ) ) {
            update_option( 'checkin_bank_account_number', sanitize_text_field( $params['account_number'] ) );
        }
        if ( ! empty( $params['account_name'] ) ) {
            update_option( 'checkin_bank_account_name', sanitize_text_field( $params['account_name'] ) );
        }
        if ( isset( $params['auto_amount'] ) ) {
            update_option( 'checkin_bank_auto_amount', absint( $params['auto_amount'] ) );
        }
    }

    /**
     * Admin proxy: Delete a gateway from server, then remove from local cache.
     */
    public static function delete_gateway( $request ) {
        $id           = $request->get_param( 'id' );
        $admin_secret = MonkeyPay_Settings::get( 'admin_secret' );

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
        $code = wp_remote_retrieve_response_code( $response );

        // On success: remove from local cache
        if ( $code < 400 ) {
            MonkeyPay_Sync::remove_cached_gateway( intval( $id ) );
        }

        return new WP_REST_Response( [
            'success' => $code < 400,
            'data'    => $data,
        ], $code );
    }

    /**
     * Manual sync trigger (admin button).
     */
    public static function manual_sync() {
        $result = MonkeyPay_Sync::sync_all( true );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $result,
        ], 200 );
    }

    /**
     * Merchant-facing: list enabled gateways from LOCAL CACHE.
     * Uses local API key validation — no server roundtrip needed.
     */
    public static function merchant_gateways( $request ) {
        // Accept key from header or query param
        $api_key = $request->get_header( 'X-Api-Key' );
        if ( empty( $api_key ) ) {
            $api_key = $request->get_param( 'api_key' );
        }

        if ( empty( $api_key ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'API key is required. Pass via X-Api-Key header or api_key query param.',
            ], 401 );
        }

        // Validate local API key
        $key_record = MonkeyPay_REST_API_Keys::validate_api_key( $api_key );

        if ( ! $key_record ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Invalid or revoked API key.',
            ], 401 );
        }

        // Read from local cache (auto-syncs if stale)
        $gateways = MonkeyPay_Sync::get_cached_gateways();

        return new WP_REST_Response( [
            'success' => true,
            'data'    => [
                'gateways' => $gateways,
            ],
        ], 200 );
    }
}
