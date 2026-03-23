<?php
/**
 * MonkeyPay Uninstall
 *
 * Fired when the plugin is uninstalled.
 * Cleans up all plugin options and custom tables from the database.
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Load settings helper for cleanup
require_once __DIR__ . '/includes/class-monkeypay-settings.php';
require_once __DIR__ . '/includes/class-monkeypay-db.php';

// ── 1. Remove all known plugin options ──
MonkeyPay_Settings::cleanup();

// ── 2. Remove any monkeypay_tx_ mappings (backward-compat: pre-4.3.0 used wp_options for tx mapping) ──
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'monkeypay_tx_%' OR option_name LIKE 'monkeypay_wc_tx_%'"
);

// ── 3. Remove connections option (legacy wp_options) ──
delete_option( 'monkeypay_webhook_connections' );
delete_option( 'monkeypay_local_api_keys' );

// ── 4. Remove migration flags ──
delete_option( 'monkeypay_migration_v2_done' );
delete_option( 'monkeypay_repair_card_tpl_done' );
delete_option( 'monkeypay_db_version' );

// ── 5. Remove transients ──
delete_transient( 'monkeypay_admin_notice' );

// ── 6. Drop ALL custom tables ──
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}monkeypay_api_keys" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}monkeypay_integrations" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}monkeypay_merchant_profile" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}monkeypay_gateways" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}monkeypay_connections" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}monkeypay_settings" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}monkeypay_transactions" );
