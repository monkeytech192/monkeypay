<?php
/**
 * MonkeyPay Lark Formatter
 *
 * Formats payment event data into Lark Interactive Card messages.
 * Supports both hardcoded default templates and user-defined card templates
 * from the drag-drop card builder.
 *
 * @package MonkeyPay
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_Lark_Formatter {

    /**
     * Available template variables with descriptions.
     *
     * @return array
     */
    public static function get_template_variables() {
        return [
            '{amount}'       => 'Số tiền (đã format)',
            '{amount_raw}'   => 'Số tiền (số nguyên)',
            '{bank_name}'    => 'Tên ngân hàng',
            '{account_no}'   => 'Số tài khoản',
            '{payment_note}' => 'Nội dung chuyển khoản',
            '{description}'  => 'Mô tả giao dịch',
            '{tx_id}'        => 'Mã giao dịch nội bộ',
            '{invoice_id}'   => 'Mã hóa đơn',
            '{matched_at}'   => 'Thời gian khớp lệnh',
            '{bank_description}' => 'Nội dung gốc từ ngân hàng',
        ];
    }

    /**
     * Default template for payment_received event.
     *
     * @return array
     */
    public static function get_default_template() {
        return [
            'header_title'    => '💰 Nhận tiền thành công',
            'header_color'    => 'green',
            'elements'        => [
                [
                    'type'    => 'text',
                    'content' => '**Số tiền: +{amount} VNĐ**',
                ],
                [
                    'type' => 'hr',
                ],
                [
                    'type'   => 'fields',
                    'fields' => [
                        [ 'label' => 'Ngân hàng',    'value' => '{bank_name}' ],
                        [ 'label' => 'Số TK',        'value' => '{account_no}' ],
                        [ 'label' => 'Nội dung CK',  'value' => '{payment_note}' ],
                        [ 'label' => 'Thời gian',    'value' => '{matched_at}' ],
                    ],
                ],
                [
                    'type'    => 'note',
                    'content' => 'TX: {tx_id}',
                ],
            ],
        ];
    }

    /**
     * Format event data into Lark webhook payload.
     *
     * @param string     $event    Event name
     * @param array      $data     Event data
     * @param array|null $template Optional custom template from card builder
     * @return array Lark webhook payload
     */
    public function format( $event, $data, $template = null ) {
        // Prepare variable map
        $vars = $this->build_variable_map( $data );

        // Use custom template or fallback to defaults
        if ( ! empty( $template ) && ! empty( $template['elements'] ) ) {
            return $this->format_from_template( $template, $vars );
        }

        // Legacy hardcoded format
        switch ( $event ) {
            case 'payment_received':
                return $this->format_payment_received( $data );

            case 'payment_sent':
                return $this->format_payment_sent( $data );

            default:
                return $this->format_generic( $event, $data );
        }
    }

    /**
     * Build variable replacement map from event data.
     *
     * @param array $data
     * @return array
     */
    private function build_variable_map( $data ) {
        $amount_raw = floatval( $data['amount'] ?? 0 );
        $amount     = number_format( $amount_raw, 0, '.', ',' );
        $note       = $data['payment_note'] ?? $data['description'] ?? '';
        $matched_at = $data['matched_at'] ?? current_time( 'd/m/Y H:i:s' );

        // Format matched_at if in MySQL format
        if ( strpos( $matched_at, '-' ) !== false ) {
            $matched_at = date( 'd/m/Y H:i:s', strtotime( $matched_at ) );
        }

        return [
            '{amount}'           => $amount,
            '{amount_raw}'       => strval( $amount_raw ),
            '{bank_name}'        => $data['bank_name'] ?? '',
            '{account_no}'       => $data['account_no'] ?? '',
            '{payment_note}'     => $note,
            '{description}'      => $data['description'] ?? '',
            '{tx_id}'            => $data['tx_id'] ?? '',
            '{invoice_id}'       => $data['invoice_id'] ?? '',
            '{matched_at}'       => $matched_at,
            '{bank_description}' => $data['bank_description'] ?? '',
        ];
    }

    /**
     * Format Lark card from user-defined template.
     *
     * @param array $template Template config from card builder
     * @param array $vars     Variable replacement map
     * @return array Lark payload
     */
    private function format_from_template( $template, $vars ) {
        $header_title = $this->replace_vars( $template['header_title'] ?? '🔔 Thông báo MonkeyPay', $vars );
        $header_color = $template['header_color'] ?? 'blue';

        $elements = [];

        foreach ( $template['elements'] as $el ) {
            $type = $el['type'] ?? '';

            switch ( $type ) {
                case 'text':
                    $content = $this->replace_vars( $el['content'] ?? '', $vars );
                    if ( ! empty( $content ) ) {
                        $elements[] = [
                            'tag'  => 'div',
                            'text' => [
                                'tag'     => 'lark_md',
                                'content' => $content,
                            ],
                        ];
                    }
                    break;

                case 'hr':
                    $elements[] = [ 'tag' => 'hr' ];
                    break;

                case 'fields':
                    $fields = [];
                    foreach ( $el['fields'] ?? [] as $field ) {
                        $label = $this->replace_vars( $field['label'] ?? '', $vars );
                        $value = $this->replace_vars( $field['value'] ?? '', $vars );

                        // Skip field if value is empty (auto-hide)
                        if ( empty( trim( $value ) ) ) {
                            continue;
                        }

                        $fields[] = [
                            'is_short' => true,
                            'text'     => [
                                'tag'     => 'lark_md',
                                'content' => "**{$label}:**\n{$value}",
                            ],
                        ];
                    }

                    if ( ! empty( $fields ) ) {
                        $elements[] = [
                            'tag'    => 'div',
                            'fields' => array_values( $fields ),
                        ];
                    }
                    break;

                case 'note':
                    $content = $this->replace_vars( $el['content'] ?? '', $vars );
                    if ( ! empty( $content ) ) {
                        $elements[] = [
                            'tag'      => 'note',
                            'elements' => [
                                [
                                    'tag'     => 'plain_text',
                                    'content' => $content,
                                ],
                            ],
                        ];
                    }
                    break;

                case 'url_button':
                    $text = $this->replace_vars( $el['text'] ?? 'Xem chi tiết', $vars );
                    $url  = $this->replace_vars( $el['url'] ?? '', $vars );
                    if ( ! empty( $url ) ) {
                        $elements[] = [
                            'tag'     => 'action',
                            'actions' => [
                                [
                                    'tag'  => 'button',
                                    'text' => [
                                        'tag'     => 'plain_text',
                                        'content' => $text,
                                    ],
                                    'type'      => 'primary',
                                    'url'       => $url,
                                ],
                            ],
                        ];
                    }
                    break;
            }
        }

        return [
            'msg_type' => 'interactive',
            'card'     => [
                'config'   => [ 'wide_screen_mode' => true ],
                'header'   => [
                    'title'    => [
                        'tag'     => 'plain_text',
                        'content' => $header_title,
                    ],
                    'template' => $header_color,
                ],
                'elements' => $elements,
            ],
        ];
    }

    /**
     * Replace {variable} placeholders in a string.
     *
     * @param string $text
     * @param array  $vars
     * @return string
     */
    private function replace_vars( $text, $vars ) {
        return str_replace( array_keys( $vars ), array_values( $vars ), $text );
    }

    // ─── Legacy hardcoded formatters (backward compat) ──────────

    /**
     * Format payment received (nhận tiền) card.
     */
    private function format_payment_received( $data ) {
        $vars = $this->build_variable_map( $data );
        $template = self::get_default_template();
        return $this->format_from_template( $template, $vars );
    }

    /**
     * Format payment sent (chuyển tiền) card.
     */
    private function format_payment_sent( $data ) {
        $amount      = isset( $data['amount'] ) ? number_format( floatval( $data['amount'] ), 0, '.', ',' ) : '0';
        $note        = $data['payment_note'] ?? $data['description'] ?? '';
        $bank_name   = $data['bank_name'] ?? 'Ngân hàng';
        $account_no  = $data['account_no'] ?? '';
        $matched_at  = $data['matched_at'] ?? current_time( 'd/m/Y H:i:s' );

        if ( strpos( $matched_at, '-' ) !== false ) {
            $matched_at = date( 'd/m/Y H:i:s', strtotime( $matched_at ) );
        }

        $fields = array_values( array_filter( [
            [
                'is_short' => true,
                'text'     => [
                    'tag'     => 'lark_md',
                    'content' => "**Ngân hàng:**\n{$bank_name}",
                ],
            ],
            ! empty( $account_no ) ? [
                'is_short' => true,
                'text'     => [
                    'tag'     => 'lark_md',
                    'content' => "**Số TK:**\n{$account_no}",
                ],
            ] : null,
            ! empty( $note ) ? [
                'is_short' => true,
                'text'     => [
                    'tag'     => 'lark_md',
                    'content' => "**Nội dung CK:**\n{$note}",
                ],
            ] : null,
            [
                'is_short' => true,
                'text'     => [
                    'tag'     => 'lark_md',
                    'content' => "**Thời gian:**\n{$matched_at}",
                ],
            ],
        ] ) );

        return [
            'msg_type' => 'interactive',
            'card'     => [
                'config' => [ 'wide_screen_mode' => true ],
                'header' => [
                    'title'    => [ 'tag' => 'plain_text', 'content' => '💸 Chuyển tiền' ],
                    'template' => 'red',
                ],
                'elements' => [
                    [
                        'tag'  => 'div',
                        'text' => [
                            'tag'     => 'lark_md',
                            'content' => "**Số tiền: -{$amount} VNĐ**",
                        ],
                    ],
                    [ 'tag' => 'hr' ],
                    [
                        'tag'    => 'div',
                        'fields' => $fields,
                    ],
                ],
            ],
        ];
    }

    /**
     * Format generic event.
     */
    private function format_generic( $event, $data ) {
        $event_label = str_replace( '_', ' ', ucfirst( $event ) );
        $content     = "Event: {$event_label}\n";
        foreach ( $data as $key => $value ) {
            if ( is_scalar( $value ) ) {
                $content .= "**{$key}:** {$value}\n";
            }
        }

        return [
            'msg_type' => 'interactive',
            'card'     => [
                'header'   => [
                    'title'    => [ 'tag' => 'plain_text', 'content' => "🔔 MonkeyPay: {$event_label}" ],
                    'template' => 'blue',
                ],
                'elements' => [
                    [
                        'tag'  => 'div',
                        'text' => [ 'tag' => 'lark_md', 'content' => $content ],
                    ],
                ],
            ],
        ];
    }
}
