<?php
/**
 * MonkeyPay REST API — Webhook Connections
 *
 * Handles CRUD operations and testing for webhook connections
 * (Lark, Slack, Telegram, etc.).
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_REST_Connections {

    /**
     * Register REST routes for webhook connections.
     */
    public static function register_routes() {
        // List all connections
        register_rest_route( 'monkeypay/v1', '/connections', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'list_connections' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Get single connection
        register_rest_route( 'monkeypay/v1', '/connections/(?P<id>[a-zA-Z0-9_-]+)', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_connection' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Create connection
        register_rest_route( 'monkeypay/v1', '/connections', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'create_connection' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Update connection
        register_rest_route( 'monkeypay/v1', '/connections/(?P<id>[a-zA-Z0-9_-]+)', [
            'methods'             => 'PUT',
            'callback'            => [ __CLASS__, 'update_connection' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Delete connection
        register_rest_route( 'monkeypay/v1', '/connections/(?P<id>[a-zA-Z0-9_-]+)', [
            'methods'             => 'DELETE',
            'callback'            => [ __CLASS__, 'delete_connection' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Test connection
        register_rest_route( 'monkeypay/v1', '/connections/(?P<id>[a-zA-Z0-9_-]+)/test', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'test_connection' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    /**
     * List all webhook connections.
     */
    public static function list_connections() {
        $mgr = MonkeyPay_Connections::get_instance();

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $mgr->get_connections(),
        ], 200 );
    }

    /**
     * Get a single connection.
     */
    public static function get_connection( $request ) {
        $mgr  = MonkeyPay_Connections::get_instance();
        $conn = $mgr->get_connection( $request->get_param( 'id' ) );

        if ( ! $conn ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Connection not found',
            ], 404 );
        }

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $conn,
        ], 200 );
    }

    /**
     * Create a new webhook connection.
     */
    public static function create_connection( $request ) {
        $mgr  = MonkeyPay_Connections::get_instance();
        $data = $request->get_json_params();

        if ( empty( $data['webhook_url'] ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Webhook URL is required',
            ], 400 );
        }

        $conn = $mgr->add_connection( $data );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $conn,
        ], 201 );
    }

    /**
     * Update an existing webhook connection.
     */
    public static function update_connection( $request ) {
        $mgr  = MonkeyPay_Connections::get_instance();
        $id   = $request->get_param( 'id' );
        $data = $request->get_json_params();
        $conn = $mgr->update_connection( $id, $data );

        if ( ! $conn ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Connection not found',
            ], 404 );
        }

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $conn,
        ], 200 );
    }

    /**
     * Delete a webhook connection.
     */
    public static function delete_connection( $request ) {
        $mgr     = MonkeyPay_Connections::get_instance();
        $deleted = $mgr->delete_connection( $request->get_param( 'id' ) );

        if ( ! $deleted ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Connection not found',
            ], 404 );
        }

        return new WP_REST_Response( [
            'success' => true,
        ], 200 );
    }

    /**
     * Test a webhook connection by sending sample payload.
     */
    public static function test_connection( $request ) {
        $mgr    = MonkeyPay_Connections::get_instance();
        $result = $mgr->send_test( $request->get_param( 'id' ) );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 500 );
        }

        $ok = isset( $result['status'] ) && $result['status'] === 'ok';

        return new WP_REST_Response( [
            'success' => $ok,
            'data'    => $result,
        ], $ok ? 200 : 502 );
    }
}
