<?php
/**
 * MonkeyPay ↔ checkin-mkt192-wp Bridge
 *
 * When both plugins are active, this bridge:
 * 1. Listens for monkeypay_payment_confirmed webhook → updates invoice to 'paid'
 * 2. Provides action/filter hooks for checkin plugin to create MonkeyPay transactions
 * 3. Optionally disables the old cron-based auto-check polling
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_Checkin_Bridge {

    /** @var MonkeyPay_Checkin_Bridge|null */
    private static $instance = null;

    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Only activate bridge if enabled in settings
        if ( get_option( 'monkeypay_checkin_bridge', '0' ) !== '1' ) {
            return;
        }

        // Hook into payment confirmation
        add_action( 'monkeypay_payment_confirmed', [ $this, 'on_payment_confirmed' ], 10, 2 );

        // Hook into session expired
        add_action( 'monkeypay_session_expired', [ $this, 'on_session_expired' ] );

        // Provide action for checkin plugin to create MonkeyPay payment
        add_action( 'monkeypay_create_payment', [ $this, 'create_payment_for_invoice' ], 10, 3 );

        // Filter: Override the payment check function
        add_filter( 'monkeypay_is_available', '__return_true' );

        // Optionally disable old cron polling
        add_action( 'init', [ $this, 'maybe_disable_old_cron' ], 20 );

        // REST API: Create payment for invoice (used by checkin frontend)
        add_action( 'rest_api_init', [ $this, 'register_bridge_routes' ] );
    }

    /**
     * Register bridge-specific REST routes.
     */
    public function register_bridge_routes() {
        register_rest_route( 'monkeypay/v1', '/checkin/create-payment', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'rest_create_payment' ],
            'permission_callback' => function ( $request ) {
                // Use same permission as checkin plugin
                if ( function_exists( 'checkin_mkt192_permission' ) ) {
                    $check = checkin_mkt192_permission( [ 'mode' => 'login', 'scope' => 'payment.process' ] );
                    return is_callable( $check ) ? $check( $request ) : false;
                }
                return is_user_logged_in();
            },
            'args' => [
                'invoice_id' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );
    }

    /**
     * REST: Create payment for a checkin invoice.
     */
    public function rest_create_payment( $request ) {
        global $wpdb;
        $invoice_id = $request->get_param( 'invoice_id' );
        $table      = $wpdb->prefix . 'checkin_mkt192_invoices_data';

        $invoice = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE invoice_id = %s", $invoice_id ),
            ARRAY_A
        );

        if ( ! $invoice ) {
            return new WP_REST_Response( [ 'success' => false, 'message' => 'Hóa đơn không tồn tại.' ], 404 );
        }

        $amount = floatval( $invoice['total'] );

        // Generate payment note compatible with checkin format
        $note = '';
        if ( function_exists( 'checkin_mkt192_generate_payment_note' ) ) {
            $note = checkin_mkt192_generate_payment_note(
                $invoice_id,
                $invoice['customer_name'] ?? '',
                $invoice['created_at'] ?? ''
            );
        } else {
            $note = 'MKT' . $invoice_id;
        }

        // Create transaction on MonkeyPay Server
        $api    = new MonkeyPay_API_Client();
        $result = $api->create_transaction( $amount, $note );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 500 );
        }

        // Store the mapping: MonkeyPay tx_id → checkin invoice_id
        update_option( 'monkeypay_tx_' . $result['tx_id'], $invoice_id );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $result,
        ], 201 );
    }

    /**
     * Create payment for a checkin invoice (action hook).
     *
     * @param string $invoice_id
     * @param float  $amount
     * @param string $payment_note
     */
    public function create_payment_for_invoice( $invoice_id, $amount, $payment_note = '' ) {
        $api    = new MonkeyPay_API_Client();
        $result = $api->create_transaction( $amount, $payment_note ?: "Invoice #{$invoice_id}" );

        if ( ! is_wp_error( $result ) ) {
            // Store mapping
            update_option( 'monkeypay_tx_' . $result['tx_id'], $invoice_id );

            /**
             * Fires when a MonkeyPay payment is created for a checkin invoice.
             *
             * @param string $invoice_id
             * @param array  $result  MonkeyPay transaction data
             */
            do_action( 'monkeypay_payment_created', $invoice_id, $result );
        }

        return $result;
    }

    /**
     * Handle payment confirmation → update checkin invoice.
     *
     * @param string $tx_id MonkeyPay transaction ID
     * @param array  $data  Webhook payload
     */
    public function on_payment_confirmed( $tx_id, $data ) {
        global $wpdb;

        // Look up the checkin invoice mapped to this tx_id
        $invoice_id = get_option( 'monkeypay_tx_' . $tx_id, '' );
        if ( empty( $invoice_id ) ) {
            return; // Not a checkin transaction, skip
        }

        $table = $wpdb->prefix . 'checkin_mkt192_invoices_data';

        $wpdb->update(
            $table,
            [
                'status'         => 'paid',
                'payment_method' => 'bank_transfer',
                'paid_at'        => current_time( 'mysql' ),
                'updated_at'     => current_time( 'mysql' ),
            ],
            [ 'invoice_id' => $invoice_id ],
            [ '%s', '%s', '%s', '%s' ],
            [ '%s' ]
        );

        // Trigger checkin-specific notifications
        if ( function_exists( 'checkin_mkt192_push_payment_success' ) ) {
            checkin_mkt192_push_payment_success( $invoice_id );
        }

        // Lark notification now handled by MonkeyPay Connections system
        // (dispatched directly from MonkeyPay_Webhook::handle_payment_completed)

        // Cleanup mapping
        delete_option( 'monkeypay_tx_' . $tx_id );

        MonkeyPay_Logger::transaction( "Bridge: Invoice #{$invoice_id} marked as paid via tx {$tx_id}" );
    }

    /**
     * Handle session expired → log for checkin context.
     */
    public function on_session_expired( $data ) {
        MonkeyPay_Logger::webhook( 'Bridge: Session expired — checkin auto-pay unavailable until re-login' );
    }

    /**
     * Optionally disable old cron-based polling when bridge is active.
     */
    public function maybe_disable_old_cron() {
        $timestamp = wp_next_scheduled( 'checkin_mbbank_auto_check' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'checkin_mbbank_auto_check' );
            MonkeyPay_Logger::log( 'error', 'INFO', 'Bridge: Disabled old cron polling (checkin_mbbank_auto_check)' );
        }
    }
}

// Auto-initialize when loaded
MonkeyPay_Checkin_Bridge::init();
