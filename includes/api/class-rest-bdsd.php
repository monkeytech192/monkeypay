<?php
/**
 * MonkeyPay REST API — BDSD Transactions
 *
 * Provides local BDSD transaction data stored from webhook events.
 * Used by the transactions page to merge BDSD IDs into bank history.
 *
 * @package MonkeyPay
 * @since   3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_REST_BDSD {

    /**
     * Register REST routes.
     */
    public static function register_routes() {
        // GET /monkeypay/v1/bdsd-transactions?from=DD/MM/YYYY&to=DD/MM/YYYY
        register_rest_route( 'monkeypay/v1', '/bdsd-transactions', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_transactions' ],
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

        // POST /monkeypay/v1/reconcile — batch match bank txs with checkin invoices
        register_rest_route( 'monkeypay/v1', '/reconcile', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'reconcile_transactions' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    /**
     * Get BDSD transactions from local DB.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_transactions( $request ) {
        $from = $request->get_param( 'from' ) ?? '';
        $to   = $request->get_param( 'to' ) ?? '';

        $transactions = MonkeyPay_DB::get_transactions( $from, $to );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => [
                'transactions' => $transactions,
                'total'        => count( $transactions ),
            ],
        ], 200 );
    }

    /**
     * Batch reconcile bank transactions with checkin invoices.
     *
     * Receives array of { description, amount } pairs.
     * Extracts payment_note (MKT prefix) from description,
     * queries checkin invoices table, checks amount match.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function reconcile_transactions( $request ) {
        global $wpdb;

        $items = $request->get_json_params();
        if ( ! is_array( $items ) ) {
            return new WP_REST_Response( [ 'success' => false, 'message' => 'Invalid payload' ], 400 );
        }

        // Check if checkin plugin table exists
        $table = $wpdb->prefix . 'checkin_mkt192_invoices_data';
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

        if ( ! $table_exists ) {
            // Return all as N/A if checkin plugin not installed
            $results = array_map( function () {
                return [ 'status' => 'na', 'invoice_id' => '', 'invoice_status' => '' ];
            }, $items );
            return new WP_REST_Response( [ 'success' => true, 'data' => $results ], 200 );
        }

        // Get payment note prefix from checkin settings
        $prefix = get_option( 'checkin_mbbank_note_prefix', 'MKT' );
        $prefix_upper = strtoupper( $prefix );

        $results = [];

        foreach ( $items as $item ) {
            $desc   = isset( $item['description'] ) ? strtoupper( sanitize_text_field( $item['description'] ) ) : '';
            $amount = isset( $item['amount'] ) ? abs( floatval( $item['amount'] ) ) : 0;

            // Try to extract invoice_id from description
            // Pattern: PREFIX followed by digits (e.g. MKT12345)
            $invoice_id = '';
            if ( ! empty( $prefix_upper ) && strpos( $desc, $prefix_upper ) !== false ) {
                // Extract the part after prefix
                $pos = strpos( $desc, $prefix_upper );
                $after = substr( $desc, $pos + strlen( $prefix_upper ) );
                // Get consecutive digits/alphanumeric
                if ( preg_match( '/^([A-Z0-9\-]+)/i', $after, $m ) ) {
                    $invoice_id = $m[1];
                }
            }

            if ( empty( $invoice_id ) ) {
                $results[] = [ 'status' => 'na', 'invoice_id' => '', 'invoice_status' => '' ];
                continue;
            }

            // Query invoice
            $invoice = $wpdb->get_row(
                $wpdb->prepare( "SELECT invoice_id, total, status FROM {$table} WHERE invoice_id = %s", $invoice_id ),
                ARRAY_A
            );

            if ( ! $invoice ) {
                $results[] = [ 'status' => 'not_found', 'invoice_id' => $prefix . $invoice_id, 'invoice_status' => '' ];
                continue;
            }

            $invoice_total = abs( floatval( $invoice['total'] ) );

            // Check amount match (allow 1 VND tolerance for rounding)
            $amount_match = abs( $amount - $invoice_total ) <= 1;

            if ( $amount_match && $invoice['status'] === 'paid' ) {
                $results[] = [
                    'status'         => 'matched',
                    'invoice_id'     => $prefix . $invoice_id,
                    'invoice_status' => $invoice['status'],
                ];
            } elseif ( $amount_match ) {
                $results[] = [
                    'status'         => 'amount_ok',
                    'invoice_id'     => $prefix . $invoice_id,
                    'invoice_status' => $invoice['status'],
                ];
            } else {
                $results[] = [
                    'status'         => 'mismatch',
                    'invoice_id'     => $prefix . $invoice_id,
                    'invoice_status' => $invoice['status'],
                    'expected'       => $invoice_total,
                    'actual'         => $amount,
                ];
            }
        }

        return new WP_REST_Response( [ 'success' => true, 'data' => $results ], 200 );
    }
}

