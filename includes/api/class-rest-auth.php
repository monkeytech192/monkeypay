<?php
/**
 * MonkeyPay REST API — Authentication & Organization
 *
 * Handles organization registration, login, password management,
 * plan listing, and merchant usage statistics.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_REST_Auth {

    /**
     * Register REST routes for authentication.
     */
    public static function register_routes() {
        // Register new organization
        register_rest_route( 'monkeypay/v1', '/register', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'register_organization' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args' => [
                'name' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'email' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ],
                'password' => [
                    'required'          => true,
                    'type'              => 'string',
                ],
                'phone' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ],
            ],
        ] );

        // Login to existing organization
        register_rest_route( 'monkeypay/v1', '/login', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'login_organization' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args' => [
                'email' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ],
                'password' => [
                    'required'          => true,
                    'type'              => 'string',
                ],
            ],
        ] );

        // Forgot password
        register_rest_route( 'monkeypay/v1', '/forgot-password', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'forgot_password' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args' => [
                'email' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ],
            ],
        ] );

        // Change password
        register_rest_route( 'monkeypay/v1', '/change-password', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'change_password' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args' => [
                'email' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ],
                'old_password' => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'new_password' => [
                    'required' => true,
                    'type'     => 'string',
                ],
            ],
        ] );

        // List available plans
        register_rest_route( 'monkeypay/v1', '/plans', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'list_plans' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // Get merchant usage stats
        register_rest_route( 'monkeypay/v1', '/usage', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'merchant_usage' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    /**
     * Helper: Get resolved API URL.
     *
     * @return string
     */
    private static function get_api_url() {
        $url = get_option( 'monkeypay_api_url', MONKEYPAY_API_URL );
        return rtrim( ! empty( $url ) ? $url : MONKEYPAY_API_URL, '/' );
    }

    /**
     * Helper: Save merchant credentials to WP options on successful auth.
     *
     * @param array $merchant Merchant data from server response.
     */
    private static function save_merchant_credentials( $merchant ) {
        if ( ! empty( $merchant['api_key'] ) ) {
            update_option( 'monkeypay_api_key', $merchant['api_key'] );
        }
        if ( ! empty( $merchant['webhook_secret'] ) ) {
            update_option( 'monkeypay_webhook_secret', $merchant['webhook_secret'] );
        }
        if ( ! empty( $merchant['admin_secret'] ) ) {
            update_option( 'monkeypay_admin_secret', $merchant['admin_secret'] );
        }
    }

    /**
     * Register a new organization.
     */
    public static function register_organization( $request ) {
        $api_url = self::get_api_url();

        if ( empty( $api_url ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Chưa cấu hình MonkeyPay API URL. Vui lòng nhập URL server trước.',
            ], 400 );
        }

        $body = [
            'name'     => $request->get_param( 'name' ),
            'email'    => $request->get_param( 'email' ),
            'password' => $request->get_param( 'password' ),
            'phone'    => $request->get_param( 'phone' ),
            'site_url' => home_url(),
        ];

        $response = wp_remote_post( $api_url . '/api/register', [
            'timeout' => 15,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $body ),
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $response->get_error_message(),
            ], 500 );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => isset( $data['message'] ) ? $data['message'] : ( isset( $data['error'] ) ? $data['error'] : 'Registration failed' ),
            ], $code );
        }

        // Auto-save credentials
        if ( ! empty( $data['merchant'] ) ) {
            self::save_merchant_credentials( $data['merchant'] );
        }

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $data,
        ], 201 );
    }

    /**
     * Login to existing organization.
     */
    public static function login_organization( $request ) {
        $api_url = self::get_api_url();

        if ( empty( $api_url ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Chưa cấu hình MonkeyPay API URL. Vui lòng nhập URL server trước.',
            ], 400 );
        }

        $body = [
            'email'    => $request->get_param( 'email' ),
            'password' => $request->get_param( 'password' ),
        ];

        $response = wp_remote_post( $api_url . '/api/login', [
            'timeout' => 15,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $body ),
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $response->get_error_message(),
            ], 500 );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => isset( $data['message'] ) ? $data['message'] : ( isset( $data['error'] ) ? $data['error'] : 'Login failed' ),
            ], $code );
        }

        // Auto-save credentials
        if ( ! empty( $data['merchant'] ) ) {
            self::save_merchant_credentials( $data['merchant'] );
        }

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $data,
        ], 200 );
    }

    /**
     * Forgot password.
     */
    public static function forgot_password( $request ) {
        $api_url = self::get_api_url();

        if ( empty( $api_url ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Chưa cấu hình MonkeyPay API URL.',
            ], 400 );
        }

        $response = wp_remote_post( $api_url . '/api/forgot-password', [
            'timeout' => 15,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [ 'email' => $request->get_param( 'email' ) ] ),
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $response->get_error_message(),
            ], 500 );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => isset( $data['message'] ) ? $data['message'] : ( isset( $data['error'] ) ? $data['error'] : 'Reset failed' ),
            ], $code );
        }

        return new WP_REST_Response( [
            'success'      => true,
            'new_password' => isset( $data['new_password'] ) ? $data['new_password'] : '',
            'message'      => isset( $data['message'] ) ? $data['message'] : 'Đã đặt lại mật khẩu.',
        ], 200 );
    }

    /**
     * Change password.
     */
    public static function change_password( $request ) {
        $api_url = self::get_api_url();

        if ( empty( $api_url ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Chưa cấu hình MonkeyPay API URL.',
            ], 400 );
        }

        $body = [
            'email'        => $request->get_param( 'email' ),
            'old_password' => $request->get_param( 'old_password' ),
            'new_password' => $request->get_param( 'new_password' ),
        ];

        $response = wp_remote_post( $api_url . '/api/change-password', [
            'timeout' => 15,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $body ),
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $response->get_error_message(),
            ], 500 );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => isset( $data['message'] ) ? $data['message'] : ( isset( $data['error'] ) ? $data['error'] : 'Change password failed' ),
            ], $code );
        }

        return new WP_REST_Response( [
            'success' => true,
            'message' => isset( $data['message'] ) ? $data['message'] : 'Đổi mật khẩu thành công!',
        ], 200 );
    }

    /**
     * List available plans.
     */
    public static function list_plans() {
        $response = wp_remote_get( self::get_api_url() . '/api/plans', [
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $response->get_error_message(),
            ], 500 );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $data,
        ], 200 );
    }

    /**
     * Get merchant usage stats.
     */
    public static function merchant_usage() {
        $api_key = get_option( 'monkeypay_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Chưa có API key. Vui lòng tạo tổ chức trước.',
            ], 400 );
        }

        $response = wp_remote_get( self::get_api_url() . '/api/me', [
            'timeout' => 15,
            'headers' => [
                'X-Api-Key' => $api_key,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $response->get_error_message(),
            ], 500 );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => isset( $data['error'] ) ? $data['error'] : 'Failed to get usage',
            ], $code );
        }

        // Inject local API key into merchant data
        if ( ! isset( $data['merchant'] ) ) {
            $data['merchant'] = [];
        }
        $data['merchant']['api_key'] = $api_key;

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $data,
        ], 200 );
    }
}
