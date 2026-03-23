<?php
/**
 * MonkeyPay Database Manager
 *
 * Handles custom database table creation and management
 * for storing transactions, settings, and connections.
 *
 * Schema v3.0:
 *  - monkeypay_transactions:      Unified transactions table (since v1.0)
 *  - monkeypay_settings:           Dedicated settings table (replaces wp_options)
 *  - monkeypay_connections:        Webhook connections table
 *  - monkeypay_gateways:           Local cache of payment gateways from server
 *  - monkeypay_merchant_profile:   Merchant info + plan details
 *  - monkeypay_integrations:       Expandable integrations config
 *  - monkeypay_api_keys:           API keys (replaces serialized wp_options)
 *
 * @package MonkeyPay
 * @since   3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_DB {

    /** @var string Table name (without prefix) */
    const TABLE_NAME = 'monkeypay_transactions';

    /** @var string Settings table name (without prefix) */
    const SETTINGS_TABLE = 'monkeypay_settings';

    /** @var string Connections table name (without prefix) */
    const CONNECTIONS_TABLE = 'monkeypay_connections';

    /** @var string Gateways table name (without prefix) */
    const GATEWAYS_TABLE = 'monkeypay_gateways';

    /** @var string Merchant profile table name (without prefix) */
    const MERCHANT_TABLE = 'monkeypay_merchant_profile';

    /** @var string Integrations table name (without prefix) */
    const INTEGRATIONS_TABLE = 'monkeypay_integrations';

    /** @var string API keys table name (without prefix) */
    const API_KEYS_TABLE = 'monkeypay_api_keys';

    /** @var string DB version option key (kept in wp_options for bootstrap) */
    const DB_VERSION_KEY = 'monkeypay_db_version';

    /** @var string Current DB schema version */
    const DB_VERSION = '3.0';

    /**
     * Get the full table name with WP prefix.
     *
     * @return string
     */
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Get the full settings table name.
     *
     * @return string
     */
    public static function settings_table() {
        global $wpdb;
        return $wpdb->prefix . self::SETTINGS_TABLE;
    }

    /**
     * Get the full connections table name.
     *
     * @return string
     */
    public static function connections_table() {
        global $wpdb;
        return $wpdb->prefix . self::CONNECTIONS_TABLE;
    }

    /**
     * Get the full gateways table name.
     *
     * @return string
     */
    public static function gateways_table() {
        global $wpdb;
        return $wpdb->prefix . self::GATEWAYS_TABLE;
    }

    /**
     * Get the full merchant profile table name.
     *
     * @return string
     */
    public static function merchant_table() {
        global $wpdb;
        return $wpdb->prefix . self::MERCHANT_TABLE;
    }

    /**
     * Get the full integrations table name.
     *
     * @return string
     */
    public static function integrations_table() {
        global $wpdb;
        return $wpdb->prefix . self::INTEGRATIONS_TABLE;
    }

    /**
     * Get the full API keys table name.
     *
     * @return string
     */
    public static function api_keys_table() {
        global $wpdb;
        return $wpdb->prefix . self::API_KEYS_TABLE;
    }

    /**
     * Create or update the transactions table.
     * Called on plugin activation and admin_init (version check).
     *
     * Uses dbDelta() which handles both CREATE and ALTER automatically.
     */
    public static function create_tables() {
        $installed_version = get_option( self::DB_VERSION_KEY, '0' );

        global $wpdb;
        $table   = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            bdsd_id VARCHAR(50) NOT NULL DEFAULT '',
            tx_id VARCHAR(100) NOT NULL DEFAULT '',
            amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            description TEXT,
            bank_name VARCHAR(100) NOT NULL DEFAULT '',
            account_no VARCHAR(50) NOT NULL DEFAULT '',
            is_credit TINYINT(1) NOT NULL DEFAULT 1,
            transaction_date DATETIME NOT NULL,
            raw_data LONGTEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            source VARCHAR(30) NOT NULL DEFAULT 'bdsd_webhook',
            status VARCHAR(20) NOT NULL DEFAULT 'raw',
            reference_type VARCHAR(30) DEFAULT NULL,
            reference_id VARCHAR(100) DEFAULT NULL,
            payment_note VARCHAR(255) DEFAULT NULL,
            matched_tx_id VARCHAR(100) DEFAULT NULL,
            confirmed_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_bdsd_id (bdsd_id),
            KEY idx_tx_id (tx_id),
            KEY idx_transaction_date (transaction_date),
            KEY idx_amount_date (amount, transaction_date),
            KEY idx_status (status),
            KEY idx_reference (reference_type, reference_id),
            KEY idx_source (source)
        ) {$charset};";

        // ── Settings table ──
        $settings_table = self::settings_table();
        $sql_settings = "CREATE TABLE {$settings_table} (
            setting_key VARCHAR(100) NOT NULL,
            setting_value LONGTEXT,
            autoload ENUM('yes','no') DEFAULT 'yes',
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (setting_key)
        ) {$charset};";

        // ── Connections table ──
        $connections_table = self::connections_table();
        $sql_connections = "CREATE TABLE {$connections_table} (
            id CHAR(36) NOT NULL,
            name VARCHAR(200) NOT NULL DEFAULT '',
            platform VARCHAR(50) NOT NULL DEFAULT 'webhook',
            webhook_url TEXT,
            secret_key VARCHAR(200) DEFAULT '',
            events TEXT,
            card_template LONGTEXT,
            card_template_debit LONGTEXT,
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_platform (platform),
            KEY idx_enabled (enabled)
        ) {$charset};";

        // ── Gateways table (local cache from server) ──
        $gateways_table = self::gateways_table();
        $sql_gateways = "CREATE TABLE {$gateways_table} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            server_id INT DEFAULT NULL,
            bank_code VARCHAR(50) NOT NULL DEFAULT '',
            bank_name VARCHAR(255) NOT NULL DEFAULT '',
            account_number VARCHAR(50) NOT NULL DEFAULT '',
            account_name VARCHAR(255) DEFAULT '',
            username VARCHAR(255) DEFAULT '',
            phone VARCHAR(50) DEFAULT '',
            note_prefix VARCHAR(20) DEFAULT 'MP',
            note_syntax VARCHAR(100) DEFAULT '{prefix}{random:6}',
            auto_amount TINYINT(1) DEFAULT 1,
            polling_interval INT DEFAULT 5,
            enabled TINYINT(1) DEFAULT 1,
            synced_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_bank_code (bank_code),
            KEY idx_server_id (server_id),
            KEY idx_enabled (enabled)
        ) {$charset};";

        // ── Merchant profile table ──
        $merchant_table = self::merchant_table();
        $sql_merchant = "CREATE TABLE {$merchant_table} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            server_merchant_id INT DEFAULT NULL,
            name VARCHAR(255) NOT NULL DEFAULT '',
            email VARCHAR(255) DEFAULT '',
            phone VARCHAR(50) DEFAULT '',
            site_url VARCHAR(512) DEFAULT '',
            plan_id VARCHAR(50) DEFAULT 'free',
            plan_name VARCHAR(100) DEFAULT 'Free',
            plan_price INT DEFAULT 0,
            request_limit INT DEFAULT 50,
            request_count INT DEFAULT 0,
            max_gateways INT DEFAULT 1,
            max_accounts_per_gw INT DEFAULT 1,
            plan_features TEXT,
            period_start DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'active',
            synced_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_server_id (server_merchant_id)
        ) {$charset};";

        // ── Integrations table ──
        $integrations_table = self::integrations_table();
        $sql_integrations = "CREATE TABLE {$integrations_table} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(100) NOT NULL,
            name VARCHAR(200) NOT NULL DEFAULT '',
            enabled TINYINT(1) DEFAULT 0,
            config LONGTEXT,
            status VARCHAR(50) DEFAULT 'inactive',
            last_error TEXT,
            synced_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_slug (slug),
            KEY idx_enabled (enabled)
        ) {$charset};";

        // ── API keys table (replaces serialized wp_options) ──
        $api_keys_table = self::api_keys_table();
        $sql_api_keys = "CREATE TABLE {$api_keys_table} (
            id VARCHAR(50) NOT NULL,
            label VARCHAR(100) DEFAULT 'API Key',
            key_hash VARCHAR(255) NOT NULL DEFAULT '',
            key_prefix VARCHAR(20) NOT NULL DEFAULT '',
            status VARCHAR(10) NOT NULL DEFAULT 'active',
            last_used_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            revoked_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_status (status)
        ) {$charset};";

        // Always run dbDelta to ensure tables exist (idempotent)
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        dbDelta( $sql_settings );
        dbDelta( $sql_connections );
        dbDelta( $sql_gateways );
        dbDelta( $sql_merchant );
        dbDelta( $sql_integrations );
        dbDelta( $sql_api_keys );

        // Migrate data from wp_options → dedicated tables
        self::migrate_from_options();

        // Migrate API keys from wp_options → api_keys table (v3.0)
        self::migrate_api_keys();

        // Seed default integrations (v3.0)
        self::seed_integrations();

        update_option( self::DB_VERSION_KEY, self::DB_VERSION );
    }

    // ─── INSERT METHODS ───────────────────────────────────────

    /**
     * Insert a BDSD transaction record (from webhook or bank history).
     *
     * @param array $data Transaction data from webhook
     * @return int|false Inserted row ID or false on failure
     */
    public static function insert_transaction( $data ) {
        global $wpdb;
        $table = self::table_name();

        // Parse transaction date
        $tx_date = $data['transaction_date'] ?? current_time( 'mysql' );
        if ( strpos( $tx_date, 'T' ) !== false ) {
            $tx_date = date( 'Y-m-d H:i:s', strtotime( $tx_date ) );
        }

        $result = $wpdb->insert(
            $table,
            [
                'bdsd_id'          => sanitize_text_field( $data['bdsd_id'] ?? '' ),
                'tx_id'            => sanitize_text_field( $data['tx_id'] ?? '' ),
                'amount'           => floatval( $data['amount'] ?? 0 ),
                'description'      => sanitize_text_field( $data['description'] ?? '' ),
                'bank_name'        => sanitize_text_field( $data['bank_name'] ?? '' ),
                'account_no'       => sanitize_text_field( $data['account_no'] ?? '' ),
                'is_credit'        => isset( $data['is_credit'] ) ? (int) $data['is_credit'] : 1,
                'transaction_date' => $tx_date,
                'raw_data'         => wp_json_encode( $data ),
                'source'           => sanitize_text_field( $data['source'] ?? 'bdsd_webhook' ),
                'status'           => sanitize_text_field( $data['status'] ?? 'raw' ),
                'payment_note'     => isset( $data['payment_note'] ) ? sanitize_text_field( $data['payment_note'] ) : null,
            ],
            [ '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( false === $result ) {
            MonkeyPay_Logger::webhook( 'DB insert failed: ' . $wpdb->last_error );
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Insert a payment transaction with reference mapping.
     * Replaces the old update_option('monkeypay_wc_tx_*') / update_option('monkeypay_tx_*') pattern.
     *
     * @param array $data {
     *     @type string $tx_id          MonkeyPay transaction ID
     *     @type float  $amount         Payment amount
     *     @type string $payment_note   Payment note / description
     *     @type string $reference_type 'wc_order' or 'checkin_invoice'
     *     @type string $reference_id   Order ID or Invoice ID
     *     @type string $source         'payment_create' (default)
     * }
     * @return int|false Inserted row ID or false on failure
     */
    public static function insert_payment( $data ) {
        global $wpdb;
        $table = self::table_name();

        $result = $wpdb->insert(
            $table,
            [
                'tx_id'            => sanitize_text_field( $data['tx_id'] ?? '' ),
                'amount'           => floatval( $data['amount'] ?? 0 ),
                'description'      => sanitize_text_field( $data['payment_note'] ?? $data['description'] ?? '' ),
                'payment_note'     => sanitize_text_field( $data['payment_note'] ?? '' ),
                'is_credit'        => 1, // Payment = incoming
                'transaction_date' => current_time( 'mysql' ),
                'source'           => sanitize_text_field( $data['source'] ?? 'payment_create' ),
                'status'           => 'pending',
                'reference_type'   => sanitize_text_field( $data['reference_type'] ?? '' ),
                'reference_id'     => sanitize_text_field( $data['reference_id'] ?? '' ),
            ],
            [ '%s', '%f', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( false === $result ) {
            MonkeyPay_Logger::webhook( 'DB insert_payment failed: ' . $wpdb->last_error );
            return false;
        }

        return $wpdb->insert_id;
    }

    // ─── LOOKUP METHODS ───────────────────────────────────────

    /**
     * Find a transaction by its MonkeyPay tx_id.
     *
     * @param string $tx_id MonkeyPay transaction ID
     * @return array|null Transaction row or null
     */
    public static function find_by_tx_id( $tx_id ) {
        global $wpdb;
        $table = self::table_name();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE tx_id = %s ORDER BY id DESC LIMIT 1",
                $tx_id
            ),
            ARRAY_A
        );
    }

    /**
     * Find transactions by reference (WC order or checkin invoice).
     *
     * @param string $type 'wc_order' or 'checkin_invoice'
     * @param string $id   Order ID or Invoice ID
     * @return array|null Transaction row or null
     */
    public static function find_by_reference( $type, $id ) {
        global $wpdb;
        $table = self::table_name();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE reference_type = %s AND reference_id = %s ORDER BY id DESC LIMIT 1",
                $type,
                $id
            ),
            ARRAY_A
        );
    }

    // ─── UPDATE METHODS ───────────────────────────────────────

    /**
     * Update transaction status.
     *
     * @param string $tx_id  MonkeyPay transaction ID
     * @param string $status New status (confirmed, expired, etc.)
     * @param array  $extra  Additional columns to update (e.g. matched_tx_id, confirmed_at)
     * @return int|false Number of rows updated or false on error
     */
    public static function update_status( $tx_id, $status, $extra = [] ) {
        global $wpdb;
        $table = self::table_name();

        $update = array_merge(
            [ 'status' => sanitize_text_field( $status ) ],
            $extra
        );

        $formats = [ '%s' ];
        foreach ( $extra as $value ) {
            $formats[] = is_int( $value ) ? '%d' : '%s';
        }

        return $wpdb->update(
            $table,
            $update,
            [ 'tx_id' => $tx_id ],
            $formats,
            [ '%s' ]
        );
    }

    // ─── QUERY METHODS ────────────────────────────────────────

    /**
     * Get transactions by date range.
     *
     * @param string $from Date from (Y-m-d or d/m/Y)
     * @param string $to   Date to (Y-m-d or d/m/Y)
     * @return array
     */
    public static function get_transactions( $from = '', $to = '' ) {
        global $wpdb;
        $table = self::table_name();

        // Normalize date formats (accept both Y-m-d and d/m/Y)
        $from = self::normalize_date( $from );
        $to   = self::normalize_date( $to );

        $where = '1=1';
        $params = [];

        if ( ! empty( $from ) ) {
            $where .= ' AND transaction_date >= %s';
            $params[] = $from . ' 00:00:00';
        }

        if ( ! empty( $to ) ) {
            $where .= ' AND transaction_date <= %s';
            $params[] = $to . ' 23:59:59';
        }

        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY transaction_date DESC";

        if ( ! empty( $params ) ) {
            $sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    // ─── HELPERS ──────────────────────────────────────────────

    /**
     * Normalize date string to Y-m-d format.
     *
     * @param string $date
     * @return string
     */
    private static function normalize_date( $date ) {
        if ( empty( $date ) ) {
            return '';
        }

        // If already Y-m-d format
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return $date;
        }

        // Convert d/m/Y → Y-m-d
        if ( preg_match( '#^(\d{2})/(\d{2})/(\d{4})$#', $date, $m ) ) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        // Try PHP parsing
        $ts = strtotime( $date );
        return $ts ? date( 'Y-m-d', $ts ) : '';
    }

    // ─── MIGRATION ────────────────────────────────────────────

    /**
     * Migrate data from wp_options into dedicated tables.
     *
     * Smart migration logic:
     *  - Settings: migrated once (guarded by monkeypay_migration_v2_done flag)
     *  - Connections: re-populated from wp_options if connections table is empty
     *    AND wp_options source still has data (safe re-activation support)
     *
     * card_template / card_template_debit are properly JSON-encoded:
     *  - If source data is array/object → wp_json_encode()
     *  - If source data is string (already JSON) → stored as-is
     *  - If source data is empty → null
     */
    public static function migrate_from_options() {
        global $wpdb;
        $now = current_time( 'mysql' );
        $settings_migrated = get_option( 'monkeypay_migration_v2_done' ) === '1';

        // ── 1. Settings migration (runs once) ──
        if ( ! $settings_migrated ) {
            $settings_keys = [
                'api_url', 'api_key', 'webhook_secret', 'admin_secret',
                'enabled', 'wc_enabled', 'checkin_bridge',
                'language', 'timezone', 'dark_mode',
                'auth_provider', 'session_status', 'session_expired_at',
                'webhook_synced',
            ];

            $settings_table = self::settings_table();
            foreach ( $settings_keys as $key ) {
                $full_key = 'monkeypay_' . $key;
                $value    = get_option( $full_key, null );

                if ( $value !== null ) {
                    $wpdb->replace(
                        $settings_table,
                        [
                            'setting_key'   => $key,
                            'setting_value' => is_array( $value ) ? wp_json_encode( $value ) : (string) $value,
                            'autoload'      => 'yes',
                            'updated_at'    => $now,
                        ],
                        [ '%s', '%s', '%s', '%s' ]
                    );
                }
            }

            update_option( 'monkeypay_migration_v2_done', '1' );
        }

        // ── 2. Connections migration (re-runs if table is empty) ──
        $connections_table = self::connections_table();

        // Check if connections table has any rows
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $existing_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$connections_table}" );

        // Only migrate if table is empty AND wp_options still has source data
        if ( $existing_count === 0 ) {
            $connections = get_option( 'monkeypay_webhook_connections', [] );

            if ( is_array( $connections ) && ! empty( $connections ) ) {
                foreach ( $connections as $conn ) {
                    // Encode card_template properly: handle both array and string formats
                    $card_tpl       = self::encode_card_template( $conn['card_template'] ?? null );
                    $card_tpl_debit = self::encode_card_template( $conn['card_template_debit'] ?? null );

                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    $wpdb->replace(
                        $connections_table,
                        [
                            'id'                  => $conn['id'] ?? wp_generate_uuid4(),
                            'name'                => $conn['name'] ?? '',
                            'platform'            => $conn['platform'] ?? 'webhook',
                            'webhook_url'         => $conn['webhook_url'] ?? '',
                            'secret_key'          => $conn['secret_key'] ?? '',
                            'events'              => wp_json_encode( $conn['events'] ?? [] ),
                            'card_template'       => $card_tpl,
                            'card_template_debit' => $card_tpl_debit,
                            'enabled'             => isset( $conn['enabled'] ) ? (int) $conn['enabled'] : 1,
                            'created_at'          => $conn['created_at'] ?? $now,
                            'updated_at'          => $conn['updated_at'] ?? $now,
                        ],
                        [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ]
                    );
                }

                if ( class_exists( 'MonkeyPay_Logger' ) ) {
                    MonkeyPay_Logger::webhook( sprintf(
                        'Connections migration: %d connection(s) populated from wp_options.',
                        count( $connections )
                    ) );
                }
            }
        }
    }

    /**
     * Encode card_template value for DB storage.
     *
     * Handles all source formats from wp_options:
     *  - PHP array/object → JSON-encode it
     *  - JSON string      → store as-is (no double-encode)
     *  - Empty/null        → null
     *
     * @param mixed $value Raw card_template value from wp_options
     * @return string|null JSON string or null
     */
    private static function encode_card_template( $value ) {
        if ( empty( $value ) ) {
            return null;
        }

        // Already a PHP array/object → encode to JSON
        if ( is_array( $value ) || is_object( $value ) ) {
            return wp_json_encode( $value );
        }

        // String: check if it's valid JSON already
        if ( is_string( $value ) ) {
            $decoded = json_decode( $value );
            if ( json_last_error() === JSON_ERROR_NONE && $decoded !== null ) {
                // Valid JSON string → store as-is
                return $value;
            }
            // Non-JSON string → return as-is (plain text template)
            return $value;
        }

        return null;
    }

    // ─── API KEYS MIGRATION (v3.0) ──────────────────────────

    /**
     * Migrate API keys from wp_options (serialized array) → monkeypay_api_keys table.
     * Runs once — only if api_keys table is empty.
     */
    private static function migrate_api_keys() {
        global $wpdb;
        $table = self::api_keys_table();

        // Only migrate if table is empty
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        if ( $count > 0 ) {
            return;
        }

        // Read from legacy wp_options storage
        $legacy_keys = get_option( 'monkeypay_local_api_keys', [] );
        if ( ! is_array( $legacy_keys ) || empty( $legacy_keys ) ) {
            return;
        }

        foreach ( $legacy_keys as $key_record ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->replace(
                $table,
                [
                    'id'           => $key_record['id'] ?? uniqid( 'key_', true ),
                    'label'        => $key_record['label'] ?? 'API Key',
                    'key_hash'     => $key_record['key_hash'] ?? '',
                    'key_prefix'   => $key_record['key_prefix'] ?? '',
                    'status'       => $key_record['status'] ?? 'active',
                    'last_used_at' => $key_record['last_used'] ?? null,
                    'created_at'   => $key_record['created_at'] ?? current_time( 'mysql' ),
                    'revoked_at'   => null,
                ],
                [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
            );
        }

        if ( class_exists( 'MonkeyPay_Logger' ) ) {
            MonkeyPay_Logger::webhook( sprintf(
                'API keys migration: %d key(s) moved from wp_options to api_keys table.',
                count( $legacy_keys )
            ) );
        }
    }

    // ─── SEED INTEGRATIONS (v3.0) ───────────────────────────

    /**
     * Seed default integration rows.
     * Runs once — only if integrations table is empty.
     * Migrates toggle values from settings if available.
     */
    private static function seed_integrations() {
        global $wpdb;
        $table = self::integrations_table();

        // Only seed if table is empty
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        if ( $count > 0 ) {
            return;
        }

        // Read legacy toggle values from settings
        $wc_enabled    = MonkeyPay_Settings::get( 'wc_enabled', '0' ) === '1' ? 1 : 0;
        $checkin_on    = MonkeyPay_Settings::get( 'checkin_bridge', '0' ) === '1' ? 1 : 0;

        $defaults = [
            [
                'slug'    => 'woocommerce',
                'name'    => 'WooCommerce',
                'enabled' => $wc_enabled,
                'config'  => wp_json_encode( [] ),
                'status'  => $wc_enabled ? 'active' : 'inactive',
            ],
            [
                'slug'    => 'checkin',
                'name'    => 'Checkin MKT192',
                'enabled' => $checkin_on,
                'config'  => wp_json_encode( [] ),
                'status'  => $checkin_on ? 'active' : 'inactive',
            ],
        ];

        foreach ( $defaults as $int ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->replace(
                $table,
                [
                    'slug'       => $int['slug'],
                    'name'       => $int['name'],
                    'enabled'    => $int['enabled'],
                    'config'     => $int['config'],
                    'status'     => $int['status'],
                    'created_at' => current_time( 'mysql' ),
                    'updated_at' => current_time( 'mysql' ),
                ],
                [ '%s', '%s', '%d', '%s', '%s', '%s', '%s' ]
            );
        }
    }
}
