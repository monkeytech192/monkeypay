<?php
/**
 * MonkeyPay API Client
 *
 * Handles all communication with the MonkeyPay Server.
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_API_Client {

    /**
     * Get configured API URL.
     *
     * @return string
     */
    private function get_api_url() {
        $url = get_option( 'monkeypay_api_url', MONKEYPAY_API_URL );
        return rtrim( ! empty( $url ) ? $url : MONKEYPAY_API_URL, '/' );
    }

    /**
     * Get configured API Key.
     *
     * @return string
     */
    private function get_api_key() {
        return get_option( 'monkeypay_api_key', '' );
    }

    /**
     * Make a request to MonkeyPay Server.
     *
     * @param string $endpoint  API endpoint (e.g., /api/health)
     * @param string $method    HTTP method
     * @param array  $body      Request body
     * @return array|WP_Error
     */
    public function request( $endpoint, $method = 'GET', $body = [] ) {
        $url = $this->get_api_url() . $endpoint;

        if ( empty( $this->get_api_url() ) ) {
            return new WP_Error( 'monkeypay_not_configured', __( 'MonkeyPay chưa được cấu hình.', 'monkeypay' ) );
        }

        $args = [
            'method'  => $method,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Key'    => $this->get_api_key(),
            ],
        ];

        if ( $method === 'POST' && ! empty( $body ) ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            $message = isset( $data['error'] ) ? $data['error'] : __( 'Lỗi không xác định', 'monkeypay' );
            return new WP_Error( 'monkeypay_api_error', $message, [ 'status' => $code ] );
        }

        return $data;
    }

    /**
     * Health check.
     *
     * @return array|WP_Error
     */
    public function health_check() {
        return $this->request( '/api/health' );
    }

    /**
     * Create a payment transaction.
     *
     * @param float  $amount      Payment amount in VND
     * @param string $description Optional description
     * @return array|WP_Error  { tx_id, payment_note, amount, bank_info, qr_url, expires_at }
     */
    public function create_transaction( $amount, $description = '', $mode = 'auto' ) {
        return $this->request( '/api/transactions', 'POST', [
            'amount'      => floatval( $amount ),
            'description' => sanitize_text_field( $description ),
            'mode'        => in_array( $mode, ['auto', 'manual'], true ) ? $mode : 'auto',
        ] );
    }

    /**
     * Check transaction status.
     *
     * @param string $tx_id Transaction ID
     * @return array|WP_Error
     */
    public function check_transaction( $tx_id ) {
        return $this->request( '/api/transactions/' . sanitize_text_field( $tx_id ) );
    }
}
