<?php
/**
 * MonkeyPay Sync Service
 *
 * Handles 2-way synchronization between MonkeyPay server and local WP cache.
 * Local tables act as a fast cache; server remains the source of truth.
 *
 * Sync triggers:
 *  - admin_init on MonkeyPay pages (lazy, max once per 5 minutes)
 *  - AJAX manual "Sync Now" button
 *  - After successful gateway save/delete
 *
 * @package MonkeyPay
 * @since   3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_Sync {

    /** @var int Cache TTL in seconds (5 minutes) */
    const CACHE_TTL = 300;

    /** @var string Transient key for last sync timestamp */
    const LAST_SYNC_KEY = 'monkeypay_last_sync';

    // ─── API HELPERS ────────────────────────────────────────

    /**
     * Get resolved API URL (from settings or constant).
     *
     * @return string
     */
    private static function get_api_url() {
        $url = MonkeyPay_Settings::get( 'api_url', MONKEYPAY_API_URL );
        return rtrim( ! empty( $url ) ? $url : MONKEYPAY_API_URL, '/' );
    }

    /**
     * Get server API key from settings.
     *
     * @return string
     */
    private static function get_api_key() {
        return MonkeyPay_Settings::get( 'api_key', '' );
    }

    /**
     * Check if cache is stale (older than CACHE_TTL).
     *
     * @return bool
     */
    public static function is_cache_stale() {
        $last_sync = get_transient( self::LAST_SYNC_KEY );
        return $last_sync === false;
    }

    /**
     * Mark cache as fresh (reset TTL).
     */
    private static function mark_synced() {
        set_transient( self::LAST_SYNC_KEY, time(), self::CACHE_TTL );
    }

    // ─── SYNC ALL ───────────────────────────────────────────

    /**
     * Run a full sync of gateways + merchant profile from server.
     * Called on admin page load (lazy) or manual sync button.
     *
     * @param bool $force Force sync even if cache is fresh.
     * @return array Summary of what was synced.
     */
    public static function sync_all( $force = false ) {
        if ( ! $force && ! self::is_cache_stale() ) {
            return [ 'skipped' => true, 'reason' => 'Cache is fresh' ];
        }

        $api_key = self::get_api_key();
        if ( empty( $api_key ) ) {
            return [ 'skipped' => true, 'reason' => 'No API key configured' ];
        }

        $result = [
            'gateways' => self::sync_gateways(),
            'merchant' => self::sync_merchant_profile(),
        ];

        self::mark_synced();

        return $result;
    }

    // ─── GATEWAYS SYNC ──────────────────────────────────────

    /**
     * Fetch gateways from server and upsert into local cache table.
     *
     * @return array Sync result.
     */
    public static function sync_gateways() {
        $api_key = self::get_api_key();
        if ( empty( $api_key ) ) {
            return [ 'success' => false, 'message' => 'No API key' ];
        }

        $response = wp_remote_get( self::get_api_url() . '/api/gateways', [
            'timeout' => 15,
            'headers' => [
                'X-Api-Key' => $api_key,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return [ 'success' => false, 'message' => $response->get_error_message() ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 400 ) {
            return [ 'success' => false, 'message' => 'Server returned ' . $code ];
        }

        $body     = json_decode( wp_remote_retrieve_body( $response ), true );
        $gateways = isset( $body['gateways'] ) ? $body['gateways'] : ( is_array( $body ) ? $body : [] );

        if ( empty( $gateways ) || ! is_array( $gateways ) ) {
            return [ 'success' => true, 'synced' => 0 ];
        }

        global $wpdb;
        $table = MonkeyPay_DB::gateways_table();
        $now   = current_time( 'mysql' );
        $count = 0;

        foreach ( $gateways as $gw ) {
            $server_id = isset( $gw['id'] ) ? intval( $gw['id'] ) : null;

            // Check if already exists by server_id
            if ( $server_id ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $existing = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE server_id = %d LIMIT 1",
                    $server_id
                ) );
            } else {
                $existing = null;
            }

            $data = [
                'server_id'        => $server_id,
                'bank_code'        => sanitize_text_field( $gw['bank_code'] ?? '' ),
                'bank_name'        => sanitize_text_field( $gw['bank_name'] ?? '' ),
                'account_number'   => sanitize_text_field( $gw['account_number'] ?? '' ),
                'account_name'     => sanitize_text_field( $gw['account_name'] ?? '' ),
                'username'         => sanitize_text_field( $gw['username'] ?? '' ),
                'phone'            => sanitize_text_field( $gw['phone'] ?? '' ),
                'enabled'          => isset( $gw['enabled'] ) ? intval( $gw['enabled'] ) : 1,
                'synced_at'        => $now,
            ];

            // Config fields: CHỈ ghi đè khi server response CÓ chứa field
            // (tránh sync default 'MP' ghi đè config user đã set, ví dụ 'KYO')
            if ( array_key_exists( 'note_prefix', $gw ) ) {
                $data['note_prefix'] = sanitize_text_field( $gw['note_prefix'] ?? 'MP' );
            }
            if ( array_key_exists( 'note_syntax', $gw ) ) {
                $data['note_syntax'] = sanitize_text_field( $gw['note_syntax'] ?? '{prefix}{random:6}' );
            }
            if ( array_key_exists( 'auto_amount', $gw ) ) {
                $data['auto_amount'] = intval( $gw['auto_amount'] );
            }
            if ( array_key_exists( 'polling_interval', $gw ) ) {
                $data['polling_interval'] = intval( $gw['polling_interval'] );
            }

            // INSERT mới thì dùng default cho config fields nếu thiếu
            if ( ! $existing ) {
                if ( ! isset( $data['note_prefix'] ) )      $data['note_prefix'] = sanitize_text_field( $gw['note_prefix'] ?? 'MP' );
                if ( ! isset( $data['note_syntax'] ) )      $data['note_syntax'] = sanitize_text_field( $gw['note_syntax'] ?? '{prefix}{random:6}' );
                if ( ! isset( $data['auto_amount'] ) )      $data['auto_amount'] = isset( $gw['auto_amount'] ) ? intval( $gw['auto_amount'] ) : 1;
                if ( ! isset( $data['polling_interval'] ) ) $data['polling_interval'] = isset( $gw['polling_interval'] ) ? intval( $gw['polling_interval'] ) : 5;
            }

            if ( $existing ) {
                // UPDATE existing
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->update( $table, $data, [ 'id' => $existing ] );
            } else {
                // INSERT new
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->insert( $table, $data );
            }
            $count++;
        }

        // Remove local gateways whose server_id no longer exists on server
        $server_ids = array_filter( array_map( function ( $gw ) {
            return isset( $gw['id'] ) ? intval( $gw['id'] ) : null;
        }, $gateways ) );

        if ( ! empty( $server_ids ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $server_ids ), '%d' ) );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$table} WHERE server_id IS NOT NULL AND server_id NOT IN ({$placeholders})",
                ...$server_ids
            ) );
        }

        return [ 'success' => true, 'synced' => $count ];
    }

    // ─── MERCHANT PROFILE SYNC ──────────────────────────────

    /**
     * Fetch merchant profile from server and upsert into local cache.
     *
     * @return array Sync result.
     */
    public static function sync_merchant_profile() {
        $api_key = self::get_api_key();
        if ( empty( $api_key ) ) {
            return [ 'success' => false, 'message' => 'No API key' ];
        }

        $response = wp_remote_get( self::get_api_url() . '/api/me', [
            'timeout' => 15,
            'headers' => [
                'X-Api-Key' => $api_key,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return [ 'success' => false, 'message' => $response->get_error_message() ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 400 ) {
            return [ 'success' => false, 'message' => 'Server returned ' . $code ];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body ) || ! is_array( $body ) ) {
            return [ 'success' => false, 'message' => 'Empty response' ];
        }

        // /api/me returns { merchant: {...}, plan: {...}, usage: {...} }
        $merchant = $body['merchant'] ?? $body;    // fallback to flat if needed
        $plan     = $body['plan']     ?? [];
        $usage    = $body['usage']    ?? [];

        global $wpdb;
        $table = MonkeyPay_DB::merchant_table();
        $now   = current_time( 'mysql' );

        $data = [
            'server_merchant_id'  => isset( $merchant['id'] ) ? intval( $merchant['id'] ) : null,
            'name'                => sanitize_text_field( $merchant['name'] ?? '' ),
            'email'               => sanitize_email( $merchant['email'] ?? '' ),
            'phone'               => sanitize_text_field( $merchant['phone'] ?? '' ),
            'site_url'            => esc_url_raw( $merchant['site_url'] ?? '' ),
            'plan_id'             => sanitize_text_field( $plan['id'] ?? $merchant['plan_id'] ?? 'free' ),
            'plan_name'           => sanitize_text_field( $plan['name'] ?? 'Free' ),
            'plan_price'          => intval( $plan['price'] ?? 0 ),
            'request_limit'       => intval( $plan['request_limit'] ?? 50 ),
            'request_count'       => intval( $usage['request_count'] ?? $merchant['request_count'] ?? 0 ),
            'max_gateways'        => intval( $plan['max_gateways'] ?? 1 ),
            'max_accounts_per_gw' => intval( $plan['max_accounts_per_gw'] ?? 1 ),
            'plan_features'       => is_array( $plan['features'] ?? null )
                ? wp_json_encode( $plan['features'] )
                : ( $plan['features'] ?? null ),
            'period_start'        => $usage['period_start'] ?? $merchant['period_start'] ?? null,
            'status'              => sanitize_text_field( $merchant['status'] ?? 'active' ),
            'synced_at'           => $now,
        ];

        // Upsert: if any row exists, update it; otherwise insert
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $existing_id = $wpdb->get_var( "SELECT id FROM {$table} LIMIT 1" );

        if ( $existing_id ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->update( $table, $data, [ 'id' => $existing_id ] );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert( $table, $data );
        }

        return [ 'success' => true ];
    }

    // ─── LOCAL CACHE READERS ────────────────────────────────

    /**
     * Get cached gateways from local table.
     * Auto-syncs if cache is stale.
     *
     * @return array List of gateway arrays.
     */
    public static function get_cached_gateways() {
        if ( self::is_cache_stale() ) {
            self::sync_gateways();
            self::mark_synced();
        }

        global $wpdb;
        $table = MonkeyPay_DB::gateways_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE enabled = 1 ORDER BY id ASC", ARRAY_A );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * Get cached merchant profile from local table.
     * Auto-syncs if cache is stale.
     *
     * @return array|null Merchant data or null.
     */
    public static function get_merchant_profile() {
        if ( self::is_cache_stale() ) {
            self::sync_merchant_profile();
            self::mark_synced();
        }

        global $wpdb;
        $table = MonkeyPay_DB::merchant_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $row = $wpdb->get_row( "SELECT * FROM {$table} LIMIT 1", ARRAY_A );

        return is_array( $row ) ? $row : null;
    }

    /**
     * Save a single gateway to local cache after server save succeeds.
     *
     * @param int   $server_id Server-assigned gateway ID.
     * @param array $data      Gateway data.
     */
    public static function cache_gateway( $server_id, $data ) {
        global $wpdb;
        $table = MonkeyPay_DB::gateways_table();
        $now   = current_time( 'mysql' );

        $row = [
            'server_id'        => intval( $server_id ),
            'bank_code'        => sanitize_text_field( $data['bank_code'] ?? '' ),
            'bank_name'        => sanitize_text_field( $data['bank_name'] ?? '' ),
            'account_number'   => sanitize_text_field( $data['account_number'] ?? '' ),
            'account_name'     => sanitize_text_field( $data['account_name'] ?? '' ),
            'username'         => sanitize_text_field( $data['username'] ?? '' ),
            'phone'            => sanitize_text_field( $data['phone'] ?? '' ),
            'note_prefix'      => sanitize_text_field( $data['note_prefix'] ?? 'MP' ),
            'note_syntax'      => sanitize_text_field( $data['note_syntax'] ?? '{prefix}{random:6}' ),
            'auto_amount'      => isset( $data['auto_amount'] ) ? intval( $data['auto_amount'] ) : 1,
            'polling_interval' => isset( $data['polling_interval'] ) ? intval( $data['polling_interval'] ) : 5,
            'enabled'          => isset( $data['enabled'] ) ? intval( $data['enabled'] ) : 1,
            'synced_at'        => $now,
        ];

        // Check if exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE server_id = %d LIMIT 1",
            $server_id
        ) );

        if ( $existing ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->update( $table, $row, [ 'id' => $existing ] );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert( $table, $row );
        }
    }

    /**
     * Remove a gateway from local cache by server_id.
     *
     * @param int $server_id Server-assigned gateway ID.
     */
    public static function remove_cached_gateway( $server_id ) {
        global $wpdb;
        $table = MonkeyPay_DB::gateways_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->delete( $table, [ 'server_id' => intval( $server_id ) ], [ '%d' ] );
    }
}
