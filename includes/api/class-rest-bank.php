<?php
/**
 * MonkeyPay REST API — Bank Data
 *
 * Handles bank account summary and transaction history
 * proxied from the MonkeyPay Cloud Run server.
 *
 * BDSD notifications (MacroDroid) are sent directly to the server.
 * The server then sends a `bank_transaction` webhook to the plugin's
 * unified handler in class-monkeypay-webhook.php.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_REST_Bank {

    /**
     * Register REST routes for bank data.
     */
    public static function register_routes() {
        // Bank summary (admin)
        register_rest_route( 'monkeypay/v1', '/bank/summary', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'bank_summary' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args' => [
                'from' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'to' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        // Bank history (admin)
        register_rest_route( 'monkeypay/v1', '/bank/history', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'bank_history' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args' => [
                'from' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'to' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );
    }

    /**
     * Bank summary — proxy to MonkeyPay server.
     * Returns: balance, total_in, total_out, counts.
     */
    public static function bank_summary( $request ) {
        $api = new MonkeyPay_API_Client();

        $endpoint = '/api/bank/summary';
        $from     = $request->get_param( 'from' );
        $to       = $request->get_param( 'to' );
        $params   = [];

        if ( $from ) $params[] = 'from=' . urlencode( $from );
        if ( $to )   $params[] = 'to='   . urlencode( $to );

        if ( ! empty( $params ) ) {
            $endpoint .= '?' . implode( '&', $params );
        }

        $result = $api->request( $endpoint );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 500 );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Bank history — proxy to MonkeyPay server.
     * Returns: transaction list for the merchant's account.
     */
    public static function bank_history( $request ) {
        $api = new MonkeyPay_API_Client();

        $endpoint = '/api/bank/history';
        $from     = $request->get_param( 'from' );
        $to       = $request->get_param( 'to' );
        $params   = [];

        if ( $from ) $params[] = 'from=' . urlencode( $from );
        if ( $to )   $params[] = 'to='   . urlencode( $to );

        if ( ! empty( $params ) ) {
            $endpoint .= '?' . implode( '&', $params );
        }

        $result = $api->request( $endpoint );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 500 );
        }

        return new WP_REST_Response( $result, 200 );
    }
}
