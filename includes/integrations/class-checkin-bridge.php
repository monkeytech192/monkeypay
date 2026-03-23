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
        if ( MonkeyPay_Settings::get( 'checkin_bridge' ) !== '1' ) {
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

        // Check payment status (used by checkin frontend polling)
        register_rest_route( 'monkeypay/v1', '/checkin/check-payment', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_check_payment' ],
            'permission_callback' => function ( $request ) {
                if ( function_exists( 'checkin_mkt192_permission' ) ) {
                    $check = checkin_mkt192_permission( [ 'mode' => 'login', 'scope' => 'payment.read' ] );
                    return is_callable( $check ) ? $check( $request ) : false;
                }
                return is_user_logged_in();
            },
            'args' => [
                'tx_id' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        // Bridge transactions (used by checkin reconciliation)
        register_rest_route( 'monkeypay/v1', '/checkin-bridge/transactions', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_transactions' ],
            'permission_callback' => function ( $request ) {
                if ( function_exists( 'checkin_mkt192_permission' ) ) {
                    $check = checkin_mkt192_permission( [ 'mode' => 'login', 'scope' => 'payment.read' ] );
                    return is_callable( $check ) ? $check( $request ) : false;
                }
                return is_user_logged_in();
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

        // Gateway config (used by checkin to read note_prefix, note_syntax, etc.)
        register_rest_route( 'monkeypay/v1', '/checkin/gateway-config', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_gateway_config' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    /**
     * REST: Get bank transactions for checkin reconciliation.
     * Proxies to MonkeyPay API Server /api/bank/history
     * (same source as the admin Transactions page).
     *
     * Note: API Server expects date format DD/MM/YYYY.
     */
    public function rest_get_transactions( $request ) {
        $api = new MonkeyPay_API_Client();

        $endpoint = '/api/bank/history';
        $from     = $request->get_param( 'from' );
        $to       = $request->get_param( 'to' );

        // Default: last 7 days if no date range specified
        if ( empty( $from ) && empty( $to ) ) {
            $to   = wp_date( 'd/m/Y' );
            $from = wp_date( 'd/m/Y', strtotime( '-6 days' ) );
        } else {
            // Convert yyyy-mm-dd → DD/MM/YYYY for bank API
            if ( $from && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) {
                $from = date( 'd/m/Y', strtotime( $from ) );
            }
            if ( $to && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
                $to = date( 'd/m/Y', strtotime( $to ) );
            }
        }

        $params = [];
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

        // DEBUG: log raw API response to understand structure
        error_log( '[MonkeyPay Bridge] bank/history raw result: ' . wp_json_encode( $result ) );

        // Normalize: ensure 'transactions' key at top level for frontend
        $transactions = $result['data']['transactions']
            ?? $result['transactions']
            ?? [];

        return new WP_REST_Response( [
            'success'      => true,
            'transactions' => $transactions,
            'total'        => count( $transactions ),
            '_debug_keys'  => is_array( $result ) ? array_keys( $result ) : 'not_array',
            '_debug_from'  => $from,
            '_debug_to'    => $to,
        ], 200 );
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

        // Store the mapping: MonkeyPay tx_id → checkin invoice_id (in transactions table)
        MonkeyPay_DB::insert_payment( [
            'tx_id'          => $result['tx_id'],
            'amount'         => $amount,
            'payment_note'   => $note,
            'reference_type' => 'checkin_invoice',
            'reference_id'   => (string) $invoice_id,
            'source'         => 'payment_create',
        ] );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $result,
        ], 201 );
    }

    /**
     * REST: Check payment status for a transaction.
     * Proxies the status check to MonkeyPay Server.
     */
    public function rest_check_payment( $request ) {
        $tx_id   = $request->get_param( 'tx_id' );
        $api_key = MonkeyPay_Settings::get( 'api_key' );
        $api_url = MonkeyPay_Settings::get( 'api_url', MONKEYPAY_API_URL );
        $api_url = rtrim( ! empty( $api_url ) ? $api_url : MONKEYPAY_API_URL, '/' );

        $response = wp_remote_get( $api_url . '/api/transactions/' . urlencode( $tx_id ), [
            'timeout' => 10,
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

        return new WP_REST_Response( [
            'success' => $code < 400,
            'data'    => $data,
        ], $code );
    }

    /**
     * REST: Return active gateway config (note_prefix, note_syntax, auto_amount, polling_interval).
     * Fetches from MonkeyPay Server, cached in transient for 5 minutes.
     */
    public function rest_gateway_config() {
        // Check cache first
        $cached = get_transient( 'monkeypay_gateway_config' );
        if ( false !== $cached ) {
            return new WP_REST_Response( [
                'success' => true,
                'data'    => $cached,
                'cached'  => true,
            ], 200 );
        }

        $api_key = MonkeyPay_Settings::get( 'api_key' );
        $api_url = MonkeyPay_Settings::get( 'api_url', MONKEYPAY_API_URL );
        $api_url = rtrim( ! empty( $api_url ) ? $api_url : MONKEYPAY_API_URL, '/' );

        $response = wp_remote_get( $api_url . '/api/gateways', [
            'timeout' => 10,
            'headers' => [
                'X-Api-Key' => $api_key,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            // Fallback to wp_options if server unreachable
            return new WP_REST_Response( [
                'success'  => true,
                'data'     => $this->get_fallback_config(),
                'fallback' => true,
            ], 200 );
        }

        $body     = json_decode( wp_remote_retrieve_body( $response ), true );
        $gateways = $body['gateways'] ?? $body['data'] ?? [];

        // Find active gateway (first one with status 'active')
        $active = null;
        foreach ( $gateways as $gw ) {
            if ( ( $gw['status'] ?? '' ) === 'active' ) {
                $active = $gw;
                break;
            }
        }

        // Fallback to first gateway if none active
        if ( ! $active && ! empty( $gateways ) ) {
            $active = $gateways[0];
        }

        if ( ! $active ) {
            return new WP_REST_Response( [
                'success'  => true,
                'data'     => $this->get_fallback_config(),
                'fallback' => true,
            ], 200 );
        }

        $config = [
            'auto_amount'      => (bool) ( $active['auto_amount'] ?? true ),
            'note_prefix'      => $active['note_prefix'] ?? 'MKT',
            'note_syntax'      => $active['note_syntax'] ?? '{invoice_id}',
            'polling_interval' => intval( $active['polling_interval'] ?? 5 ),
        ];

        // Cache for 5 minutes
        set_transient( 'monkeypay_gateway_config', $config, 5 * MINUTE_IN_SECONDS );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $config,
        ], 200 );
    }

    /**
     * Fallback config when server is unreachable.
     */
    private function get_fallback_config() {
        return [
            'auto_amount'      => get_option( 'checkin_bank_auto_amount', '1' ) === '1',
            'note_prefix'      => get_option( 'checkin_monkeypay_note_prefix', get_option( 'checkin_mbbank_note_prefix', 'MKT' ) ),
            'note_syntax'      => get_option( 'checkin_monkeypay_note_syntax', get_option( 'checkin_mbbank_note_syntax', '{invoice_id}' ) ),
            'polling_interval' => intval( get_option( 'checkin_monkeypay_polling_interval', get_option( 'checkin_mbbank_polling_interval', '5' ) ) ),
        ];
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
            // Store mapping (in transactions table)
            MonkeyPay_DB::insert_payment( [
                'tx_id'          => $result['tx_id'],
                'amount'         => $amount,
                'payment_note'   => $payment_note ?: "Invoice #{$invoice_id}",
                'reference_type' => 'checkin_invoice',
                'reference_id'   => (string) $invoice_id,
                'source'         => 'payment_create',
            ] );

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

        // Look up the checkin invoice mapped to this tx_id from transactions table
        $tx_row = MonkeyPay_DB::find_by_tx_id( $tx_id );
        if ( ! $tx_row || $tx_row['reference_type'] !== 'checkin_invoice' || empty( $tx_row['reference_id'] ) ) {
            return; // Not a checkin transaction, skip
        }

        $invoice_id = $tx_row['reference_id'];
        $table      = $wpdb->prefix . 'checkin_mkt192_invoices_data';

        $wpdb->update(
            $table,
            [
                'status'         => 'paid',
                'payment_method' => 'bank',
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

        // Update transaction status to confirmed
        MonkeyPay_DB::update_status( $tx_id, 'confirmed', [
            'confirmed_at' => current_time( 'mysql' ),
        ] );

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
