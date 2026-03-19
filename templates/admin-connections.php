<?php
/**
 * MonkeyPay Connections Admin Page — Platform Grid v2
 *
 * Modern card-based UI for 9 connection platforms with smart toggles.
 *
 * @package MonkeyPay
 * @since   2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$connections_mgr = MonkeyPay_Connections::get_instance();
$connections     = $connections_mgr->get_connections();
$platform_meta   = MonkeyPay_Connections::get_platform_meta();
$events          = MonkeyPay_Connections::EVENTS;

// Index connections by platform for quick lookup
$conn_by_platform = [];
foreach ( $connections as $conn ) {
    $p = $conn['platform'] ?? '';
    if ( ! isset( $conn_by_platform[ $p ] ) ) {
        $conn_by_platform[ $p ] = [];
    }
    $conn_by_platform[ $p ][] = $conn;
}

// Platform SVG icons (official brand + custom designs)
$platform_icons = [
    'webhook'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M14.31 8l5.74 9.94M9.69 8h11.48M7.38 12l5.74-9.94M9.69 16L3.95 6.06M14.31 16H2.83M16.62 12l-5.74 9.94"/></svg>',
    'lark'          => '<svg viewBox="0 0 24 24" fill="none"><path d="M6.5 3C6.5 3 4 8 4 12.5C4 17 8 21 12 21C16 21 20 17 20 12.5L12 8.5L6.5 3Z" fill="currentColor" opacity="0.15"/><path d="M6.5 3L12 8.5L20 12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 12.5C4 17 8 21 12 21C16 21 20 17 20 12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
    'telegram'      => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 1 0 24 12.056A12.013 12.013 0 0 0 11.944 0ZM16.906 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472c-.18 1.898-.962 6.502-1.36 8.627c-.168.9-.499 1.201-.82 1.23c-.696.065-1.225-.46-1.9-.902c-1.056-.693-1.653-1.124-2.678-1.8c-1.185-.78-.417-1.21.258-1.91c.177-.184 3.247-2.977 3.307-3.23c.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345c-.48.33-.913.49-1.302.48c-.428-.008-1.252-.241-1.865-.44c-.752-.245-1.349-.374-1.297-.789c.027-.216.325-.437.893-.663c3.498-1.524 5.83-2.529 6.998-3.014c3.332-1.386 4.025-1.627 4.476-1.635Z"/></svg>',
    'google_sheets' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6z" opacity="0.9"/><path d="M8 12h3v2H8v-2zm5 0h3v2h-3v-2zm-5 4h3v2H8v-2zm5 4h3v-2h-3v2z"/></svg>',
    'slack'         => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/></svg>',
    'discord'       => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128c.125-.094.25-.192.373-.292a.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>',
    'mqtt'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="3"/><circle cx="5" cy="19" r="3"/><circle cx="19" cy="19" r="3"/><line x1="12" y1="8" x2="5" y2="16"/><line x1="12" y1="8" x2="19" y2="16"/><line x1="5" y1="19" x2="19" y2="19"/></svg>',
    'viber'         => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.398.002C9.473.028 5.141.473 3.22 2.253C1.692 3.78 1.132 5.963 1.072 8.662c-.06 2.7-.132 7.758 4.747 9.16h.004l-.003 2.089s-.032.845.525 1.017c.674.207 1.069-.433 1.713-1.123l1.2-1.347c3.284.276 5.807-.355 6.093-.447.66-.213 4.39-.693 4.998-5.651.628-5.117-.304-8.343-1.955-9.792C17.193 1.508 12.807-.019 11.398.002Z"/></svg>',
    'whatsapp'      => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>',
];
?>

<div class="wrap monkeypay-admin-wrap">
    <?php include MONKEYPAY_PLUGIN_DIR . 'templates/partials/global-header.php'; ?>

    <div class="monkeypay-admin-page">

        <!-- Page Header -->
        <div class="monkeypay-page-header">
            <div>
                <h2 class="monkeypay-page-title">
                    <svg viewBox="0 0 24 24" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;color:var(--mp-primary);fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;">
                        <path d="M15 7h3a5 5 0 0 1 0 10h-3M9 17H6a5 5 0 0 1 0-10h3"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    <?php esc_html_e( 'Kết Nối Nền Tảng', 'monkeypay' ); ?>
                </h2>
                <p class="monkeypay-page-desc"><?php esc_html_e( 'Bật/tắt và cấu hình các nền tảng nhận thông báo giao dịch realtime', 'monkeypay' ); ?></p>
            </div>
        </div>

        <!-- Platform Cards Grid -->
        <div class="mp-conn-grid" id="mp-conn-grid">
            <?php foreach ( $platform_meta as $slug => $meta ) :
                $platform_conns = $conn_by_platform[ $slug ] ?? [];
                $active_conn    = null;
                foreach ( $platform_conns as $c ) {
                    if ( ! empty( $c['enabled'] ) ) {
                        $active_conn = $c;
                        break;
                    }
                }
                if ( ! $active_conn && ! empty( $platform_conns ) ) {
                    $active_conn = $platform_conns[0];
                }
                $has_config = ! empty( $platform_conns );
                $is_active  = $active_conn && ! empty( $active_conn['enabled'] );
                $is_coming  = ! empty( $meta['coming_soon'] );
            ?>
            <div class="mp-conn-card<?php echo $is_active ? ' is-active' : ''; ?><?php echo $is_coming ? ' is-coming-soon' : ''; ?>"
                 data-platform="<?php echo esc_attr( $slug ); ?>"
                 data-conn-id="<?php echo esc_attr( $active_conn['id'] ?? '' ); ?>"
                 data-has-config="<?php echo $has_config ? '1' : '0'; ?>"
                 style="--platform-color: <?php echo esc_attr( $meta['color'] ); ?>;">

                <?php if ( $is_coming ) : ?>
                    <span class="mp-conn-badge-coming"><?php esc_html_e( 'Sắp ra mắt', 'monkeypay' ); ?></span>
                <?php endif; ?>

                <div class="mp-conn-card__header">
                    <div class="mp-conn-card__logo" aria-label="<?php echo esc_attr( $meta['label'] ); ?>">
                        <?php echo $platform_icons[ $slug ] ?? ''; ?>
                    </div>
                    <label class="mp-conn-toggle-wrap" onclick="event.stopPropagation();">
                        <input type="checkbox"
                               class="mp-conn-toggle-input"
                               data-platform="<?php echo esc_attr( $slug ); ?>"
                               data-conn-id="<?php echo esc_attr( $active_conn['id'] ?? '' ); ?>"
                               <?php checked( $is_active ); ?>
                               <?php disabled( $is_coming ); ?> />
                        <span class="mp-conn-toggle-slider"></span>
                    </label>
                </div>

                <div class="mp-conn-card__body">
                    <h3 class="mp-conn-card__title"><?php echo esc_html( $meta['label'] ); ?></h3>
                    <p class="mp-conn-card__desc"><?php echo esc_html( $meta['description'] ); ?></p>
                </div>

                <div class="mp-conn-card__footer">
                    <?php if ( $has_config && ! $is_coming ) : ?>
                        <span class="mp-conn-status <?php echo $is_active ? 'is-on' : 'is-off'; ?>">
                            <span class="mp-conn-status__dot"></span>
                            <?php echo $is_active ? esc_html__( 'Đang hoạt động', 'monkeypay' ) : esc_html__( 'Đã tắt', 'monkeypay' ); ?>
                        </span>
                    <?php elseif ( ! $is_coming ) : ?>
                        <span class="mp-conn-status is-empty"><?php esc_html_e( 'Chưa cấu hình', 'monkeypay' ); ?></span>
                    <?php else : ?>
                        <span class="mp-conn-status is-coming"><?php esc_html_e( 'Coming soon', 'monkeypay' ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- Modal: Platform Config -->
<div id="mp-platform-modal" class="mp-modal" aria-hidden="true">
    <div class="mp-modal__backdrop"></div>
    <div class="mp-modal__sheet" role="dialog" aria-modal="true">
        <div class="mp-modal__drag-handle"><span></span></div>

        <div class="mp-modal__header">
            <div class="mp-modal__platform-info">
                <div class="mp-modal__platform-icon" id="mp-modal-icon"></div>
                <div>
                    <h3 class="mp-modal__title" id="mp-modal-title"><?php esc_html_e( 'Cấu hình kết nối', 'monkeypay' ); ?></h3>
                    <p class="mp-modal__subtitle" id="mp-modal-subtitle"></p>
                </div>
            </div>
            <button type="button" class="mp-modal__close" id="mp-modal-close" aria-label="<?php esc_attr_e( 'Đóng', 'monkeypay' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="mp-platform-form" class="mp-modal__body">
            <input type="hidden" id="mp-pf-conn-id" value="">
            <input type="hidden" id="mp-pf-platform" value="">

            <div class="mp-form-group">
                <label class="mp-form-label" for="mp-pf-name"><?php esc_html_e( 'Tên kết nối', 'monkeypay' ); ?> <span class="mp-form-opt">(<?php esc_html_e( 'tuỳ chọn', 'monkeypay' ); ?>)</span></label>
                <input type="text" id="mp-pf-name" class="mp-form-input" placeholder="<?php esc_attr_e( 'Ví dụ: Nhóm quản lý', 'monkeypay' ); ?>">
            </div>

            <div class="mp-form-group">
                <label class="mp-form-label" for="mp-pf-url"><?php esc_html_e( 'URL Webhook', 'monkeypay' ); ?> <span class="mp-form-req">*</span></label>
                <input type="url" id="mp-pf-url" class="mp-form-input" placeholder="https://..." required>
                <p class="mp-form-hint" id="mp-pf-url-hint"></p>
            </div>

            <div class="mp-form-group">
                <label class="mp-form-label" for="mp-pf-secret"><?php esc_html_e( 'Secret Key (HMAC)', 'monkeypay' ); ?> <span class="mp-form-opt">(<?php esc_html_e( 'xác thực chữ ký', 'monkeypay' ); ?>)</span></label>
                <input type="text" id="mp-pf-secret" class="mp-form-input" placeholder="<?php esc_attr_e( 'Nhập secret key...', 'monkeypay' ); ?>">
            </div>

            <div class="mp-form-group">
                <label class="mp-form-label"><?php esc_html_e( 'Sự kiện theo dõi', 'monkeypay' ); ?></label>
                <div class="mp-form-pills">
                    <?php foreach ( $events as $key => $label ) : ?>
                    <label class="mp-pill">
                        <input type="checkbox" name="mp_pf_events[]" value="<?php echo esc_attr( $key ); ?>"
                               <?php echo $key === 'payment_received' ? 'checked' : ''; ?>>
                        <span class="mp-pill__label">
                            <?php if ( $key === 'payment_received' ) : ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mp-pill__icon"><polyline points="7 13 10 16 17 9"/><circle cx="12" cy="12" r="10"/></svg>
                            <?php else : ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mp-pill__icon"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                            <?php endif; ?>
                            <?php echo esc_html( $label ); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Card Builder trigger (Lark only) -->
            <div class="mp-form-group" id="mp-pf-card-builder-group" style="display:none;">
                <label class="mp-form-label"><?php esc_html_e( 'Mẫu thông báo (Card Template)', 'monkeypay' ); ?></label>
                <button type="button" class="mp-btn mp-btn--outline" id="mp-pf-open-card-builder" style="gap:6px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                    <?php esc_html_e( 'Tùy chỉnh Card', 'monkeypay' ); ?>
                </button>
                <p class="mp-form-hint"><?php esc_html_e( 'Kéo thả để thiết kế card thông báo Lark theo ý muốn', 'monkeypay' ); ?></p>
            </div>

            <div class="mp-modal__actions">
                <button type="button" class="mp-btn mp-btn--ghost" id="mp-pf-cancel"><?php esc_html_e( 'Hủy', 'monkeypay' ); ?></button>
                <div class="mp-modal__actions-right">
                    <button type="button" class="mp-btn mp-btn--outline-danger" id="mp-pf-delete" style="display:none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        <?php esc_html_e( 'Xóa', 'monkeypay' ); ?>
                    </button>
                    <button type="button" class="mp-btn mp-btn--outline" id="mp-pf-test" style="display:none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        <?php esc_html_e( 'Test', 'monkeypay' ); ?>
                    </button>
                    <button type="submit" class="mp-btn mp-btn--primary" id="mp-pf-save">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="20 6 9 17 4 12"/></svg>
                        <span id="mp-pf-save-text"><?php esc_html_e( 'Lưu kết nối', 'monkeypay' ); ?></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ═══ Modal: Card Template Builder ═══ -->
<div id="mp-card-builder-modal" class="mp-modal" aria-hidden="true">
    <div class="mp-modal__backdrop"></div>
    <div class="mp-modal__sheet mp-modal__sheet--wide" role="dialog" aria-modal="true">
        <div class="mp-modal__drag-handle"><span></span></div>

        <div class="mp-modal__header">
            <div class="mp-modal__platform-info">
                <div class="mp-modal__platform-icon" style="color:#3370ff;">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/><line x1="3" y1="9" x2="21" y2="9" stroke="currentColor" stroke-width="1.5"/><line x1="9" y1="21" x2="9" y2="9" stroke="currentColor" stroke-width="1.5"/></svg>
                </div>
                <div>
                    <h3 class="mp-modal__title"><?php esc_html_e( 'Card Builder — Lark', 'monkeypay' ); ?></h3>
                    <p class="mp-modal__subtitle"><?php esc_html_e( 'Kéo thả elements để thiết kế card thông báo', 'monkeypay' ); ?></p>
                </div>
            </div>
            <button type="button" class="mp-modal__close" id="mp-cb-close" aria-label="<?php esc_attr_e( 'Đóng', 'monkeypay' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="mp-cb-body">
            <!-- Left: Builder -->
            <div class="mp-cb-builder">
                <!-- Card Header config -->
                <div class="mp-cb-section">
                    <label class="mp-cb-section__label"><?php esc_html_e( 'Tiêu đề Card', 'monkeypay' ); ?></label>
                    <input type="text" id="mp-cb-header-title" class="mp-form-input" value="💰 Nhận tiền thành công" placeholder="Tiêu đề card...">
                    <div class="mp-cb-color-row">
                        <label class="mp-cb-section__label" style="margin-bottom:0"><?php esc_html_e( 'Màu', 'monkeypay' ); ?></label>
                        <div class="mp-cb-colors" id="mp-cb-colors">
                            <?php
                            $lark_colors = [
                                'blue'      => '#3370ff',
                                'green'     => '#34c724',
                                'orange'    => '#ff7d00',
                                'red'       => '#f54a45',
                                'purple'    => '#7c3aed',
                                'indigo'    => '#4f46e5',
                                'turquoise' => '#2dd4bf',
                            ];
                            foreach ( $lark_colors as $name => $hex ) :
                            ?>
                            <label class="mp-cb-color-swatch" data-color="<?php echo esc_attr( $name ); ?>" style="--swatch-color:<?php echo esc_attr( $hex ); ?>;" title="<?php echo esc_attr( ucfirst( $name ) ); ?>">
                                <input type="radio" name="mp_cb_color" value="<?php echo esc_attr( $name ); ?>" <?php echo $name === 'green' ? 'checked' : ''; ?>>
                                <span class="mp-cb-color-swatch__circle"></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Element Palette -->
                <div class="mp-cb-section">
                    <label class="mp-cb-section__label"><?php esc_html_e( 'Elements', 'monkeypay' ); ?></label>
                    <div class="mp-cb-palette" id="mp-cb-palette">
                        <button type="button" class="mp-cb-palette-item" data-type="text" draggable="true" title="Text block">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>
                            Text
                        </button>
                        <button type="button" class="mp-cb-palette-item" data-type="fields" draggable="true" title="2-column fields">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                            Fields
                        </button>
                        <button type="button" class="mp-cb-palette-item" data-type="hr" draggable="true" title="Divider line">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><line x1="2" y1="12" x2="22" y2="12"/></svg>
                            HR
                        </button>
                        <button type="button" class="mp-cb-palette-item" data-type="note" draggable="true" title="Small note text">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            Note
                        </button>
                        <button type="button" class="mp-cb-palette-item" data-type="url_button" draggable="true" title="URL action button">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            Button
                        </button>
                    </div>
                </div>

                <!-- Sortable Canvas -->
                <div class="mp-cb-section mp-cb-section--grow">
                    <label class="mp-cb-section__label"><?php esc_html_e( 'Canvas — Kéo thả để sắp xếp', 'monkeypay' ); ?></label>
                    <div class="mp-cb-canvas" id="mp-cb-canvas">
                        <div class="mp-cb-canvas__empty" id="mp-cb-empty-hint">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:32px;height:32px;opacity:.4"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            <p><?php esc_html_e( 'Bấm element ở trên hoặc kéo thả vào đây', 'monkeypay' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Variables reference -->
                <div class="mp-cb-section">
                    <label class="mp-cb-section__label"><?php esc_html_e( 'Biến có sẵn', 'monkeypay' ); ?></label>
                    <div class="mp-cb-vars" id="mp-cb-vars">
                        <?php foreach ( MonkeyPay_Lark_Formatter::get_template_variables() as $var => $desc ) : ?>
                        <span class="mp-cb-var-chip" data-var="<?php echo esc_attr( $var ); ?>" title="<?php echo esc_attr( $desc ); ?>"><?php echo esc_html( $var ); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Live Preview -->
            <div class="mp-cb-preview">
                <label class="mp-cb-section__label"><?php esc_html_e( 'Xem trước (Preview)', 'monkeypay' ); ?></label>
                <div class="mp-cb-preview-card" id="mp-cb-preview-card">
                    <!-- Rendered by JS -->
                </div>
            </div>
        </div>

        <div class="mp-modal__actions">
            <button type="button" class="mp-btn mp-btn--ghost" id="mp-cb-reset">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                <?php esc_html_e( 'Reset mặc định', 'monkeypay' ); ?>
            </button>
            <div class="mp-modal__actions-right">
                <button type="button" class="mp-btn mp-btn--ghost" id="mp-cb-cancel"><?php esc_html_e( 'Hủy', 'monkeypay' ); ?></button>
                <button type="button" class="mp-btn mp-btn--primary" id="mp-cb-apply">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php esc_html_e( 'Áp dụng', 'monkeypay' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Platform metadata for JS -->
<script>
    window.mpPlatformMeta   = <?php echo wp_json_encode( $platform_meta ); ?>;
    window.mpConnections    = <?php echo wp_json_encode( $connections ); ?>;
    window.mpTemplateVars   = <?php echo wp_json_encode( MonkeyPay_Lark_Formatter::get_template_variables() ); ?>;
    window.mpDefaultTemplate = <?php echo wp_json_encode( MonkeyPay_Lark_Formatter::get_default_template() ); ?>;
</script>
