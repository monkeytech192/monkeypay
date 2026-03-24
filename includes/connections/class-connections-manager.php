<?php
/**
 * MonkeyPay Webhook Connections Manager
 *
 * Manages outbound webhook connections to external platforms (Lark, Slack, Telegram, custom).
 * When a payment event occurs, dispatches notifications to all active connections.
 *
 * All CRUD operations use the dedicated {prefix}monkeypay_connections table.
 * The table is guaranteed to exist via activate() + admin_init hooks.
 *
 * @package MonkeyPay
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_Connections {

    /** @var MonkeyPay_Connections|null */
    private static $instance = null;

    /** Supported platforms */
    const PLATFORMS = [
        'webhook'       => 'Webhook',
        'lark'          => 'Lark / Feishu',
        'telegram'      => 'Telegram',
        'google_sheets' => 'Google Sheets',
        'slack'         => 'Slack',
        'discord'       => 'Discord',
        'mqtt'          => 'MQTT',
        'viber'         => 'Viber',
        'whatsapp'      => 'WhatsApp',
    ];

    /**
     * Platform metadata for UI rendering.
     *
     * @return array
     */
    public static function get_platform_meta() {
        return [
            'webhook' => [
                'label'       => 'Webhook',
                'description' => 'Nhận thông báo qua HTTP callback',
                'color'       => '#06b6d4',
                'coming_soon' => false,
            ],
            'lark' => [
                'label'       => 'Lark / Feishu',
                'description' => 'Tích hợp với Lark / Feishu',
                'color'       => '#3370ff',
                'coming_soon' => false,
            ],
            'telegram' => [
                'label'       => 'Telegram',
                'description' => 'Gửi tin nhắn tới bot hoặc group',
                'color'       => '#26A5E4',
                'coming_soon' => false,
            ],
            'google_sheets' => [
                'label'       => 'Google Sheets',
                'description' => 'Ghi dữ liệu trực tiếp vào bảng tính',
                'color'       => '#0F9D58',
                'coming_soon' => false,
            ],
            'slack' => [
                'label'       => 'Slack',
                'description' => 'Đẩy thông báo tới workspace Slack',
                'color'       => '#4A154B',
                'coming_soon' => false,
            ],
            'discord' => [
                'label'       => 'Discord',
                'description' => 'Gửi thông báo tới kênh Discord',
                'color'       => '#5865F2',
                'coming_soon' => true,
            ],
            'mqtt' => [
                'label'       => 'MQTT',
                'description' => 'Nhận dữ liệu giao dịch trên thiết bị IoT',
                'color'       => '#7B61FF',
                'coming_soon' => true,
            ],
            'viber' => [
                'label'       => 'Viber',
                'description' => 'Gửi tin nhắn qua Viber Bot',
                'color'       => '#7360F2',
                'coming_soon' => true,
            ],
            'whatsapp' => [
                'label'       => 'WhatsApp',
                'description' => 'Thông báo qua WhatsApp Business API',
                'color'       => '#25D366',
                'coming_soon' => true,
            ],
        ];
    }

    /** Supported events */
    const EVENTS = [
        'payment_received'  => 'Nhận tiền',
        'payment_sent'      => 'Chuyển tiền',
    ];

    /**
     * Get singleton instance.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the connections table name (shortcut).
     *
     * @return string
     */
    private function table() {
        return MonkeyPay_DB::connections_table();
    }

    /**
     * Unserialize a DB row into connection array format.
     *
     * @param array $row DB row (ARRAY_A)
     * @return array
     */
    private function row_to_connection( $row ) {
        return [
            'id'                  => $row['id'],
            'name'                => $row['name'],
            'platform'            => $row['platform'],
            'webhook_url'         => $row['webhook_url'],
            'secret_key'          => $row['secret_key'],
            'events'              => json_decode( $row['events'] ?? '[]', true ) ?: [],
            'card_template'       => ! empty( $row['card_template'] )
                                        ? json_decode( $row['card_template'], true )
                                        : null,
            'card_template_debit' => ! empty( $row['card_template_debit'] )
                                        ? json_decode( $row['card_template_debit'], true )
                                        : null,
            'enabled'             => (bool) $row['enabled'],
            'created_at'          => $row['created_at'],
            'updated_at'          => $row['updated_at'],
        ];
    }

    /**
     * Get all connections.
     *
     * @return array
     */
    public function get_connections() {
        global $wpdb;
        $table = $this->table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A );

        if ( empty( $rows ) ) {
            return [];
        }

        return array_map( [ $this, 'row_to_connection' ], $rows );
    }

    /**
     * Get a single connection by ID.
     *
     * @param string $id
     * @return array|null
     */
    public function get_connection( $id ) {
        global $wpdb;
        $table = $this->table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %s",
            $id
        ), ARRAY_A );

        return $row ? $this->row_to_connection( $row ) : null;
    }

    /**
     * Add a new connection.
     *
     * @param array $data Connection data
     * @return array The created connection
     */
    public function add_connection( $data ) {
        $connection = [
            'id'                  => wp_generate_uuid4(),
            'name'                => sanitize_text_field( $data['name'] ?? '' ),
            'platform'            => in_array( $data['platform'] ?? '', array_keys( self::PLATFORMS ), true )
                                       ? $data['platform'] : 'custom',
            'webhook_url'         => esc_url_raw( $data['webhook_url'] ?? '' ),
            'secret_key'          => sanitize_text_field( $data['secret_key'] ?? '' ),
            'events'              => $this->sanitize_events( $data['events'] ?? [] ),
            'card_template'       => $data['card_template'] ?? null,
            'card_template_debit' => $data['card_template_debit'] ?? null,
            'enabled'             => (bool) ( $data['enabled'] ?? true ),
            'created_at'          => current_time( 'mysql' ),
            'updated_at'          => current_time( 'mysql' ),
        ];

        global $wpdb;
        $table = $this->table();

        // Serialize JSON fields for DB storage
        $card_tpl_db       = is_array( $connection['card_template'] )
                                ? wp_json_encode( $connection['card_template'] )
                                : $connection['card_template'];
        $card_tpl_debit_db = is_array( $connection['card_template_debit'] )
                                ? wp_json_encode( $connection['card_template_debit'] )
                                : $connection['card_template_debit'];

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->insert(
            $table,
            [
                'id'                  => $connection['id'],
                'name'                => $connection['name'],
                'platform'            => $connection['platform'],
                'webhook_url'         => $connection['webhook_url'],
                'secret_key'          => $connection['secret_key'],
                'events'              => wp_json_encode( $connection['events'] ),
                'card_template'       => $card_tpl_db,
                'card_template_debit' => $card_tpl_debit_db,
                'enabled'             => (int) $connection['enabled'],
                'created_at'          => $connection['created_at'],
                'updated_at'          => $connection['updated_at'],
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ]
        );

        return $connection;
    }

    /**
     * Update an existing connection.
     *
     * @param string $id   Connection ID
     * @param array  $data Updated data
     * @return array|null  Updated connection or null
     */
    public function update_connection( $id, $data ) {
        global $wpdb;
        $table = $this->table();

        // Build update columns
        $update  = [ 'updated_at' => current_time( 'mysql' ) ];
        $formats = [ '%s' ];

        if ( isset( $data['name'] ) )        { $update['name']       = sanitize_text_field( $data['name'] ); $formats[] = '%s'; }
        if ( isset( $data['platform'] ) )    { $update['platform']   = in_array( $data['platform'], array_keys( self::PLATFORMS ), true ) ? $data['platform'] : 'webhook'; $formats[] = '%s'; }
        if ( isset( $data['webhook_url'] ) ) { $update['webhook_url'] = esc_url_raw( $data['webhook_url'] ); $formats[] = '%s'; }
        if ( isset( $data['secret_key'] ) )  { $update['secret_key'] = sanitize_text_field( $data['secret_key'] ); $formats[] = '%s'; }
        if ( isset( $data['events'] ) )      { $update['events']     = wp_json_encode( $this->sanitize_events( $data['events'] ) ); $formats[] = '%s'; }
        if ( array_key_exists( 'card_template', $data ) )       { $update['card_template']       = is_array( $data['card_template'] ) ? wp_json_encode( $data['card_template'] ) : $data['card_template']; $formats[] = '%s'; }
        if ( array_key_exists( 'card_template_debit', $data ) ) { $update['card_template_debit'] = is_array( $data['card_template_debit'] ) ? wp_json_encode( $data['card_template_debit'] ) : $data['card_template_debit']; $formats[] = '%s'; }
        if ( isset( $data['enabled'] ) )     { $update['enabled']    = (int) (bool) $data['enabled']; $formats[] = '%d'; }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->update( $table, $update, [ 'id' => $id ], $formats, [ '%s' ] );

        if ( $result === false || $result === 0 ) {
            // Check if row existed
            $exists = $this->get_connection( $id );
            return $exists ?: null;
        }

        return $this->get_connection( $id );
    }

    /**
     * Delete a connection.
     *
     * @param string $id Connection ID
     * @return bool
     */
    public function delete_connection( $id ) {
        global $wpdb;
        $table = $this->table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->delete( $table, [ 'id' => $id ], [ '%s' ] );
        return $result !== false && $result > 0;
    }

    /**
     * Dispatch an event to all active connections that subscribe to it.
     *
     * @param string $event Event name (e.g., 'payment_received')
     * @param array  $data  Event data (amount, description, bank info, etc.)
     * @return array Results per connection
     */
    public function dispatch_event( $event, $data ) {
        $connections = $this->get_connections();
        $results     = [];

        foreach ( $connections as $conn ) {
            // Skip disabled or non-matching
            if ( ! $conn['enabled'] || ! in_array( $event, $conn['events'], true ) ) {
                continue;
            }

            $result = $this->send_webhook( $conn, $event, $data );
            $results[ $conn['id'] ] = $result;

            MonkeyPay_Logger::lark( sprintf(
                'Dispatched %s to "%s" (%s): %s',
                $event,
                $conn['name'] ?: $conn['id'],
                $conn['platform'],
                is_wp_error( $result ) ? $result->get_error_message() : 'OK'
            ), [
                'connection_id' => $conn['id'],
                'platform'      => $conn['platform'],
                'event'         => $event,
                'result'        => is_wp_error( $result ) ? $result->get_error_message() : $result,
            ] );
        }

        return $results;
    }

    /**
     * Send webhook to a single connection.
     *
     * @param array  $conn  Connection config
     * @param string $event Event name
     * @param array  $data  Event data
     * @return array|WP_Error
     */
    private function send_webhook( $conn, $event, $data ) {
        if ( empty( $conn['webhook_url'] ) ) {
            return new WP_Error( 'missing_url', 'Webhook URL is empty' );
        }

        $platform = $conn['platform'] ?? 'custom';

        // Format payload based on platform
        switch ( $platform ) {
            case 'lark':
                $payload = $this->format_lark_payload( $event, $data, $conn );
                break;

            default: // custom
                $payload = $this->format_custom_payload( $event, $data );
                break;
        }

        // Build request headers
        $headers = [ 'Content-Type' => 'application/json' ];

        // HMAC signature if secret configured
        $body_json = wp_json_encode( $payload );
        if ( ! empty( $conn['secret_key'] ) ) {
            $signature = hash_hmac( 'sha256', $body_json, $conn['secret_key'] );
            $headers['X-MonkeyPay-Signature'] = $signature;
        }

        $response = wp_remote_post( $conn['webhook_url'], [
            'timeout' => 10,
            'headers' => $headers,
            'body'    => $body_json,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return [
            'status'  => $code < 400 ? 'ok' : 'error',
            'code'    => $code,
            'body'    => $body,
        ];
    }

    /**
     * Format payload for Lark webhook bot.
     */
    private function format_lark_payload( $event, $data, $conn = null ) {
        $formatter      = new MonkeyPay_Lark_Formatter();
        $template       = $conn['card_template'] ?? null;
        $template_debit = $conn['card_template_debit'] ?? null;
        return $formatter->format( $event, $data, $template, $template_debit );
    }

    /**
     * Format payload for custom webhook.
     */
    private function format_custom_payload( $event, $data ) {
        return [
            'event'     => $event,
            'timestamp' => current_time( 'c' ),
            'data'      => $data,
        ];
    }

    /**
     * Send a test event to a connection.
     *
     * @param string $id Connection ID
     * @return array|WP_Error
     */
    public function send_test( $id ) {
        $conn = $this->get_connection( $id );
        if ( ! $conn ) {
            return new WP_Error( 'not_found', 'Connection not found' );
        }

        $test_data = [
            'tx_id'        => 'test_' . wp_generate_password( 8, false ),
            'amount'       => 100000,
            'payment_note' => 'MONKEYPAY_TEST',
            'description'  => 'Giao dịch test từ MonkeyPay',
            'bank_name'    => 'MB Bank',
            'account_no'   => '0123456789',
            'matched_at'   => current_time( 'mysql' ),
        ];

        return $this->send_webhook( $conn, 'payment_received', $test_data );
    }

    /**
     * Sanitize events array.
     */
    private function sanitize_events( $events ) {
        if ( ! is_array( $events ) ) {
            return [ 'payment_received' ];
        }

        return array_values( array_intersect( $events, array_keys( self::EVENTS ) ) );
    }
}
