<?php
/**
 * MonkeyPay REST API — Authentication & Organization
 *
 * Handles organization registration, login, password management,
 * Google OAuth, 2FA (TOTP), plan listing, and merchant usage stats.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_REST_Auth {

    // ── Rate-limit constants ────────────────────────
    const RATE_LIMIT_LOGIN     = 5;   // max attempts per window
    const RATE_LIMIT_WINDOW    = 60;  // seconds
    const RATE_LIMIT_IP_EMAILS = 3;   // max different emails per IP per window
    const RATE_LIMIT_2FA       = 5;   // max 2FA verify attempts
    const RATE_LIMIT_2FA_LOCK  = 900; // 15 min lockout

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
                    'required'          => false,
                    'type'              => 'string',
                    'default'           => '',
                ],
                'phone' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ],
                'auth_provider' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => 'password',
                ],
                'google_id' => [
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

        // Google OAuth
        register_rest_route( 'monkeypay/v1', '/google-auth', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'google_auth' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args' => [
                'id_token' => [
                    'required' => true,
                    'type'     => 'string',
                ],
            ],
        ] );

        // Set password (for Google users who don't have one)
        register_rest_route( 'monkeypay/v1', '/set-password', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'set_password' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args' => [
                'new_password' => [
                    'required' => true,
                    'type'     => 'string',
                ],
            ],
        ] );

        // 2FA Setup — generate TOTP secret + QR
        register_rest_route( 'monkeypay/v1', '/2fa/setup', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'twofa_setup' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        // 2FA Verify — verify OTP code (used for enable + login)
        register_rest_route( 'monkeypay/v1', '/2fa/verify', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'twofa_verify' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args' => [
                'otp_code' => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'email' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                    'default'           => '',
                ],
                'temp_token' => [
                    'type'    => 'string',
                    'default' => '',
                ],
            ],
        ] );

        // 2FA Disable
        register_rest_route( 'monkeypay/v1', '/2fa/disable', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'twofa_disable' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args' => [
                'otp_code' => [
                    'required' => true,
                    'type'     => 'string',
                ],
            ],
        ] );

        // 2FA Status
        register_rest_route( 'monkeypay/v1', '/2fa/status', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'twofa_status' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
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

    // ═══════════════════════════════════════════════════
    // Rate Limiting Helpers
    // ═══════════════════════════════════════════════════

    /**
     * Get client IP address.
     *
     * @return string
     */
    private static function get_client_ip() {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];
        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
                // Handle comma-separated list (X-Forwarded-For)
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                return $ip;
            }
        }
        return '127.0.0.1';
    }

    /**
     * Check rate limit for a given action.
     *
     * @param  string $action   Action key (login, register, etc.)
     * @param  int    $max      Max attempts.
     * @param  int    $window   Time window in seconds.
     * @return bool|WP_REST_Response  True if OK, WP_REST_Response if blocked.
     */
    private static function check_rate_limit( $action, $max = null, $window = null ) {
        $max    = $max ?? self::RATE_LIMIT_LOGIN;
        $window = $window ?? self::RATE_LIMIT_WINDOW;
        $ip     = self::get_client_ip();
        $key    = 'mp_rl_' . $action . '_' . md5( $ip );

        $attempts = get_transient( $key );
        if ( false === $attempts ) {
            $attempts = 0;
        }

        if ( $attempts >= $max ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau ' . $window . ' giây.',
                'code'    => 'rate_limited',
            ], 429 );
        }

        set_transient( $key, $attempts + 1, $window );
        return true;
    }

    /**
     * Check IP → email diversity limit. Prevents 1 IP from probing many emails.
     *
     * @param  string $email  Email being used.
     * @return bool|WP_REST_Response  True if OK, WP_REST_Response if blocked.
     */
    private static function check_ip_email_limit( $email ) {
        $ip  = self::get_client_ip();
        $key = 'mp_rl_ipe_' . md5( $ip );

        $emails = get_transient( $key );
        if ( false === $emails || ! is_array( $emails ) ) {
            $emails = [];
        }

        // Normalize email
        $email_hash = md5( strtolower( trim( $email ) ) );

        if ( ! in_array( $email_hash, $emails, true ) ) {
            $emails[] = $email_hash;
        }

        if ( count( $emails ) > self::RATE_LIMIT_IP_EMAILS ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Quá nhiều tài khoản thử từ cùng một địa chỉ. Vui lòng thử lại sau.',
                'code'    => 'ip_email_limited',
            ], 429 );
        }

        set_transient( $key, $emails, self::RATE_LIMIT_WINDOW * 5 ); // 5-minute window
        return true;
    }

    // ═══════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════

    /**
     * Get resolved API URL.
     *
     * @return string
     */
    private static function get_api_url() {
        $url = get_option( 'monkeypay_api_url', MONKEYPAY_API_URL );
        return rtrim( ! empty( $url ) ? $url : MONKEYPAY_API_URL, '/' );
    }

    /**
     * Save merchant credentials to WP options on successful auth.
     *
     * @param array  $merchant Merchant data from server response.
     * @param string $provider Auth provider (password, google).
     */
    private static function save_merchant_credentials( $merchant, $provider = 'password' ) {
        if ( ! empty( $merchant['api_key'] ) ) {
            update_option( 'monkeypay_api_key', $merchant['api_key'] );
        }
        if ( ! empty( $merchant['webhook_secret'] ) ) {
            update_option( 'monkeypay_webhook_secret', $merchant['webhook_secret'] );
        }
        if ( ! empty( $merchant['admin_secret'] ) ) {
            update_option( 'monkeypay_admin_secret', $merchant['admin_secret'] );
        }
        update_option( 'monkeypay_auth_provider', sanitize_text_field( $provider ) );
    }

    /**
     * Validate password strength: min 8 chars, at least 1 letter + 1 number.
     *
     * @param  string $password Password to check.
     * @return bool|string True if valid, error message if not.
     */
    private static function validate_password_strength( $password ) {
        if ( strlen( $password ) < 8 ) {
            return 'Mật khẩu phải có ít nhất 8 ký tự.';
        }
        if ( ! preg_match( '/[a-zA-Z]/', $password ) ) {
            return 'Mật khẩu phải chứa ít nhất 1 chữ cái.';
        }
        if ( ! preg_match( '/[0-9]/', $password ) ) {
            return 'Mật khẩu phải chứa ít nhất 1 chữ số.';
        }
        return true;
    }

    // ═══════════════════════════════════════════════════
    // Auth Endpoints
    // ═══════════════════════════════════════════════════

    /**
     * Register a new organization.
     * Supports both password and Google auth registration.
     */
    public static function register_organization( $request ) {
        // Rate limit
        $check = self::check_rate_limit( 'register' );
        if ( $check instanceof WP_REST_Response ) {
            return $check;
        }

        $api_url       = self::get_api_url();
        $auth_provider = sanitize_text_field( $request->get_param( 'auth_provider' ) );
        $is_google     = ( 'google' === $auth_provider );

        if ( empty( $api_url ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Chưa cấu hình MonkeyPay API URL. Vui lòng nhập URL server trước.',
            ], 400 );
        }

        // Password strength — only required for non-Google registration
        if ( ! $is_google ) {
            $pw_check = self::validate_password_strength( $request->get_param( 'password' ) );
            if ( true !== $pw_check ) {
                return new WP_REST_Response( [
                    'success' => false,
                    'message' => $pw_check,
                ], 400 );
            }
        }

        $body = [
            'name'        => $request->get_param( 'name' ),
            'email'       => $request->get_param( 'email' ),
            'phone'       => $request->get_param( 'phone' ),
            'site_url'    => home_url(),
            'webhook_url' => rest_url( 'monkeypay/v1/webhook' ),
        ];

        // Add auth-specific fields
        if ( $is_google ) {
            $body['google_id']     = sanitize_text_field( $request->get_param( 'google_id' ) );
            $body['auth_provider'] = 'google';
            // Generate random password to satisfy backend server validation.
            // User won't need this password — they authenticate via Google.
            $body['password'] = wp_generate_password( 16, true, true );
        } else {
            $body['password'] = $request->get_param( 'password' );
        }

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
                'message' => isset( $data['message'] ) ? $data['message'] : ( isset( $data['error'] ) ? $data['error'] : 'Đăng ký thất bại' ),
            ], $code );
        }

        // Auto-save credentials
        if ( ! empty( $data['merchant'] ) ) {
            self::save_merchant_credentials( $data['merchant'], $is_google ? 'google' : 'password' );
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
        $email = $request->get_param( 'email' );

        // Rate limit — login failures
        $check = self::check_rate_limit( 'login' );
        if ( $check instanceof WP_REST_Response ) {
            return $check;
        }

        // Rate limit — IP probing multiple emails
        $ip_check = self::check_ip_email_limit( $email );
        if ( $ip_check instanceof WP_REST_Response ) {
            return $ip_check;
        }

        $api_url = self::get_api_url();

        if ( empty( $api_url ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Chưa cấu hình MonkeyPay API URL. Vui lòng nhập URL server trước.',
            ], 400 );
        }

        $body = [
            'email'    => $email,
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
            // Generic error message — don't reveal if email exists
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Email hoặc mật khẩu không đúng.',
            ], 401 );
        }

        // Check if 2FA is required
        if ( ! empty( $data['requires_2fa'] ) ) {
            return new WP_REST_Response( [
                'success'      => true,
                'requires_2fa' => true,
                'temp_token'   => isset( $data['temp_token'] ) ? $data['temp_token'] : '',
                'message'      => 'Vui lòng nhập mã xác thực 2 bước.',
            ], 200 );
        }

        // Auto-save credentials
        if ( ! empty( $data['merchant'] ) ) {
            self::save_merchant_credentials( $data['merchant'], 'password' );
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
        // Rate limit
        $check = self::check_rate_limit( 'forgot_password', 3, 300 );
        if ( $check instanceof WP_REST_Response ) {
            return $check;
        }

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

        // Password strength
        $pw_check = self::validate_password_strength( $request->get_param( 'new_password' ) );
        if ( true !== $pw_check ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $pw_check,
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
                'message' => isset( $data['message'] ) ? $data['message'] : ( isset( $data['error'] ) ? $data['error'] : 'Đổi mật khẩu thất bại' ),
            ], $code );
        }

        return new WP_REST_Response( [
            'success' => true,
            'message' => isset( $data['message'] ) ? $data['message'] : 'Đổi mật khẩu thành công!',
        ], 200 );
    }

    // ═══════════════════════════════════════════════════
    // Google OAuth
    // ═══════════════════════════════════════════════════

    /**
     * Google OAuth — verify id_token with Google, decide register or login.
     *
     * Flow:
     * - Verify id_token via Google tokeninfo API
     * - If WP already has monkeypay_api_key → already linked → dashboard
     * - If not → return needs_registration with email/name pre-filled
     */
    public static function google_auth( $request ) {
        // Rate limit
        $check = self::check_rate_limit( 'google_auth' );
        if ( $check instanceof WP_REST_Response ) {
            return $check;
        }

        $id_token = $request->get_param( 'id_token' );

        if ( empty( $id_token ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Thiếu Google token.',
            ], 400 );
        }

        // ── Verify id_token with Google ──
        $google_user = self::verify_google_id_token( $id_token );

        if ( is_wp_error( $google_user ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $google_user->get_error_message(),
            ], 401 );
        }

        $email     = sanitize_email( $google_user['email'] );
        $name      = sanitize_text_field( isset( $google_user['name'] ) ? $google_user['name'] : '' );
        $google_id = sanitize_text_field( $google_user['sub'] );

        if ( empty( $email ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Không lấy được email từ Google.',
            ], 400 );
        }

        // ── Check if this WP site already has a linked merchant ──
        $existing_key = get_option( 'monkeypay_api_key', '' );

        if ( ! empty( $existing_key ) ) {
            // Already linked — save auth_provider and go to dashboard
            update_option( 'monkeypay_auth_provider', 'google' );

            return new WP_REST_Response( [
                'success'        => true,
                'already_linked' => true,
                'message'        => 'Đăng nhập Google thành công!',
            ], 200 );
        }

        // ── New merchant — needs registration ──
        // Return Google user info so frontend can pre-fill the register form.
        return new WP_REST_Response( [
            'success'            => true,
            'needs_registration' => true,
            'google_user'        => [
                'email'     => $email,
                'name'      => $name,
                'google_id' => $google_id,
            ],
            'message' => 'Vui lòng hoàn tất thông tin tổ chức để đăng ký.',
        ], 200 );
    }

    /**
     * Verify Google id_token using Google's tokeninfo API.
     *
     * @param string $id_token The JWT id_token from Google.
     * @return array|WP_Error Google user data or error.
     */
    private static function verify_google_id_token( $id_token ) {
        $response = wp_remote_get(
            'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode( $id_token ),
            [ 'timeout' => 10 ]
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'google_verify_failed', 'Không thể xác thực với Google: ' . $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 || empty( $data ) ) {
            return new WP_Error( 'google_token_invalid', 'Google token không hợp lệ hoặc đã hết hạn.' );
        }

        // Verify audience matches our client ID
        $expected_client_id = defined( 'MONKEYPAY_GOOGLE_CLIENT_ID' )
            ? MONKEYPAY_GOOGLE_CLIENT_ID
            : ( getenv( 'GOOGLE_CLIENT_ID' ) ?: '' );

        if ( ! isset( $data['aud'] ) || $data['aud'] !== $expected_client_id ) {
            return new WP_Error( 'google_aud_mismatch', 'Token không dành cho ứng dụng này.' );
        }

        // Verify email is verified
        if ( empty( $data['email_verified'] ) || $data['email_verified'] !== 'true' ) {
            return new WP_Error( 'google_email_unverified', 'Email chưa được xác thực bởi Google.' );
        }

        return $data;
    }

    // ═══════════════════════════════════════════════════
    // Set Password (for Google users)
    // ═══════════════════════════════════════════════════

    /**
     * Set password for Google-authenticated users who don't have one yet.
     */
    public static function set_password( $request ) {
        $api_key = get_option( 'monkeypay_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Chưa đăng nhập. Vui lòng đăng nhập trước.',
            ], 401 );
        }

        // Password strength
        $pw_check = self::validate_password_strength( $request->get_param( 'new_password' ) );
        if ( true !== $pw_check ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $pw_check,
            ], 400 );
        }

        $api_url = self::get_api_url();

        $response = wp_remote_post( $api_url . '/api/set-password', [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Key'    => $api_key,
            ],
            'body' => wp_json_encode( [
                'new_password' => $request->get_param( 'new_password' ),
            ] ),
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
                'message' => isset( $data['message'] ) ? $data['message'] : 'Tạo mật khẩu thất bại.',
            ], $code );
        }

        // Update provider to password since they now have a password
        update_option( 'monkeypay_auth_provider', 'google_password' );

        return new WP_REST_Response( [
            'success' => true,
            'message' => isset( $data['message'] ) ? $data['message'] : 'Tạo mật khẩu thành công!',
        ], 200 );
    }

    // ═══════════════════════════════════════════════════
    // 2FA (TOTP)
    // ═══════════════════════════════════════════════════

    /**
     * 2FA Setup — request TOTP secret + QR from backend.
     */
    public static function twofa_setup( $request ) {
        $api_key = get_option( 'monkeypay_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Chưa đăng nhập.',
            ], 401 );
        }

        $api_url = self::get_api_url();

        $response = wp_remote_post( $api_url . '/api/2fa/setup', [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Key'    => $api_key,
            ],
            'body' => '{}',
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
                'message' => isset( $data['message'] ) ? $data['message'] : '2FA setup failed.',
            ], $code );
        }

        return new WP_REST_Response( [
            'success'    => true,
            'secret'     => isset( $data['secret'] ) ? $data['secret'] : '',
            'qr_url'     => isset( $data['qr_url'] ) ? $data['qr_url'] : '',
            'otpauth_uri' => isset( $data['otpauth_uri'] ) ? $data['otpauth_uri'] : '',
        ], 200 );
    }

    /**
     * 2FA Verify — verify OTP code.
     *
     * Use cases:
     *   1) Enable 2FA (from account page — requires API key)
     *   2) Login 2FA step (uses temp_token from login response)
     */
    public static function twofa_verify( $request ) {
        // Rate limit for 2FA
        $check = self::check_rate_limit( '2fa_verify', self::RATE_LIMIT_2FA, self::RATE_LIMIT_2FA_LOCK );
        if ( $check instanceof WP_REST_Response ) {
            return $check;
        }

        $api_url    = self::get_api_url();
        $otp_code   = $request->get_param( 'otp_code' );
        $temp_token = $request->get_param( 'temp_token' );
        $email      = $request->get_param( 'email' );
        $api_key    = get_option( 'monkeypay_api_key', '' );

        $headers = [ 'Content-Type' => 'application/json' ];
        $body    = [ 'otp_code' => $otp_code ];

        // If we have a temp_token — this is a login 2FA step
        if ( ! empty( $temp_token ) ) {
            $body['temp_token'] = $temp_token;
            if ( ! empty( $email ) ) {
                $body['email'] = $email;
            }
        } elseif ( ! empty( $api_key ) ) {
            // This is enabling 2FA from account page
            $headers['X-Api-Key'] = $api_key;
        } else {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Thiếu thông tin xác thực.',
            ], 401 );
        }

        $response = wp_remote_post( $api_url . '/api/2fa/verify', [
            'timeout' => 15,
            'headers' => $headers,
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
                'message' => isset( $data['message'] ) ? $data['message'] : 'Mã OTP không hợp lệ.',
            ], $code );
        }

        // If this was a login 2FA — save credentials
        if ( ! empty( $temp_token ) && ! empty( $data['merchant'] ) ) {
            $provider = ! empty( $data['auth_provider'] ) ? $data['auth_provider'] : 'password';
            self::save_merchant_credentials( $data['merchant'], $provider );
        }

        return new WP_REST_Response( [
            'success'    => true,
            'data'       => isset( $data['merchant'] ) ? $data : null,
            'twofa_enabled' => true,
            'message'    => isset( $data['message'] ) ? $data['message'] : 'Xác thực thành công!',
        ], 200 );
    }

    /**
     * 2FA Disable — disable TOTP for current merchant.
     */
    public static function twofa_disable( $request ) {
        $api_key = get_option( 'monkeypay_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Chưa đăng nhập.',
            ], 401 );
        }

        $api_url = self::get_api_url();

        $response = wp_remote_post( $api_url . '/api/2fa/disable', [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Key'    => $api_key,
            ],
            'body' => wp_json_encode( [
                'otp_code' => $request->get_param( 'otp_code' ),
            ] ),
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
                'message' => isset( $data['message'] ) ? $data['message'] : 'Tắt 2FA thất bại.',
            ], $code );
        }

        return new WP_REST_Response( [
            'success'       => true,
            'twofa_enabled' => false,
            'message'       => isset( $data['message'] ) ? $data['message'] : 'Đã tắt xác thực 2 bước.',
        ], 200 );
    }

    /**
     * 2FA Status — check if 2FA is enabled for current merchant.
     */
    public static function twofa_status() {
        $api_key = get_option( 'monkeypay_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_REST_Response( [
                'success'       => true,
                'twofa_enabled' => false,
            ], 200 );
        }

        $api_url = self::get_api_url();

        $response = wp_remote_get( $api_url . '/api/2fa/status', [
            'timeout' => 10,
            'headers' => [
                'X-Api-Key' => $api_key,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( [
                'success'       => true,
                'twofa_enabled' => false,
            ], 200 );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        return new WP_REST_Response( [
            'success'       => true,
            'twofa_enabled' => ! empty( $data['twofa_enabled'] ),
        ], 200 );
    }

    // ═══════════════════════════════════════════════════
    // Plans & Usage
    // ═══════════════════════════════════════════════════

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

        // Inject local data into merchant
        if ( ! isset( $data['merchant'] ) ) {
            $data['merchant'] = [];
        }
        $data['merchant']['api_key']       = $api_key;
        $data['merchant']['auth_provider'] = get_option( 'monkeypay_auth_provider', 'password' );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $data,
        ], 200 );
    }
}
