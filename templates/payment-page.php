<?php
/**
 * MonkeyPay Payment Page Template
 *
 * Displays QR code, bank info, payment note, and auto-polling status.
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Variables can come from shortcode ($tx_id) or WooCommerce ($tx_id, $qr_url, etc.)
$tx_id = isset( $tx_id ) ? $tx_id : '';
?>

<div class="monkeypay-payment" id="monkeypay-payment" data-tx-id="<?php echo esc_attr( $tx_id ); ?>">

    <!-- Payment Card -->
    <div class="monkeypay-payment__card">

        <!-- Header -->
        <div class="monkeypay-payment__header">
            <span class="monkeypay-payment__logo">🐒</span>
            <h2 class="monkeypay-payment__title"><?php esc_html_e( 'Thanh toán chuyển khoản', 'monkeypay' ); ?></h2>
            <p class="monkeypay-payment__subtitle"><?php esc_html_e( 'Quét mã QR hoặc chuyển khoản thủ công', 'monkeypay' ); ?></p>
        </div>

        <!-- QR Code -->
        <div class="monkeypay-payment__qr" id="monkeypay-qr-wrap">
            <img id="monkeypay-qr-img" src="" alt="QR Code" class="monkeypay-payment__qr-img" />
            <div class="monkeypay-payment__qr-loading" id="monkeypay-qr-loading">
                <div class="monkeypay-spinner"></div>
                <p><?php esc_html_e( 'Đang tạo mã QR...', 'monkeypay' ); ?></p>
            </div>
        </div>

        <!-- Bank Info -->
        <div class="monkeypay-payment__info" id="monkeypay-bank-info">
            <div class="monkeypay-payment__row">
                <span class="monkeypay-payment__label"><?php esc_html_e( 'Ngân hàng', 'monkeypay' ); ?></span>
                <span class="monkeypay-payment__value" id="monkeypay-bank-name">MB Bank</span>
            </div>
            <div class="monkeypay-payment__row">
                <span class="monkeypay-payment__label"><?php esc_html_e( 'Số tài khoản', 'monkeypay' ); ?></span>
                <span class="monkeypay-payment__value monkeypay-payment__copyable" id="monkeypay-account-number" data-copy="">
                    —
                    <button type="button" class="monkeypay-btn-copy" title="Copy">📋</button>
                </span>
            </div>
            <div class="monkeypay-payment__row">
                <span class="monkeypay-payment__label"><?php esc_html_e( 'Chủ tài khoản', 'monkeypay' ); ?></span>
                <span class="monkeypay-payment__value" id="monkeypay-account-name">—</span>
            </div>
            <div class="monkeypay-payment__row monkeypay-payment__row--highlight">
                <span class="monkeypay-payment__label"><?php esc_html_e( 'Số tiền', 'monkeypay' ); ?></span>
                <span class="monkeypay-payment__value monkeypay-payment__amount" id="monkeypay-amount">—</span>
            </div>
            <div class="monkeypay-payment__row monkeypay-payment__row--highlight">
                <span class="monkeypay-payment__label"><?php esc_html_e( 'Nội dung CK', 'monkeypay' ); ?></span>
                <span class="monkeypay-payment__value monkeypay-payment__copyable" id="monkeypay-payment-note" data-copy="">
                    —
                    <button type="button" class="monkeypay-btn-copy" title="Copy">📋</button>
                </span>
            </div>
        </div>

        <!-- Status -->
        <div class="monkeypay-payment__status" id="monkeypay-payment-status">
            <div class="monkeypay-payment__polling" id="monkeypay-polling">
                <div class="monkeypay-pulse"></div>
                <span><?php esc_html_e( 'Đang chờ thanh toán...', 'monkeypay' ); ?></span>
            </div>
            <div class="monkeypay-payment__success" id="monkeypay-success" style="display:none;">
                <div class="monkeypay-checkmark">
                    <svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="25" fill="none"/><path fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>
                </div>
                <h3><?php esc_html_e( 'Thanh toán thành công!', 'monkeypay' ); ?></h3>
                <p id="monkeypay-success-msg"><?php esc_html_e( 'Giao dịch đã được xác nhận.', 'monkeypay' ); ?></p>
            </div>
            <div class="monkeypay-payment__expired" id="monkeypay-expired" style="display:none;">
                <span>⏰</span>
                <h3><?php esc_html_e( 'Giao dịch hết hạn', 'monkeypay' ); ?></h3>
                <p><?php esc_html_e( 'Vui lòng tạo giao dịch mới.', 'monkeypay' ); ?></p>
            </div>
        </div>

        <!-- Timer -->
        <div class="monkeypay-payment__timer" id="monkeypay-timer">
            <span><?php esc_html_e( 'Hết hạn sau:', 'monkeypay' ); ?></span>
            <span class="monkeypay-payment__countdown" id="monkeypay-countdown">--:--</span>
        </div>

    </div>

</div>
