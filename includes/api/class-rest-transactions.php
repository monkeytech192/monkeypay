<?php
/**
 * MonkeyPay REST API — Transactions
 *
 * Handles payment transaction creation and status checking.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_REST_Transactions {

    /**
     * Register REST routes for transactions.
     */
    public static function register_routes() {
        // Create transaction (admin/internal use)
        register_rest_route( 'monkeypay/v1', '/transactions', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'create_transaction' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' ) || wp_verify_nonce( $_SERVER['HTTP_X_WP_NONCE'] ?? '', 'wp_rest' );
            },
            'args' => [
                'amount' => [
                    'required'          => true,
                    'type'              => 'number',
                    'sanitize_callback' => 'floatval',
                ],
                'description' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ],
                'mode' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => 'auto',
                    'enum'              => ['auto', 'manual'],
                ],
            ],
        ] );

        // Check transaction status (public — nonce verified)
        register_rest_route( 'monkeypay/v1', '/transactions/(?P<tx_id>[a-zA-Z0-9_]+)', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'check_transaction' ],
            'permission_callback' => '__return_true',
            'args' => [
                'tx_id' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );
    }

    /**
     * Create a payment transaction.
     */
    public static function create_transaction( $request ) {
        $api    = new MonkeyPay_API_Client();
        $result = $api->create_transaction(
            $request->get_param( 'amount' ),
            $request->get_param( 'description' ),
            $request->get_param( 'mode' )
        );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 500 );
        }

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $result,
        ], 201 );
    }

    /**
     * Check transaction status.
     */
    public static function check_transaction( $request ) {
        $api    = new MonkeyPay_API_Client();
        $result = $api->check_transaction( $request->get_param( 'tx_id' ) );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 404 );
        }

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $result,
        ], 200 );
    }
}
