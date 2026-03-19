<?php

/**
 * Plugin Name: MonkeyPay - Cổng Thanh Toán Tự Động
 * Plugin URI:  https://monkeytech192.vn/monkeypay
 * Description: Cổng thanh toán chuyển khoản ngân hàng tự động. Tích hợp MB Bank, WooCommerce, và hệ sinh thái Monkey.
 * Version:     3.2.0
 * Author:      Monkey Tech 192
 * Author URI:  https://monkeytech192.vn/
 * Text Domain: monkeypay
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (! defined('ABSPATH')) {
    exit;
}

// ═══════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════

define('MONKEYPAY_VERSION', '3.2.0');
define('MONKEYPAY_PLUGIN_FILE', __FILE__);
define('MONKEYPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MONKEYPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MONKEYPAY_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('MONKEYPAY_API_URL', 'https://monkeypay-server-iutko24vhq-as.a.run.app');
define('MONKEYPAY_UPDATE_API', 'https://monkeytech192.vn/plugin-updates/monkeypay/check-for-updates.json');

// ═══════════════════════════════════════════════════════════════
// BOOTSTRAP
// ═══════════════════════════════════════════════════════════════

require_once MONKEYPAY_PLUGIN_DIR . 'includes/class-monkeypay.php';

/**
 * Returns the main MonkeyPay instance.
 *
 * @return MonkeyPay
 */
function monkeypay()
{
    return MonkeyPay::get_instance();
}

// Initialize
monkeypay();
