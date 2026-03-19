<?php
/**
 * MonkeyPay WooCommerce Payment Gateway
 *
 * Extends WC_Payment_Gateway to provide bank transfer via MonkeyPay.
 * Shows QR code on the thank-you page for easy payment.
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
    return;
}

class MonkeyPay_WooCommerce_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'monkeypay';
        $this->icon               = MONKEYPAY_PLUGIN_URL . 'assets/images/monkeypay-icon.png';
        $this->has_fields         = false;
        $this->method_title       = __( 'MonkeyPay - Chuyển khoản ngân hàng', 'monkeypay' );
        $this->method_description = __( 'Thanh toán bằng chuyển khoản ngân hàng qua MonkeyPay. Xác nhận tự động.', 'monkeypay' );

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option( 'title', __( 'Chuyển khoản ngân hàng (Tự động)', 'monkeypay' ) );
        $this->description = $this->get_option( 'description', __( 'Quét mã QR hoặc chuyển khoản thủ công. Xác nhận tự động.', 'monkeypay' ) );
        $this->enabled     = $this->get_option( 'enabled', 'no' );

        // Save admin options
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

        // Thank you page
        add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'thankyou_page' ] );

        // Handle payment confirmation via hook
        add_action( 'monkeypay_payment_confirmed', [ $this, 'handle_payment_confirmed' ], 10, 2 );
    }

    /**
     * Gateway settings fields.
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __( 'Kích hoạt', 'monkeypay' ),
                'type'    => 'checkbox',
                'label'   => __( 'Bật cổng thanh toán MonkeyPay', 'monkeypay' ),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __( 'Tiêu đề', 'monkeypay' ),
                'type'        => 'text',
                'description' => __( 'Hiển thị tại trang thanh toán.', 'monkeypay' ),
                'default'     => __( 'Chuyển khoản ngân hàng (Tự động)', 'monkeypay' ),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __( 'Mô tả', 'monkeypay' ),
                'type'        => 'textarea',
                'description' => __( 'Hiển thị khi chọn phương thức thanh toán.', 'monkeypay' ),
                'default'     => __( 'Quét mã QR hoặc chuyển khoản thủ công. Xác nhận tự động trong vài giây.', 'monkeypay' ),
            ],
        ];
    }

    /**
     * Check if gateway is available.
     */
    public function is_available() {
        if ( 'yes' !== $this->enabled ) {
            return false;
        }

        // Check MonkeyPay is configured
        return monkeypay()->is_active();
    }

    /**
     * Process payment.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        // Create MonkeyPay transaction
        $api    = new MonkeyPay_API_Client();
        $result = $api->create_transaction(
            $order->get_total(),
            sprintf( __( 'Đơn hàng #%s', 'monkeypay' ), $order->get_order_number() )
        );

        if ( is_wp_error( $result ) ) {
            wc_add_notice( $result->get_error_message(), 'error' );
            return [ 'result' => 'failure' ];
        }

        // Store MonkeyPay transaction data
        $order->update_meta_data( '_monkeypay_tx_id', $result['tx_id'] );
        $order->update_meta_data( '_monkeypay_payment_note', $result['payment_note'] );
        $order->update_meta_data( '_monkeypay_qr_url', $result['qr_url'] );
        $order->update_meta_data( '_monkeypay_expires_at', $result['expires_at'] );
        $order->save();

        // Store reverse mapping: tx_id → order_id
        update_option( 'monkeypay_wc_tx_' . $result['tx_id'], $order_id );

        // Set order status to pending
        $order->update_status( 'on-hold', __( 'Đang chờ chuyển khoản qua MonkeyPay.', 'monkeypay' ) );

        // Empty cart
        WC()->cart->empty_cart();

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        ];
    }

    /**
     * Thank you page — display QR and bank info.
     *
     * @param int $order_id
     */
    public function thankyou_page( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order || $order->is_paid() ) {
            return;
        }

        $tx_id        = $order->get_meta( '_monkeypay_tx_id' );
        $payment_note = $order->get_meta( '_monkeypay_payment_note' );
        $qr_url       = $order->get_meta( '_monkeypay_qr_url' );
        $expires_at   = $order->get_meta( '_monkeypay_expires_at' );
        $amount       = $order->get_total();

        if ( empty( $tx_id ) ) {
            return;
        }

        // Enqueue payment assets
        wp_enqueue_style( 'monkeypay-payment', MONKEYPAY_PLUGIN_URL . 'assets/css/payment.css', [], MONKEYPAY_VERSION );
        wp_enqueue_script( 'monkeypay-payment', MONKEYPAY_PLUGIN_URL . 'assets/js/payment.js', [ 'jquery' ], MONKEYPAY_VERSION, true );
        wp_localize_script( 'monkeypay-payment', 'monkeypayPayment', [
            'restUrl'   => rest_url( 'monkeypay/v1/' ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'txId'      => $tx_id,
            'orderId'   => $order_id,
        ] );

        include MONKEYPAY_PLUGIN_DIR . 'templates/payment-page.php';
    }

    /**
     * Handle payment confirmation from webhook.
     *
     * @param string $tx_id
     * @param array  $data
     */
    public function handle_payment_confirmed( $tx_id, $data ) {
        $order_id = get_option( 'monkeypay_wc_tx_' . $tx_id, '' );
        if ( empty( $order_id ) ) {
            return; // Not a WooCommerce transaction
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Mark order as completed
        $order->payment_complete( $tx_id );
        $order->add_order_note(
            sprintf(
                __( 'Thanh toán MonkeyPay thành công. TX: %s', 'monkeypay' ),
                $tx_id
            )
        );

        // Cleanup mapping
        delete_option( 'monkeypay_wc_tx_' . $tx_id );

        error_log( "[MonkeyPay WC] Order #{$order_id} completed via tx {$tx_id}" );
    }
}
