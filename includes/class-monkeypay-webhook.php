<?php
/**
 * MonkeyPay Webhook Receiver
 *
 * Receives webhook callbacks from MonkeyPay Server and dispatches WordPress actions.
 * Verifies HMAC-SHA256 signature for security.
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_Webhook {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_webhook_endpoint' ] );
    }

    /**
     * Register the webhook REST endpoint.
     */
    public function register_webhook_endpoint() {
        register_rest_route( 'monkeypay/v1', '/webhook', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_webhook' ],
            'permission_callback' => '__return_true', // Verified via HMAC
        ] );
    }

    /**
     * Handle incoming webhook from MonkeyPay Server.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_webhook( $request ) {
        $body      = $request->get_body();
        $signature = $request->get_header( 'X-MonkeyPay-Signature' );
        $secret    = get_option( 'monkeypay_webhook_secret', '' );

        // Verify signature
        if ( ! empty( $secret ) ) {
            $expected = hash_hmac( 'sha256', $body, $secret );
            if ( ! hash_equals( $expected, $signature ?? '' ) ) {
                return new WP_REST_Response( [
                    'success' => false,
                    'message' => 'Invalid signature',
                ], 401 );
            }
        }

        $data  = json_decode( $body, true );
        $event = isset( $data['event'] ) ? sanitize_text_field( $data['event'] ) : '';

        if ( empty( $event ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Missing event',
            ], 400 );
        }

        // Log webhook
        MonkeyPay_Logger::webhook( 'Webhook received: ' . $event, $data );

        // Dispatch events
        switch ( $event ) {
            case 'payment_completed':
                $this->handle_payment_completed( $data );
                break;

            case 'bank_transaction':
                $this->handle_bank_transaction( $data );
                break;

            case 'session_expired':
                $this->handle_session_expired( $data );
                break;

            default:
                /**
                 * Fires for custom webhook events.
                 *
                 * @param string $event Event name
                 * @param array  $data  Event data
                 */
                do_action( 'monkeypay_webhook_' . $event, $data );
                break;
        }

        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    /**
     * Handle bank_transaction event — BDSD notification from Cloud Run.
     * Dispatches to Lark/connections for ALL bank transactions (not just payment matches).
     *
     * @param array $data Webhook payload from Cloud Run
     */
    private function handle_bank_transaction( $data ) {
        $amount       = isset( $data['amount'] ) ? floatval( $data['amount'] ) : 0;
        $is_credit    = isset( $data['is_credit'] ) ? (bool) $data['is_credit'] : ( $amount > 0 );
        $description  = isset( $data['description'] ) ? sanitize_text_field( $data['description'] ) : '';
        $account_no   = isset( $data['account_number'] ) ? sanitize_text_field( $data['account_number'] ) : '';
        $bank_name    = isset( $data['bank_name'] ) ? sanitize_text_field( $data['bank_name'] ) : 'MB Bank';
        $tx_date      = isset( $data['transaction_date'] ) ? sanitize_text_field( $data['transaction_date'] ) : current_time( 'mysql' );

        // Format date for Lark display
        if ( strpos( $tx_date, 'T' ) !== false ) {
            $tx_date = date( 'd/m/Y H:i:s', strtotime( $tx_date ) );
        }

        // Build data for Lark formatter (reuse existing template variables)
        $dispatch_data = [
            'amount'       => $amount,
            'payment_note' => $description,
            'description'  => $description,
            'bank_name'    => $bank_name,
            'account_no'   => $account_no,
            'matched_at'   => $tx_date,
            'tx_id'        => isset( $data['bdsd_id'] ) ? 'BDSD-' . $data['bdsd_id'] : '',
        ];

        // Dispatch to connections: payment_received (credit) or payment_sent (debit)
        $event = $is_credit ? 'payment_received' : 'payment_sent';

        $connections = MonkeyPay_Connections::get_instance();
        $connections->dispatch_event( $event, $dispatch_data );

        /**
         * Fires when a bank transaction is received via BDSD webhook.
         *
         * @param array  $dispatch_data Formatted transaction data
         * @param string $event         Event type (payment_received or payment_sent)
         * @param array  $data          Raw webhook payload
         */
        do_action( 'monkeypay_bank_transaction', $dispatch_data, $event, $data );

        MonkeyPay_Logger::webhook( "Bank transaction ({$event}): {$amount} VND", [
            'account_no'  => $account_no,
            'description' => $description,
            'is_credit'   => $is_credit,
        ] );
    }

    /**
     * Handle payment_completed event.
     *
     * @param array $data Webhook payload
     */
    private function handle_payment_completed( $data ) {
        $tx_id        = isset( $data['tx_id'] ) ? sanitize_text_field( $data['tx_id'] ) : '';
        $amount       = isset( $data['amount'] ) ? floatval( $data['amount'] ) : 0;
        $payment_note = isset( $data['payment_note'] ) ? sanitize_text_field( $data['payment_note'] ) : '';

        /**
         * Fires when a payment is confirmed via webhook.
         *
         * This is the PRIMARY integration hook. Other plugins (checkin-mkt192-wp,
         * WooCommerce, etc.) should hook into this to update their records.
         *
         * @param string $tx_id        MonkeyPay transaction ID
         * @param array  $data         Full webhook payload (amount, payment_note, matched_transaction, etc.)
         */
        do_action( 'monkeypay_payment_confirmed', $tx_id, $data );

        // Dispatch to webhook connections (Lark, Slack, custom, etc.)
        $connections = MonkeyPay_Connections::get_instance();
        $connections->dispatch_event( 'payment_received', $data );

        MonkeyPay_Logger::transaction( "Payment confirmed: {$tx_id}", [
            'amount'       => $amount,
            'payment_note' => $payment_note,
            'tx_id'        => $tx_id,
        ] );
    }

    /**
     * Handle session_expired event.
     *
     * @param array $data Webhook payload
     */
    private function handle_session_expired( $data ) {
        // Store notification for admin
        update_option( 'monkeypay_session_status', 'expired' );
        update_option( 'monkeypay_session_expired_at', current_time( 'mysql' ) );

        /**
         * Fires when the MB Bank session expires.
         * Admin should re-login and update session.
         *
         * @param array $data Webhook payload
         */
        do_action( 'monkeypay_session_expired', $data );

        // Admin notice
        set_transient( 'monkeypay_admin_notice', [
            'type'    => 'error',
            'message' => __( '⚠️ Phiên MB Bank đã hết hạn. Vui lòng đăng nhập lại và cập nhật session.', 'monkeypay' ),
        ], 3600 );

        MonkeyPay_Logger::webhook( 'Session expired — admin notification set' );
    }
}
