<?php
/**
 * MonkeyPay Admin — API Documentation
 *
 * @package MonkeyPay
 * @since   3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get the base REST URL for the site
$rest_base = rest_url( 'monkeypay/v1/' );
?>

<div class="monkeypay-admin-wrap">
    <?php include __DIR__ . '/partials/global-header.php'; ?>

    <div class="mp-docs-layout">
        <!-- Sidebar Navigation -->
        <nav class="mp-docs-sidebar">
            <div class="mp-docs-sidebar__inner">
                <p class="mp-docs-nav__group"><?php esc_html_e( 'Bắt đầu', 'monkeypay' ); ?></p>
                <ul class="mp-docs-nav">
                    <li><a href="#doc-intro" class="mp-docs-nav__link mp-docs-nav__link--active"><?php esc_html_e( 'Giới thiệu', 'monkeypay' ); ?></a></li>
                    <li><a href="#doc-auth" class="mp-docs-nav__link"><?php esc_html_e( 'Xác thực', 'monkeypay' ); ?></a></li>
                </ul>

                <p class="mp-docs-nav__group"><?php esc_html_e( 'Endpoints', 'monkeypay' ); ?></p>
                <ul class="mp-docs-nav">
                    <li><a href="#doc-transactions" class="mp-docs-nav__link"><?php esc_html_e( 'Kiểm tra giao dịch', 'monkeypay' ); ?></a></li>
                    <li><a href="#doc-gateways" class="mp-docs-nav__link"><?php esc_html_e( 'Danh sách cổng thanh toán', 'monkeypay' ); ?></a></li>
                </ul>

                <p class="mp-docs-nav__group"><?php esc_html_e( 'Webhooks', 'monkeypay' ); ?></p>
                <ul class="mp-docs-nav">
                    <li><a href="#doc-webhooks" class="mp-docs-nav__link"><?php esc_html_e( 'Nhận Webhook', 'monkeypay' ); ?></a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <article class="mp-docs-content">

            <!-- Introduction -->
            <section id="doc-intro" class="mp-docs-section">
                <h2 class="mp-docs-section__title"><?php esc_html_e( 'Giới thiệu', 'monkeypay' ); ?></h2>
                <p><?php esc_html_e( 'MonkeyPay cung cấp REST API cho phép bạn tích hợp việc kiểm tra trạng thái thanh toán và lấy dữ liệu cổng thanh toán trực tiếp vào hệ thống hoặc ứng dụng tùy chỉnh của mình.', 'monkeypay' ); ?></p>
                <p><?php esc_html_e( 'Base URL cho mọi API endpoint trên website này là:', 'monkeypay' ); ?></p>
                <div class="mp-docs-code">
                    <code><?php echo esc_url( $rest_base ); ?></code>
                    <button class="mp-docs-code__copy" data-copy="<?php echo esc_url( $rest_base ); ?>" title="Copy">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                    </button>
                </div>
            </section>

            <!-- Authentication -->
            <section id="doc-auth" class="mp-docs-section">
                <h2 class="mp-docs-section__title"><?php esc_html_e( 'Xác thực (Authentication)', 'monkeypay' ); ?></h2>
                <p><?php esc_html_e( 'Để gửi yêu cầu đến API, bạn cần cung cấp API Key được tạo từ trang Quản lý API Keys. API Key có định dạng bắt đầu bằng', 'monkeypay' ); ?> <code class="mp-docs-inline-code">mkp_live_</code>.</p>
                <p><?php esc_html_e( 'Có 2 cách để gửi API Key:', 'monkeypay' ); ?></p>
                <ol class="mp-docs-list">
                    <li><strong>Header:</strong> <?php esc_html_e( 'Thêm header', 'monkeypay' ); ?> <code class="mp-docs-inline-code">X-Api-Key: mkp_live_...</code></li>
                    <li><strong>Query Parameter:</strong> <?php esc_html_e( 'Thêm tham số', 'monkeypay' ); ?> <code class="mp-docs-inline-code">?api_key=mkp_live_...</code></li>
                </ol>
                <div class="mp-docs-alert mp-docs-alert--info">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                    <p><?php esc_html_e( 'Khuyến nghị sử dụng Header để bảo mật tốt hơn, tránh việc API Key bị ghi lại trong access logs của server.', 'monkeypay' ); ?></p>
                </div>
                <div class="mp-docs-alert mp-docs-alert--warning">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <p><?php esc_html_e( 'Giữ API Key bí mật. Không chia sẻ key trong code frontend, repository công khai hoặc URL công khai.', 'monkeypay' ); ?></p>
                </div>
            </section>

            <hr class="mp-docs-divider">

            <!-- Transactions -->
            <section id="doc-transactions" class="mp-docs-section">
                <h2 class="mp-docs-section__title"><?php esc_html_e( 'Kiểm tra trạng thái giao dịch', 'monkeypay' ); ?></h2>
                <p><?php esc_html_e( 'Kiểm tra trạng thái của một giao dịch thanh toán cụ thể dựa trên ID giao dịch.', 'monkeypay' ); ?></p>

                <div class="mp-docs-endpoint">
                    <span class="mp-docs-method mp-docs-method--get">GET</span>
                    <code class="mp-docs-path">/transactions/{tx_id}</code>
                </div>

                <h3><?php esc_html_e( 'Path Parameters', 'monkeypay' ); ?></h3>
                <table class="mp-docs-table">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Type</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code class="mp-docs-inline-code">tx_id</code></td>
                            <td><em>string</em></td>
                            <td><?php esc_html_e( 'Mã giao dịch cần kiểm tra (Ví dụ: MKP_1623A8C)', 'monkeypay' ); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="mp-docs-examples">
                    <div class="mp-docs-example">
                        <h3><?php esc_html_e( 'Example Request', 'monkeypay' ); ?></h3>
                        <div class="mp-docs-codeblock">
                            <div class="mp-docs-codeblock__header">
                                <span class="mp-docs-codeblock__lang">bash</span>
                                <button class="mp-docs-code__copy" data-copy="curl -X GET &quot;<?php echo esc_url( $rest_base ); ?>transactions/MKP_1623A8C&quot; -H &quot;X-Api-Key: mkp_live_your_key_here&quot;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg></button>
                            </div>
<pre><code>curl -X GET "<?php echo esc_url( $rest_base ); ?>transactions/MKP_1623A8C" \
  -H "X-Api-Key: mkp_live_your_key_here"</code></pre>
                        </div>
                    </div>
                    <div class="mp-docs-example">
                        <h3><?php esc_html_e( 'Example Response', 'monkeypay' ); ?></h3>
                        <div class="mp-docs-codeblock">
                            <div class="mp-docs-codeblock__header">
                                <span class="mp-docs-codeblock__lang">json</span>
                            </div>
<pre><code>{
  "success": true,
  "data": {
    "id": "MKP_1623A8C",
    "amount": 500000,
    "status": "COMPLETED",
    "createdAt": "2023-10-01T10:00:00Z",
    "description": "Thanh toan don hang 123"
  }
}</code></pre>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="mp-docs-divider">

            <!-- Gateways -->
            <section id="doc-gateways" class="mp-docs-section">
                <h2 class="mp-docs-section__title"><?php esc_html_e( 'Danh sách cổng thanh toán', 'monkeypay' ); ?></h2>
                <p><?php esc_html_e( 'Lấy danh sách các cổng thanh toán đang hoạt động của cửa hàng để hiển thị trên ứng dụng hoặc website bên ngoài.', 'monkeypay' ); ?></p>

                <div class="mp-docs-endpoint">
                    <span class="mp-docs-method mp-docs-method--get">GET</span>
                    <code class="mp-docs-path">/merchant-gateways</code>
                </div>

                <div class="mp-docs-examples">
                    <div class="mp-docs-example">
                        <h3><?php esc_html_e( 'Example Request', 'monkeypay' ); ?></h3>
                        <div class="mp-docs-codeblock">
                            <div class="mp-docs-codeblock__header">
                                <span class="mp-docs-codeblock__lang">bash</span>
                                <button class="mp-docs-code__copy" data-copy="curl -X GET &quot;<?php echo esc_url( $rest_base ); ?>merchant-gateways&quot; -H &quot;X-Api-Key: mkp_live_your_key_here&quot;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg></button>
                            </div>
<pre><code>curl -X GET "<?php echo esc_url( $rest_base ); ?>merchant-gateways" \
  -H "X-Api-Key: mkp_live_your_key_here"</code></pre>
                        </div>
                    </div>
                    <div class="mp-docs-example">
                        <h3><?php esc_html_e( 'Example Response', 'monkeypay' ); ?></h3>
                        <div class="mp-docs-codeblock">
                            <div class="mp-docs-codeblock__header">
                                <span class="mp-docs-codeblock__lang">json</span>
                            </div>
<pre><code>{
  "success": true,
  "data": [
    {
      "id": "tpbank_123",
      "type": "tpbank",
      "bank_name": "Tien Phong Bank",
      "account_number": "0000123456",
      "account_name": "NGUYEN VAN A",
      "enabled": true
    }
  ]
}</code></pre>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="mp-docs-divider">

            <!-- Webhooks -->
            <section id="doc-webhooks" class="mp-docs-section">
                <h2 class="mp-docs-section__title"><?php esc_html_e( 'Nhận Webhooks', 'monkeypay' ); ?></h2>
                <p><?php esc_html_e( 'MonkeyPay có thể gửi thông báo (Webhook) đến website của bạn khi một giao dịch thay đổi trạng thái thành công. Để thiết lập xử lý logic sau khi thanh toán, bạn sử dụng filter hook trong WordPress:', 'monkeypay' ); ?></p>

                <div class="mp-docs-codeblock">
                    <div class="mp-docs-codeblock__header">
                        <span class="mp-docs-codeblock__lang">php</span>
                        <button class="mp-docs-code__copy" data-copy="add_action( 'monkeypay_payment_success', 'my_custom_payment_success_handler', 10, 1 );&#10;&#10;function my_custom_payment_success_handler( $transaction_data ) {&#10;    $tx_id  = $transaction_data['id'] ?? '';&#10;    $amount = $transaction_data['amount'] ?? 0;&#10;    $desc   = $transaction_data['description'] ?? '';&#10;&#10;    error_log( &quot;Thanh toan thanh cong: {$tx_id} - {$amount}VND&quot; );&#10;}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg></button>
                    </div>
<pre><code>add_action( 'monkeypay_payment_success', 'my_custom_payment_success_handler', 10, 1 );

function my_custom_payment_success_handler( $transaction_data ) {
    $tx_id  = $transaction_data['id'] ?? '';
    $amount = $transaction_data['amount'] ?? 0;
    $desc   = $transaction_data['description'] ?? '';

    // Xử lý logic tại đây: cập nhật đơn hàng, gửi email, vv...
    error_log( "Thanh toán thành công: {$tx_id} - {$amount}VNĐ" );
}</code></pre>
                </div>
            </section>

        </article>
    </div>
</div>
