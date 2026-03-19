<?php
/**
 * MonkeyPay REST API — Bank & BDSD Webhook
 *
 * Handles bank account summary, transaction history,
 * and BDSD (MacroDroid) webhook forwarding.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_REST_Bank {

    /**
     * Register REST routes for bank & BDSD webhook.
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

        // BDSD webhook receiver (public — authenticated via X-Webhook-Secret)
        register_rest_route( 'monkeypay/v1', '/webhook/bdsd', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'bdsd_webhook' ],
            'permission_callback' => '__return_true',
        ] );

        // BDSD webhook health test (public)
        register_rest_route( 'monkeypay/v1', '/webhook/bdsd/test', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'bdsd_webhook_test' ],
            'permission_callback' => '__return_true',
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

    /**
     * BDSD Webhook — receives MB Bank notifications from MacroDroid.
     * Public endpoint, authenticated via X-Webhook-Secret header.
     * Forwards notification to the MonkeyPay server's /api/webhook/bdsd.
     *
     * POST /wp-json/monkeypay/v1/webhook/bdsd
     *   Header: X-Webhook-Secret: <secret>
     *   Body: { "notification": "TK 09xxx254 |GD: +5,000VND ..." }
     */
    public static function bdsd_webhook( $request ) {
        // Verify webhook secret
        $secret          = $request->get_header( 'X-Webhook-Secret' );
        $expected_secret = get_option( 'monkeypay_webhook_secret', '' );

        if ( empty( $expected_secret ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'error'   => 'WEBHOOK_SECRET not configured on this site',
            ], 500 );
        }

        if ( $secret !== $expected_secret ) {
            return new WP_REST_Response( [
                'success' => false,
                'error'   => 'Invalid webhook secret',
            ], 401 );
        }

        // Get notification from body
        $body         = $request->get_json_params();
        $notification = isset( $body['notification'] ) ? $body['notification'] : '';

        if ( empty( $notification ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'error'   => 'Missing "notification" field in body',
            ], 400 );
        }

        // Forward to MonkeyPay server
        $api_url = self::get_api_url();

        if ( empty( $api_url ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'error'   => 'MonkeyPay API URL not configured',
            ], 500 );
        }

        $response = wp_remote_post( $api_url . '/api/webhook/bdsd', [
            'timeout' => 15,
            'headers' => [
                'Content-Type'     => 'application/json',
                'X-Webhook-Secret' => $expected_secret,
            ],
            'body' => wp_json_encode( [ 'notification' => $notification ] ),
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'error'   => 'Failed to forward to server: ' . $response->get_error_message(),
            ], 502 );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        return new WP_REST_Response(
            $data ? $data : [ 'success' => $code < 400 ],
            $code
        );
    }

    /**
     * BDSD Webhook health check — test connectivity from Android phone.
     * GET /wp-json/monkeypay/v1/webhook/bdsd/test
     */
    public static function bdsd_webhook_test() {
        return new WP_REST_Response( [
            'status'  => 'ok',
            'message' => 'BDSD webhook endpoint ready',
            'time'    => current_time( 'mysql' ),
        ], 200 );
    }
}
