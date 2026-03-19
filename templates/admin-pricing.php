<?php
/**
 * MonkeyPay Pricing Page
 *
 * Beautiful pricing plans with monthly/yearly toggle.
 *
 * @package MonkeyPay
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap monkeypay-admin-wrap">
    <?php include MONKEYPAY_PLUGIN_DIR . 'templates/partials/global-header.php'; ?>

    <div class="monkeypay-admin-page">

        <!-- Page Header -->
        <div class="mp-pricing-header">
            <h2 class="mp-pricing-title">
                <?php esc_html_e( 'Chọn gói phù hợp cho doanh nghiệp bạn', 'monkeypay' ); ?>
            </h2>
            <p class="mp-pricing-subtitle">
                <?php esc_html_e( 'Bắt đầu miễn phí, nâng cấp khi cần. Huỷ bất cứ lúc nào.', 'monkeypay' ); ?>
            </p>

            <!-- Monthly / Yearly Toggle -->
            <div class="mp-pricing-toggle">
                <span class="mp-pricing-toggle-label mp-pricing-toggle-label--active" data-period="monthly">Hàng tháng</span>
                <button type="button" class="mp-pricing-toggle-switch" id="mp-pricing-period-toggle" aria-label="Toggle yearly pricing">
                    <span class="mp-pricing-toggle-knob"></span>
                </button>
                <span class="mp-pricing-toggle-label" data-period="yearly">
                    Hàng năm
                    <span class="mp-pricing-save-badge">Tiết kiệm 17%</span>
                </span>
            </div>
        </div>

        <!-- Plans Grid (populated by JS) -->
        <div class="mp-pricing-grid" id="mp-pricing-grid">
            <!-- Loading skeleton -->
            <div class="mp-pricing-loading">
                <div class="mp-pricing-skeleton"></div>
                <div class="mp-pricing-skeleton"></div>
                <div class="mp-pricing-skeleton"></div>
                <div class="mp-pricing-skeleton"></div>
            </div>
        </div>

        <!-- Bottom CTA -->
        <div class="mp-pricing-footer">
            <p>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;vertical-align:middle;color:var(--mp-success);">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <?php esc_html_e( 'Tất cả gói đều bao gồm hỗ trợ kỹ thuật qua Zalo/Email', 'monkeypay' ); ?>
            </p>
        </div>

    </div>
</div>
