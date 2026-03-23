<?php
/**
 * MonkeyPay Payment Gateways Page
 *
 * Configure bank payment gateways (MB Bank, etc.)
 * Each gateway stores: bank credentials for payment processing
 *
 * @package MonkeyPay
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$api_url = MonkeyPay_Settings::get('api_url', MONKEYPAY_API_URL);
$api_url = ! empty( $api_url ) ? $api_url : MONKEYPAY_API_URL;
$api_key = MonkeyPay_Settings::get('api_key', '');

// Available bank definitions (extend as needed)
$available_banks = [
    'mbbank' => [
        'name'  => 'MB Bank',
        'color' => '#1e40af',
        'bin'   => '970422',
        'logo'  => 'https://api.vietqr.io/img/MB.png',
    ],
];
?>

<div class="wrap monkeypay-admin-wrap">
    <?php include MONKEYPAY_PLUGIN_DIR . 'templates/partials/global-header.php'; ?>

    <div class="monkeypay-admin-page">

        <div class="monkeypay-page-header">
            <div>
                <h2 class="monkeypay-page-title">
                    <svg viewBox="0 0 24 24" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;color:var(--mp-primary);fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    <?php esc_html_e('Cổng Thanh Toán', 'monkeypay'); ?>
                </h2>
                <p class="monkeypay-page-desc"><?php esc_html_e('Cấu hình ngân hàng nhận thanh toán. Thông tin sẽ được đồng bộ tới các plugin tích hợp.', 'monkeypay'); ?></p>
            </div>
        </div>

        <?php if (empty($api_url) || empty($api_key)) : ?>
        <div class="monkeypay-info-box" style="border-left-color: var(--mp-warning);">
            <strong><?php esc_html_e('Chưa cấu hình', 'monkeypay'); ?>:</strong>
            <?php
            printf(
                esc_html__('Vui lòng cấu hình %1$sAPI URL%2$s và %1$sAPI Key%2$s trong trang %3$sCài Đặt%4$s trước.', 'monkeypay'),
                '<strong>', '</strong>',
                '<a href="' . admin_url('admin.php?page=monkeypay-settings') . '">', '</a>'
            );
            ?>
        </div>
        <?php else : ?>

        <!-- Gateway Cards Grid -->
        <div class="monkeypay-gateways-grid" id="monkeypay-gateways-grid">
            <?php foreach ($available_banks as $code => $bank) : ?>
            <div class="monkeypay-gateway-card" data-bank-code="<?php echo esc_attr($code); ?>">
                <div class="monkeypay-gateway-header">
                    <div class="monkeypay-gateway-logo">
                        <img src="<?php echo esc_attr($bank['logo']); ?>" alt="<?php echo esc_attr($bank['name']); ?>" />
                    </div>
                    <div class="monkeypay-gateway-info">
                        <h3 class="monkeypay-gateway-name"><?php echo esc_html($bank['name']); ?></h3>
                        <span class="monkeypay-badge monkeypay-badge-gray monkeypay-gateway-status">
                            <?php esc_html_e('Chưa cấu hình', 'monkeypay'); ?>
                        </span>
                    </div>
                    <button type="button" class="monkeypay-btn monkeypay-btn--sm monkeypay-gateway-toggle-btn">
                        <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                        <?php esc_html_e('Cấu hình', 'monkeypay'); ?>
                    </button>
                </div>

                <!-- Expandable Form -->
                <div class="monkeypay-gateway-form" style="display: none;">
                    <!-- Hướng dẫn chia sẻ biến động số dư -->
                    <div class="monkeypay-info-box" style="border-left-color: var(--mp-primary); margin-bottom: 16px;">
                        <h4 style="margin: 0 0 8px; font-size: 14px; font-weight: 600; color: var(--mp-text-primary, #1a1a2e); display: flex; align-items: center; gap: 6px;">
                            <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;color:var(--mp-primary);"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <?php esc_html_e('Hướng dẫn thiết lập', 'monkeypay'); ?>
                        </h4>
                        <ol style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.8; color: var(--mp-text-secondary, #4b5563);">
                            <li><?php esc_html_e('Mở app MB Bank → Vào tài khoản nguồn → Cài đặt', 'monkeypay'); ?></li>
                            <li><?php esc_html_e('Chọn "Chia sẻ biến động số dư"', 'monkeypay'); ?></li>
                            <li>
                                <?php esc_html_e('Thêm số tài khoản nhận chia sẻ:', 'monkeypay'); ?>
                                <code style="background:rgba(59,130,246,.1);padding:2px 8px;border-radius:4px;font-weight:700;font-size:14px;color:var(--mp-primary);user-select:all;">0962794917</code>
                            </li>
                            <li><?php esc_html_e('Nhập số tài khoản và tên chủ tài khoản của bạn vào form bên dưới', 'monkeypay'); ?></li>
                            <li><?php esc_html_e('Bấm "Lưu cổng thanh toán" → Hoàn tất!', 'monkeypay'); ?></li>
                        </ol>
                    </div>

                    <div class="monkeypay-field">
                        <label><?php esc_html_e('Số tài khoản', 'monkeypay'); ?> <span style="color:var(--mp-error);">*</span></label>
                        <input type="text" name="account_number" placeholder="<?php esc_attr_e('Ví dụ: 0962794917', 'monkeypay'); ?>" />
                    </div>
                    <div class="monkeypay-field">
                        <label><?php esc_html_e('Tên chủ tài khoản', 'monkeypay'); ?></label>
                        <input type="text" name="account_name" placeholder="<?php esc_attr_e('Ví dụ: HO LE MINH TUAN', 'monkeypay'); ?>" style="text-transform:uppercase;" />
                    </div>

                    <!-- Gateway Config Fields -->
                    <div style="border-top: 1px solid var(--mp-border, #e5e7eb); margin-top: 16px; padding-top: 16px;">
                        <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 600; color: var(--mp-text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">
                            <?php esc_html_e('Cấu hình thanh toán', 'monkeypay'); ?>
                        </h4>

                        <!-- Auto Amount Toggle (switch style) -->
                        <div class="monkeypay-toggle">
                            <div class="monkeypay-toggle__info" style="flex:1;">
                                <span class="monkeypay-toggle__label"><?php esc_html_e('Fill tiền tự động', 'monkeypay'); ?></span>
                                <span class="monkeypay-toggle__desc"><?php esc_html_e('Bật: QR có sẵn số tiền, khớp ghi chú + số tiền. Tắt: khách tự nhập, chỉ khớp ghi chú.', 'monkeypay'); ?></span>
                            </div>
                            <label class="monkeypay-switch">
                                <input type="checkbox" name="auto_amount" value="1" checked />
                                <span class="monkeypay-switch__slider"></span>
                            </label>
                        </div>

                        <!-- Note Prefix -->
                        <div class="monkeypay-field" style="margin-top:12px;">
                            <label><?php esc_html_e('Prefix ghi chú', 'monkeypay'); ?></label>
                            <input type="text" name="note_prefix" value="MP" maxlength="20" placeholder="MP" style="text-transform:uppercase;" />
                            <small style="color:var(--mp-text-secondary, #6b7280);font-size:12px;"><?php esc_html_e('Ký tự đầu nội dung chuyển khoản. VD: MKT, MP, SHOP...', 'monkeypay'); ?></small>
                        </div>

                        <!-- Note Syntax (expanded variables) -->
                        <div class="monkeypay-field" style="margin-top:12px;">
                            <label><?php esc_html_e('Cú pháp ghi chú', 'monkeypay'); ?></label>
                            <input type="text" name="note_syntax" value="{prefix}{random:6}" placeholder="{prefix}{random:6}" />
                            <div style="margin-top:8px;padding:10px 12px;background:var(--mp-bg, #f8fafc);border:1px solid var(--mp-border-light, #f1f5f9);border-radius:8px;font-size:12px;line-height:1.8;">
                                <div style="font-weight:600;color:var(--mp-text, #1a1a2e);margin-bottom:4px;"><?php esc_html_e('Biến hỗ trợ:', 'monkeypay'); ?></div>
                                <div style="color:var(--mp-text-secondary, #6b7280);">
                                    <code style="background:rgba(6,182,212,.08);padding:1px 6px;border-radius:4px;font-size:11px;color:var(--mp-primary);">{prefix}</code> — <?php esc_html_e('Prefix bên trên', 'monkeypay'); ?><br>
                                    <code style="background:rgba(6,182,212,.08);padding:1px 6px;border-radius:4px;font-size:11px;color:var(--mp-primary);">{random:N}</code> — <?php esc_html_e('N ký tự ngẫu nhiên (chữ + số)', 'monkeypay'); ?><br>
                                    <code style="background:rgba(6,182,212,.08);padding:1px 6px;border-radius:4px;font-size:11px;color:var(--mp-primary);">{invoice_id}</code> — <?php esc_html_e('Mã hóa đơn từ hệ thống', 'monkeypay'); ?><br>
                                    <code style="background:rgba(6,182,212,.08);padding:1px 6px;border-radius:4px;font-size:11px;color:var(--mp-primary);">{date}</code> — <?php esc_html_e('Ngày hiện tại (ddmmyy)', 'monkeypay'); ?><br>
                                    <code style="background:rgba(6,182,212,.08);padding:1px 6px;border-radius:4px;font-size:11px;color:var(--mp-primary);">{timestamp}</code> — <?php esc_html_e('Unix timestamp', 'monkeypay'); ?><br>
                                    <code style="background:rgba(6,182,212,.08);padding:1px 6px;border-radius:4px;font-size:11px;color:var(--mp-primary);">{customer}</code> — <?php esc_html_e('Tên khách (bỏ dấu)', 'monkeypay'); ?><br>
                                    <code style="background:rgba(6,182,212,.08);padding:1px 6px;border-radius:4px;font-size:11px;color:var(--mp-primary);">{amount}</code> — <?php esc_html_e('Số tiền giao dịch', 'monkeypay'); ?>
                                </div>
                                <div style="margin-top:6px;padding-top:6px;border-top:1px dashed var(--mp-border-light, #e2e8f0);font-family:monospace;color:var(--mp-text, #1a1a2e);">
                                    <span style="color:var(--mp-text-muted, #94a3b8);font-family:inherit;">VD:</span>
                                    <code>{prefix}{random:6}</code> → <strong>MKT1A2B3C</strong> &nbsp;|&nbsp;
                                    <code>{prefix}{invoice_id}</code> → <strong>MKTINV001</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Polling Interval -->
                        <div class="monkeypay-field" style="margin-top:12px;">
                            <label><?php esc_html_e('Thời gian poll (giây)', 'monkeypay'); ?></label>
                            <input type="number" name="polling_interval" value="5" min="3" max="30" step="1" />
                            <small style="color:var(--mp-text-secondary, #6b7280);font-size:12px;"><?php esc_html_e('Kiểm tra thanh toán mỗi N giây. Mặc định: 5', 'monkeypay'); ?></small>
                        </div>
                    </div>
                    <div class="monkeypay-gateway-form-actions">
                        <button type="button" class="monkeypay-btn monkeypay-btn--primary monkeypay-gateway-save-btn">
                            <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            <?php esc_html_e('Lưu cổng thanh toán', 'monkeypay'); ?>
                        </button>
                        <button type="button" class="monkeypay-btn monkeypay-gateway-delete-btn" style="display: none;">
                            <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            <?php esc_html_e('Xoá', 'monkeypay'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

    </div>
</div>
