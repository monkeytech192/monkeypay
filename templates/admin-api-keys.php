<?php
/**
 * MonkeyPay Admin — API Keys Management
 *
 * Allows merchants to create, view, and revoke API keys.
 * Full key is shown only once on creation for security.
 *
 * Uses monkeypay-card pattern + mp-modal system for visual consistency
 * with Settings and Connections pages.
 *
 * @package MonkeyPay
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap monkeypay-admin-wrap">
    <?php include MONKEYPAY_PLUGIN_DIR . 'templates/partials/global-header.php'; ?>

    <div class="monkeypay-admin-page">

        <!-- ═══ API Keys Card ═══ -->
        <div class="monkeypay-card">
            <div class="monkeypay-card__header">
                <div class="monkeypay-card__header-left">
                    <div class="monkeypay-card__icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
                        </svg>
                    </div>
                    <div>
                        <h2><?php esc_html_e( 'Quản Lý API Keys', 'monkeypay' ); ?></h2>
                        <p class="monkeypay-card__subtitle"><?php esc_html_e( 'Tạo và quản lý các API key để xác thực với hệ thống MonkeyPay', 'monkeypay' ); ?></p>
                    </div>
                </div>
                <div class="monkeypay-card__header-right">
                    <button type="button" class="mp-btn mp-btn--primary" id="mp-create-key-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <?php esc_html_e( 'Tạo API Key', 'monkeypay' ); ?>
                    </button>
                </div>
            </div>

            <div class="mp-apikeys-info-banner">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 16v-4"></path>
                    <path d="M12 8h.01"></path>
                </svg>
                <span><?php printf(
                    /* translators: %s: max keys allowed */
                    esc_html__( 'Tối đa %s key hoạt động. Key bị thu hồi không thể khôi phục. Không chia sẻ key của bạn.', 'monkeypay' ),
                    '<strong>10</strong>'
                ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=monkeypay-api-docs' ) ); ?>"><?php esc_html_e( 'Xem API Docs →', 'monkeypay' ); ?></a></span>
            </div>

            <!-- Keys Table -->
            <div class="mp-apikeys-table-wrap">
                <div class="mp-apikeys-loading" id="mp-keys-loading">
                    <div class="mp-spinner"></div>
                    <span><?php esc_html_e( 'Đang tải...', 'monkeypay' ); ?></span>
                </div>

                <table class="mp-apikeys-table" id="mp-keys-table" style="display:none;">
                    <thead>
                        <tr>
                            <th class="mp-col-label"><?php esc_html_e( 'Nhãn', 'monkeypay' ); ?></th>
                            <th class="mp-col-key"><?php esc_html_e( 'API Key', 'monkeypay' ); ?></th>
                            <th class="mp-col-status"><?php esc_html_e( 'Trạng thái', 'monkeypay' ); ?></th>
                            <th class="mp-col-used"><?php esc_html_e( 'Lần dùng cuối', 'monkeypay' ); ?></th>
                            <th class="mp-col-created"><?php esc_html_e( 'Ngày tạo', 'monkeypay' ); ?></th>
                            <th class="mp-col-actions"><?php esc_html_e( 'Thao tác', 'monkeypay' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="mp-keys-tbody">
                    </tbody>
                </table>

                <div class="mp-apikeys-empty" id="mp-keys-empty" style="display:none;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--mp-text-muted)" stroke-width="1.5">
                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
                    </svg>
                    <p><?php esc_html_e( 'Chưa có API key nào. Hãy tạo key đầu tiên!', 'monkeypay' ); ?></p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ═══ Create Key Modal (mp-modal system) ═══ -->
<div id="mp-create-key-modal" class="mp-modal" aria-hidden="true">
    <div class="mp-modal__backdrop"></div>
    <div class="mp-modal__sheet" role="dialog" aria-modal="true">
        <div class="mp-modal__drag-handle"><span></span></div>

        <div class="mp-modal__header">
            <div class="mp-modal__platform-info">
                <div class="mp-modal__platform-icon" style="color: var(--mp-primary);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </div>
                <div>
                    <h3 class="mp-modal__title"><?php esc_html_e( 'Tạo API Key Mới', 'monkeypay' ); ?></h3>
                    <p class="mp-modal__subtitle"><?php esc_html_e( 'Đặt nhãn để phân biệt giữa các key', 'monkeypay' ); ?></p>
                </div>
            </div>
            <button type="button" class="mp-modal__close" id="mp-create-modal-close" aria-label="<?php esc_attr_e( 'Đóng', 'monkeypay' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="mp-modal__body">
            <div class="mp-form-group">
                <label class="mp-form-label" for="mp-new-key-label"><?php esc_html_e( 'Nhãn', 'monkeypay' ); ?> <span class="mp-form-opt">(<?php esc_html_e( 'tùy chọn', 'monkeypay' ); ?>)</span></label>
                <input type="text" id="mp-new-key-label" class="mp-form-input" placeholder="<?php esc_attr_e( 'Ví dụ: Production, Staging, Test...', 'monkeypay' ); ?>" maxlength="100">
                <p class="mp-form-hint"><?php esc_html_e( 'Đặt tên để dễ phân biệt giữa các key.', 'monkeypay' ); ?></p>
            </div>
        </div>

        <div class="mp-modal__actions" style="padding: 0 24px 24px;">
            <button type="button" class="mp-btn mp-btn--ghost mp-apikeys-modal-cancel"><?php esc_html_e( 'Hủy', 'monkeypay' ); ?></button>
            <div class="mp-modal__actions-right">
                <button type="button" class="mp-btn mp-btn--primary" id="mp-confirm-create-key">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <?php esc_html_e( 'Tạo Key', 'monkeypay' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Show New Key Modal ═══ -->
<div id="mp-show-key-modal" class="mp-modal" aria-hidden="true">
    <div class="mp-modal__backdrop"></div>
    <div class="mp-modal__sheet" role="dialog" aria-modal="true">
        <div class="mp-modal__drag-handle"><span></span></div>

        <div class="mp-modal__header">
            <div class="mp-modal__platform-info">
                <div class="mp-modal__platform-icon" style="color: var(--mp-success); background: rgba(16, 185, 129, 0.1);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div>
                    <h3 class="mp-modal__title" style="color: var(--mp-success);"><?php esc_html_e( 'API Key Đã Tạo!', 'monkeypay' ); ?></h3>
                    <p class="mp-modal__subtitle"><?php esc_html_e( 'Sao chép ngay — key sẽ không hiển thị lại', 'monkeypay' ); ?></p>
                </div>
            </div>
            <button type="button" class="mp-modal__close mp-apikeys-modal-cancel" aria-label="<?php esc_attr_e( 'Đóng', 'monkeypay' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="mp-modal__body">
            <div class="mp-new-key-display">
                <div class="mp-new-key-warning">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span><?php esc_html_e( 'Sao chép ngay! Key sẽ', 'monkeypay' ); ?> <strong><?php esc_html_e( 'không hiển thị lại', 'monkeypay' ); ?></strong>.</span>
                </div>
                <div class="mp-new-key-box">
                    <code id="mp-new-key-value" class="mp-new-key-code"></code>
                    <button type="button" class="mp-new-key-copy-btn" id="mp-copy-new-key" data-copy="">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <span><?php esc_html_e( 'Sao chép API Key', 'monkeypay' ); ?></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="mp-modal__actions" style="padding: 0 24px 24px;">
            <span></span>
            <div class="mp-modal__actions-right">
                <button type="button" class="mp-btn mp-btn--primary mp-apikeys-modal-cancel"><?php esc_html_e( 'Đã sao chép, đóng', 'monkeypay' ); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Revoke Confirmation Modal ═══ -->
<div id="mp-revoke-key-modal" class="mp-modal" aria-hidden="true">
    <div class="mp-modal__backdrop"></div>
    <div class="mp-modal__sheet" role="dialog" aria-modal="true">
        <div class="mp-modal__drag-handle"><span></span></div>

        <div class="mp-modal__header">
            <div class="mp-modal__platform-info">
                <div class="mp-modal__platform-icon" style="color: var(--mp-error); background: rgba(239, 68, 68, 0.1);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div>
                    <h3 class="mp-modal__title" style="color: var(--mp-error);"><?php esc_html_e( 'Thu Hồi API Key', 'monkeypay' ); ?></h3>
                    <p class="mp-modal__subtitle"><?php esc_html_e( 'Hành động này không thể hoàn tác', 'monkeypay' ); ?></p>
                </div>
            </div>
            <button type="button" class="mp-modal__close mp-apikeys-modal-cancel" aria-label="<?php esc_attr_e( 'Đóng', 'monkeypay' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="mp-modal__body">
            <p><?php esc_html_e( 'Bạn có chắc muốn thu hồi key', 'monkeypay' ); ?> <strong id="mp-revoke-key-label"></strong>?</p>
            <p class="mp-text-danger"><?php esc_html_e( 'Mọi request sử dụng key này sẽ bị từ chối ngay lập tức.', 'monkeypay' ); ?></p>
        </div>

        <div class="mp-modal__actions" style="padding: 0 24px 24px;">
            <button type="button" class="mp-btn mp-btn--ghost mp-apikeys-modal-cancel"><?php esc_html_e( 'Hủy', 'monkeypay' ); ?></button>
            <div class="mp-modal__actions-right">
                <button type="button" class="mp-btn mp-btn--danger" id="mp-confirm-revoke-key"><?php esc_html_e( 'Thu hồi', 'monkeypay' ); ?></button>
            </div>
        </div>
    </div>
</div>
