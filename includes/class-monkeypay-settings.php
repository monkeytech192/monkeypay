<?php
/**
 * MonkeyPay Settings Helper
 *
 * Centralised settings read/write layer backed by the dedicated
 * {prefix}monkeypay_settings table.
 *
 * The table is ALWAYS created before any read/write via:
 *  - register_activation_hook  → create_tables()
 *  - admin_init                → create_tables()
 *
 * No fallback to wp_options — settings live exclusively
 * in the dedicated table after migration.
 *
 * @package MonkeyPay
 * @since   3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_Settings {

    /** @var string Prefix for legacy wp_options keys (used by cleanup only) */
    const PREFIX = 'monkeypay_';

    /** @var array<string, mixed> In-memory cache (per request) */
    private static $cache = [];

    /** @var bool Whether cache has been primed */
    private static $primed = false;

    /**
     * All known setting keys with their defaults and sanitise callbacks.
     *
     * @return array<string, array{default: mixed, sanitize: string}>
     */
    public static function schema() {
        return [
            // Core
            'api_url'            => [ 'default' => '', 'sanitize' => 'esc_url_raw' ],
            'api_key'            => [ 'default' => '', 'sanitize' => 'sanitize_text_field' ],
            'webhook_secret'     => [ 'default' => '', 'sanitize' => 'sanitize_text_field' ],
            'admin_secret'       => [ 'default' => '', 'sanitize' => 'sanitize_text_field' ],
            'enabled'            => [ 'default' => '1', 'sanitize' => 'sanitize_text_field' ],

            // Integrations
            'wc_enabled'         => [ 'default' => '0', 'sanitize' => 'sanitize_text_field' ],
            'checkin_bridge'     => [ 'default' => '0', 'sanitize' => 'sanitize_text_field' ],

            // UI / Preferences
            'language'           => [ 'default' => 'vi', 'sanitize' => 'sanitize_text_field' ],
            'timezone'           => [ 'default' => 'Asia/Ho_Chi_Minh', 'sanitize' => 'sanitize_text_field' ],
            'dark_mode'          => [ 'default' => '0', 'sanitize' => 'sanitize_text_field' ],

            // Auth
            'auth_provider'      => [ 'default' => 'password', 'sanitize' => 'sanitize_text_field' ],

            // Session
            'session_status'     => [ 'default' => '', 'sanitize' => 'sanitize_text_field' ],
            'session_expired_at' => [ 'default' => '', 'sanitize' => 'sanitize_text_field' ],

            // DB version (managed by MonkeyPay_DB, kept in wp_options)
            'db_version'         => [ 'default' => '0', 'sanitize' => 'sanitize_text_field' ],

            // Webhook sync
            'webhook_synced'     => [ 'default' => '0', 'sanitize' => 'sanitize_text_field' ],
        ];
    }

    // ─── TABLE HELPERS ────────────────────────────────────────

    /**
     * Get the settings table name.
     *
     * @return string
     */
    private static function table() {
        return MonkeyPay_DB::settings_table();
    }

    // ─── CORE API ──────────────────────────────────────────────

    /**
     * Prime the cache with all settings in a single DB call.
     */
    public static function prime() {
        if ( self::$primed ) {
            return;
        }

        global $wpdb;
        $table  = self::table();
        $schema = self::schema();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results( "SELECT setting_key, setting_value FROM {$table}", ARRAY_A );

        $db_values = [];
        if ( ! empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $db_values[ $row['setting_key'] ] = $row['setting_value'];
            }
        }

        foreach ( $schema as $key => $meta ) {
            self::$cache[ $key ] = $db_values[ $key ] ?? $meta['default'];
        }

        self::$primed = true;
    }

    /**
     * Get a setting value.
     *
     * @param string $key     Short key (without prefix), e.g. 'api_key'.
     * @param mixed  $default Override default if needed.
     * @return mixed
     */
    public static function get( $key, $default = null ) {
        // Return from cache if available
        if ( isset( self::$cache[ $key ] ) ) {
            return self::$cache[ $key ];
        }

        $schema   = self::schema();
        $fallback = $default ?? ( $schema[ $key ]['default'] ?? '' );

        global $wpdb;
        $table = self::table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $value = $wpdb->get_var( $wpdb->prepare(
            "SELECT setting_value FROM {$table} WHERE setting_key = %s",
            $key
        ) );

        $value = ( $value !== null ) ? $value : $fallback;

        self::$cache[ $key ] = $value;
        return $value;
    }

    /**
     * Set a setting value.
     *
     * @param string $key   Short key (without prefix).
     * @param mixed  $value Raw value — will be sanitised.
     * @return bool
     */
    public static function set( $key, $value ) {
        $schema = self::schema();

        // Sanitize if callback defined
        if ( isset( $schema[ $key ]['sanitize'] ) && function_exists( $schema[ $key ]['sanitize'] ) ) {
            $value = call_user_func( $schema[ $key ]['sanitize'], $value );
        }

        global $wpdb;
        $table = self::table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->replace(
            $table,
            [
                'setting_key'   => $key,
                'setting_value' => is_array( $value ) ? wp_json_encode( $value ) : (string) $value,
                'autoload'      => 'yes',
                'updated_at'    => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s' ]
        );

        self::$cache[ $key ] = $value;
        return $result !== false;
    }

    /**
     * Delete a setting.
     *
     * @param string $key Short key.
     * @return bool
     */
    public static function delete( $key ) {
        unset( self::$cache[ $key ] );

        global $wpdb;
        $table = self::table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->delete( $table, [ 'setting_key' => $key ], [ '%s' ] );
        return $result !== false;
    }

    /**
     * Bulk set multiple settings.
     *
     * @param array<string, mixed> $settings Key-value pairs.
     */
    public static function set_many( $settings ) {
        foreach ( $settings as $key => $value ) {
            self::set( $key, $value );
        }
    }

    /**
     * Get all settings as an associative array.
     *
     * @return array<string, mixed>
     */
    public static function get_all() {
        self::prime();
        return self::$cache;
    }

    /**
     * Returns all option keys (full, with prefix)
     * used by MonkeyPay. Useful for uninstall cleanup.
     *
     * @return string[]
     */
    public static function all_option_keys() {
        $keys = [];
        foreach ( array_keys( self::schema() ) as $key ) {
            $keys[] = self::PREFIX . $key;
        }
        return $keys;
    }

    /**
     * Clean up ALL MonkeyPay settings.
     * Called during uninstall.
     */
    public static function cleanup() {
        // Clean dedicated table
        global $wpdb;
        $table = self::table();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "TRUNCATE TABLE {$table}" );

        // Also clean legacy wp_options (from pre-v2.0 installs)
        foreach ( self::all_option_keys() as $full_key ) {
            delete_option( $full_key );
        }

        self::$cache  = [];
        self::$primed = false;
    }

    /**
     * Reset in-memory cache (useful for tests).
     */
    public static function reset_cache() {
        self::$cache  = [];
        self::$primed = false;
    }
}
