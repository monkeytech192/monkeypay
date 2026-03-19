<?php
/**
 * MonkeyPay REST API — Local API Key Management
 *
 * Generates, stores, lists, and revokes API keys locally in WordPress.
 * Keys are stored hashed in wp_options; the full key is shown only once at creation.
 *
 * Key format: mkp_live_<32 hex chars> (40 chars total)
 *
 * @package MonkeyPay
 * @since   3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_REST_API_Keys {

    /** wp_options key for storing API keys array. */
    const OPTION_KEY = 'monkeypay_local_api_keys';

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
        register_rest_route( 'monkeypay/v1', '/api-keys/(?P<id>[a-zA-Z0-9]+)', [
            'methods'             => 'PUT',
            'callback'            => [ __CLASS__, 'update_key' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Revoke API key
        register_rest_route( 'monkeypay/v1', '/api-keys/(?P<id>[a-zA-Z0-9]+)/revoke', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'revoke_key' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    // ─── Storage Helpers ─────────────────────────────────

    /**
     * Get all stored API keys.
     *
     * @return array
     */
    private static function get_all_keys() {
        $keys = get_option( self::OPTION_KEY, [] );
        return is_array( $keys ) ? $keys : [];
    }

    /**
     * Save keys array to wp_options.
     *
     * @param array $keys Full keys array.
     */
    private static function save_all_keys( $keys ) {
        update_option( self::OPTION_KEY, $keys, false );
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
        // wp_check_password handles phpass comparison
        return wp_check_password( $key, $hash );
    }

    // ─── Public Validation (used by other endpoints) ────

    /**
     * Validate an API key from a request.
     * Checks the key against all active stored keys.
     * Updates last_used timestamp on match.
     *
     * @param  string $api_key Plain-text key from request.
     * @return array|false     Key record on success, false on failure.
     */
    public static function validate_api_key( $api_key ) {
        if ( empty( $api_key ) || strpos( $api_key, self::KEY_PREFIX ) !== 0 ) {
            return false;
        }

        $keys = self::get_all_keys();

        foreach ( $keys as &$key_record ) {
            if ( $key_record['status'] !== 'active' ) {
                continue;
            }

            if ( self::verify_key( $api_key, $key_record['key_hash'] ) ) {
                // Update last_used timestamp
                $key_record['last_used'] = current_time( 'c' );
                self::save_all_keys( $keys );
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
        $keys   = self::get_all_keys();
        $output = [];

        foreach ( $keys as $key ) {
            $output[] = [
                'id'         => $key['id'],
                'label'      => $key['label'],
                'key_prefix' => $key['key_prefix'],
                'status'     => $key['status'],
                'created_at' => $key['created_at'],
                'last_used'  => isset( $key['last_used'] ) ? $key['last_used'] : null,
            ];
        }

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $output,
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

        $key_record = [
            'id'         => uniqid( 'key_', true ),
            'label'      => $label,
            'key_hash'   => $key_hash,
            'key_prefix' => $key_prefix,
            'status'     => 'active',
            'created_at' => current_time( 'c' ),
            'last_used'  => null,
        ];

        $keys   = self::get_all_keys();
        $keys[] = $key_record;
        self::save_all_keys( $keys );

        // Return FULL key once (for user to copy)
        return new WP_REST_Response( [
            'success' => true,
            'data'    => [
                'id'         => $key_record['id'],
                'label'      => $key_record['label'],
                'api_key'    => $full_key,
                'key_prefix' => $key_prefix,
                'status'     => 'active',
                'created_at' => $key_record['created_at'],
            ],
        ], 201 );
    }

    /**
     * Update an API key label.
     */
    public static function update_key( $request ) {
        $id    = $request->get_param( 'id' );
        $body  = $request->get_json_params();
        $label = isset( $body['label'] ) ? sanitize_text_field( $body['label'] ) : '';

        if ( empty( $label ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Label không được để trống.',
            ], 400 );
        }

        $keys  = self::get_all_keys();
        $found = false;

        foreach ( $keys as &$key ) {
            if ( $key['id'] === $id ) {
                $key['label'] = $label;
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'API Key không tìm thấy.',
            ], 404 );
        }

        self::save_all_keys( $keys );

        return new WP_REST_Response( [
            'success' => true,
            'message' => 'Đã cập nhật label.',
        ], 200 );
    }

    /**
     * Revoke an API key (soft delete — status → revoked).
     */
    public static function revoke_key( $request ) {
        $id   = $request->get_param( 'id' );
        $keys = self::get_all_keys();
        $found = false;

        foreach ( $keys as &$key ) {
            if ( $key['id'] === $id ) {
                $key['status'] = 'revoked';
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'API Key không tìm thấy.',
            ], 404 );
        }

        self::save_all_keys( $keys );

        return new WP_REST_Response( [
            'success' => true,
            'message' => 'API Key đã bị thu hồi.',
        ], 200 );
    }
}
