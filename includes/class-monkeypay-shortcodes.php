<?php
/**
 * MonkeyPay Shortcodes
 *
 * [monkeypay_payment] — Renders the payment page with QR code and auto-polling.
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_Shortcodes {

    /**
     * Register shortcodes.
     */
    public static function register() {
        add_shortcode( 'monkeypay_payment', [ __CLASS__, 'payment_page' ] );
    }

    /**
     * Render payment page shortcode.
     *
     * Usage: [monkeypay_payment tx_id="tx_abc123"]
     * Or via URL: ?monkeypay_tx=tx_abc123
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function payment_page( $atts ) {
        $atts = shortcode_atts( [
            'tx_id' => '',
        ], $atts, 'monkeypay_payment' );

        // Get tx_id from shortcode attr or URL param
        $tx_id = ! empty( $atts['tx_id'] )
            ? sanitize_text_field( $atts['tx_id'] )
            : sanitize_text_field( $_GET['monkeypay_tx'] ?? '' );

        if ( empty( $tx_id ) ) {
            return '<div class="monkeypay-error">' . esc_html__( 'Không tìm thấy giao dịch.', 'monkeypay' ) . '</div>';
        }

        ob_start();
        include MONKEYPAY_PLUGIN_DIR . 'templates/payment-page.php';
        return ob_get_clean();
    }
}
