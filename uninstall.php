<?php
/**
 * MonkeyPay Uninstall
 *
 * Fired when the plugin is uninstalled.
 * Cleans up all plugin options from the database.
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove plugin options
$options = [
    'monkeypay_api_url',
    'monkeypay_api_key',
    'monkeypay_webhook_secret',
    'monkeypay_enabled',
    'monkeypay_wc_enabled',
    'monkeypay_checkin_bridge',
    'monkeypay_session_status',
    'monkeypay_session_expired_at',
];

foreach ( $options as $option ) {
    delete_option( $option );
}

// Remove any monkeypay_tx_ mappings
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'monkeypay_tx_%' OR option_name LIKE 'monkeypay_wc_tx_%'"
);

// Remove transients
delete_transient( 'monkeypay_admin_notice' );
