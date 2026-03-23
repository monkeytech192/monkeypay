<?php
/**
 * MonkeyPay REST API — Local API Key Management
 *
 * Generates, stores, lists, and revokes API keys in the dedicated
 * monkeypay_api_keys database table (replaces legacy wp_options storage).
 * Keys are stored hashed; the full key is shown only once at creation.
 *
 * Key format: mkp_live_<32 hex chars> (40 chars total)
 *
 * @package MonkeyPay
 * @since   3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_REST_API_Keys {

    /** Key prefix. */
    const KEY_PREFIX = 'mkp_live_';

    /**
     * Register REST routes for API key management (admin only).
     */
    public static function register_routes() {
        // List all API keys
        register_rest_route( 'monkeypay/v1', '/api-keys', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'list_keys' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Create new API key
        register_rest_route( 'monkeypay/v1', '/api-keys', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'create_key' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Update API key label
        register_rest_route( 'monkeypay/v1', '/api-keys/(?P<id>[a-zA-Z0-9_.]+)', [
            'methods'             => 'PUT',
            'callback'            => [ __CLASS__, 'update_key' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Revoke API key
        register_rest_route( 'monkeypay/v1', '/api-keys/(?P<id>[a-zA-Z0-9_.]+)/revoke', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'revoke_key' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Delete API key (hard delete)
        register_rest_route( 'monkeypay/v1', '/api-keys/(?P<id>[a-zA-Z0-9_.]+)', [
            'methods'             => 'DELETE',
            'callback'            => [ __CLASS__, 'delete_key' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    // ─── Key Generation ─────────────────────────────────

    /**
     * Generate a cryptographically secure API key.
     *
     * @return string Full key: mkp_live_<32 hex chars>
     */
    private static function generate_key() {
        return self::KEY_PREFIX . bin2hex( random_bytes( 16 ) );
    }

    /**
     * Hash an API key for storage using WordPress password hashing.
     *
     * @param  string $key The plain-text key.
     * @return string      The hashed key.
     */
    private static function hash_key( $key ) {
        return wp_hash_password( $key );
    }

    /**
     * Verify a plain-text key against a stored hash.
     *
     * @param  string $key  Plain-text key.
     * @param  string $hash Stored hash.
     * @return bool
     */
    private static function verify_key( $key, $hash ) {
        return wp_check_password( $key, $hash );
    }

    // ─── Public Validation (used by other endpoints) ────

    /**
     * Validate an API key from a request.
     * Checks against all active keys in the DB table.
     * Updates last_used_at timestamp on match.
     *
     * @param  string $api_key Plain-text key from request.
     * @return array|false     Key record on success, false on failure.
     */
    public static function validate_api_key( $api_key ) {
        if ( empty( $api_key ) || strpos( $api_key, self::KEY_PREFIX ) !== 0 ) {
            return false;
        }

        global $wpdb;
        $table = MonkeyPay_DB::api_keys_table();

        // Fetch only active keys
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $keys = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE status = 'active'",
            ARRAY_A
        );

        if ( empty( $keys ) ) {
            return false;
        }

        foreach ( $keys as $key_record ) {
            if ( self::verify_key( $api_key, $key_record['key_hash'] ) ) {
                // Update last_used_at timestamp
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->update(
                    $table,
                    [ 'last_used_at' => current_time( 'mysql' ) ],
                    [ 'id' => $key_record['id'] ],
                    [ '%s' ],
                    [ '%s' ]
                );

                return $key_record;
            }
        }

        return false;
    }

    // ─── REST Callbacks ─────────────────────────────────

    /**
     * List all API keys (masked, no hashes exposed).
     */
    public static function list_keys() {
        global $wpdb;
        $table = MonkeyPay_DB::api_keys_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $keys = $wpdb->get_results(
            "SELECT id, label, key_prefix, status, created_at, last_used_at, revoked_at FROM {$table} ORDER BY created_at DESC",
            ARRAY_A
        );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => is_array( $keys ) ? $keys : [],
        ], 200 );
    }

    /**
     * Create a new API key.
     * Returns the full key ONCE; it will never be retrievable again.
     */
    public static function create_key( $request ) {
        $body  = $request->get_json_params();
        $label = isset( $body['label'] ) ? sanitize_text_field( $body['label'] ) : 'API Key';

        if ( empty( $label ) ) {
            $label = 'API Key';
        }

        // Generate key
        $full_key   = self::generate_key();
        $key_hash   = self::hash_key( $full_key );
        $key_prefix = substr( $full_key, 0, 16 ); // mkp_live_ + first 8 hex
        $id         = uniqid( 'key_', true );
        $now        = current_time( 'mysql' );

        global $wpdb;
        $table = MonkeyPay_DB::api_keys_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $inserted = $wpdb->insert(
            $table,
            [
                'id'           => $id,
                'label'        => $label,
                'key_hash'     => $key_hash,
                'key_prefix'   => $key_prefix,
                'status'       => 'active',
                'created_at'   => $now,
                'last_used_at' => null,
                'revoked_at'   => null,
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( false === $inserted ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Không thể tạo API Key. Vui lòng thử lại.',
            ], 500 );
        }

        // Return FULL key once (for user to copy)
        return new WP_REST_Response( [
            'success' => true,
            'data'    => [
                'id'         => $id,
                'label'      => $label,
                'api_key'    => $full_key,
                'key_prefix' => $key_prefix,
                'status'     => 'active',
                'created_at' => $now,
            ],
        ], 201 );
    }

    /**
     * Update an API key label.
     */
    public static function update_key( $request ) {
        $id    = sanitize_text_field( $request->get_param( 'id' ) );
        $body  = $request->get_json_params();
        $label = isset( $body['label'] ) ? sanitize_text_field( $body['label'] ) : '';

        if ( empty( $label ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Label không được để trống.',
            ], 400 );
        }

        global $wpdb;
        $table = MonkeyPay_DB::api_keys_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $updated = $wpdb->update(
            $table,
            [ 'label' => $label ],
            [ 'id' => $id ],
            [ '%s' ],
            [ '%s' ]
        );

        if ( false === $updated ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Lỗi cập nhật. Vui lòng thử lại.',
            ], 500 );
        }

        if ( 0 === $updated ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'API Key không tìm thấy.',
            ], 404 );
        }

        return new WP_REST_Response( [
            'success' => true,
            'message' => 'Đã cập nhật label.',
        ], 200 );
    }

    /**
     * Revoke an API key (soft delete — status → revoked).
     */
    public static function revoke_key( $request ) {
        $id = sanitize_text_field( $request->get_param( 'id' ) );

        global $wpdb;
        $table = MonkeyPay_DB::api_keys_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $updated = $wpdb->update(
            $table,
            [
                'status'     => 'revoked',
                'revoked_at' => current_time( 'mysql' ),
            ],
            [ 'id' => $id ],
            [ '%s', '%s' ],
            [ '%s' ]
        );

        if ( false === $updated ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Lỗi thu hồi key. Vui lòng thử lại.',
            ], 500 );
        }

        if ( 0 === $updated ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'API Key không tìm thấy.',
            ], 404 );
        }

        return new WP_REST_Response( [
            'success' => true,
            'message' => 'API Key đã bị thu hồi.',
        ], 200 );
    }

    /**
     * Delete an API key permanently (hard delete).
     */
    public static function delete_key( $request ) {
        $id = sanitize_text_field( $request->get_param( 'id' ) );

        global $wpdb;
        $table = MonkeyPay_DB::api_keys_table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $deleted = $wpdb->delete(
            $table,
            [ 'id' => $id ],
            [ '%s' ]
        );

        if ( false === $deleted ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Lỗi xóa key. Vui lòng thử lại.',
            ], 500 );
        }

        if ( 0 === $deleted ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'API Key không tìm thấy.',
            ], 404 );
        }

        return new WP_REST_Response( [
            'success' => true,
            'message' => 'API Key đã bị xóa vĩnh viễn.',
        ], 200 );
    }
}
