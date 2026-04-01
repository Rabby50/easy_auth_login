<?php

namespace EasyAuthLogin\Auth;

use EasyAuthLogin\Support\Options;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

class AuthController
{
    private RateLimitService $rateLimit;

    public function __construct(RateLimitService $rateLimit)
    {
        $this->rateLimit = $rateLimit;
    }

    public function registerRoutes(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('easy-auth/v1', '/login', [
                'methods' => 'POST',
                'callback' => [$this, 'login'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('easy-auth/v1', '/register', [
                'methods' => 'POST',
                'callback' => [$this, 'register'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('easy-auth/v1', '/forgot-password', [
                'methods' => 'POST',
                'callback' => [$this, 'requestForgotOtp'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('easy-auth/v1', '/forgot-password/request-otp', [
                'methods' => 'POST',
                'callback' => [$this, 'requestForgotOtp'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('easy-auth/v1', '/forgot-password/verify-otp', [
                'methods' => 'POST',
                'callback' => [$this, 'verifyForgotOtp'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('easy-auth/v1', '/logout', [
                'methods' => 'POST',
                'callback' => [$this, 'logout'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    public function login(WP_REST_Request $request)
    {
        if (is_user_logged_in()) {
            return new WP_REST_Response([
                'success' => true,
                'message' => __('Already logged in.', 'easy-auth-login'),
                'redirect_url' => (new RedirectResolver())->resolve('login'),
            ], 200);
        }

        $nonceValid = wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest');
        if (! $nonceValid) {
            return new WP_Error('easy_auth_nonce', __('Invalid security token.', 'easy-auth-login'), ['status' => 403]);
        }

        $username = sanitize_user((string) $request->get_param('username'));
        $password = (string) $request->get_param('password');

        if (empty($username) || empty($password)) {
            return new WP_Error('easy_auth_required', __('Username and password are required.', 'easy-auth-login'), ['status' => 400]);
        }

        $ipAddress = $this->getIpAddress();
        if ($this->rateLimit->isLockedOut($ipAddress)) {
            return new WP_Error('easy_auth_locked', __('Too many failed attempts. Please try later.', 'easy-auth-login'), ['status' => 429]);
        }

        $credentials = [
            'user_login' => $username,
            'user_password' => $password,
            'remember' => true,
        ];

        $user = wp_signon($credentials, is_ssl());
        if (is_wp_error($user)) {
            $this->rateLimit->recordFailedAttempt($ipAddress, $username);
            return new WP_Error('easy_auth_invalid', __('Invalid credentials.', 'easy-auth-login'), ['status' => 401]);
        }

        $this->rateLimit->clearAttempts($ipAddress);

        $nextUrl = $this->sanitizeRedirectTo((string) $request->get_param('redirect_to'));

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Login successful.', 'easy-auth-login'),
            'redirect_url' => $nextUrl ?: (new RedirectResolver())->resolve('login'),
        ], 200);
    }

    public function register(WP_REST_Request $request)
    {
        $nonceValid = wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest');
        if (! $nonceValid) {
            return new WP_Error('easy_auth_nonce', __('Invalid security token.', 'easy-auth-login'), ['status' => 403]);
        }

        if (Options::get('honeypot_enabled', true) && ! empty($request->get_param('company_name'))) {
            return new WP_Error('easy_auth_spam', __('Spam detected.', 'easy-auth-login'), ['status' => 400]);
        }

        $email = sanitize_email((string) $request->get_param('email'));
        $username = sanitize_user((string) $request->get_param('username'));
        $password = (string) $request->get_param('password');

        if (! is_email($email) || empty($username) || empty($password)) {
            return new WP_Error('easy_auth_invalid_input', __('Valid username, email, and password are required.', 'easy-auth-login'), ['status' => 400]);
        }

        if (username_exists($username) || email_exists($email)) {
            return new WP_Error('easy_auth_exists', __('User already exists.', 'easy-auth-login'), ['status' => 409]);
        }

        $userId = wp_create_user($username, $password, $email);
        if (is_wp_error($userId)) {
            return $userId;
        }

        wp_set_current_user($userId);
        wp_set_auth_cookie($userId, true);

        $this->sendRegistrationThankYouEmail($userId, $username, $email);

        $nextUrl = $this->sanitizeRedirectTo((string) $request->get_param('redirect_to'));

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Registration successful.', 'easy-auth-login'),
            'redirect_url' => $nextUrl ?: (new RedirectResolver())->resolve('register'),
        ], 201);
    }

    public function requestForgotOtp(WP_REST_Request $request)
    {
        $nonceValid = wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest');
        if (! $nonceValid) {
            return new WP_Error('easy_auth_nonce', __('Invalid security token.', 'easy-auth-login'), ['status' => 403]);
        }

        $userLogin = sanitize_text_field((string) $request->get_param('user_login'));
        if (empty($userLogin)) {
            return new WP_Error('easy_auth_required', __('Email or username is required.', 'easy-auth-login'), ['status' => 400]);
        }

        $user = $this->resolveUserByLoginOrEmail($userLogin);
        if (! $user) {
            return new WP_Error('easy_auth_user_not_found', __('No account found for the provided email/username.', 'easy-auth-login'), ['status' => 404]);
        }

        if (! $this->rateLimit->canSendForgotOtp((int) $user->ID)) {
            $retryAfter = $this->rateLimit->getForgotOtpRetryAfterSeconds((int) $user->ID);
            return new WP_Error(
                'easy_auth_otp_rate_limited',
                sprintf(__('Too many OTP requests. Please try again in %d minutes.', 'easy-auth-login'), max(1, (int) ceil($retryAfter / 60))),
                ['status' => 429]
            );
        }

        $otpCode = (string) random_int(100000, 999999);
        $otpToken = wp_generate_password(32, false, false);
        $expiresIn = max(30, (int) Options::get('forgot_otp_expiry_seconds', 45));
        $expiresAt = time() + $expiresIn;

        $otpData = [
            'user_id' => (int) $user->ID,
            'user_login' => $user->user_login,
            'otp_hash' => $this->hashOtp($otpCode),
            'expires_at' => $expiresAt,
        ];

        set_transient($this->getForgotOtpTransientKey($otpToken), $otpData, $expiresIn);

        $siteName = $this->getEmailSiteName();
        $subject = sprintf(__('[%s] Password reset OTP', 'easy-auth-login'), $siteName);
        $message = sprintf(
            __('Your OTP is %1$s. It expires in %2$d seconds.', 'easy-auth-login'),
            $otpCode,
            $expiresIn
        );

        wp_mail($user->user_email, $subject, $message);

        $this->rateLimit->recordForgotOtpRequest((int) $user->ID);

        return new WP_REST_Response([
            'success' => true,
            'message' => sprintf(
                __('OTP sent. Verify within %d seconds to receive reset link.', 'easy-auth-login'),
                $expiresIn
            ),
            'awaiting_otp' => true,
            'otp_token' => $otpToken,
            'expires_in' => $expiresIn,
            'cooldown_seconds' => $this->rateLimit->getForgotOtpRetryAfterSeconds((int) $user->ID),
        ], 200);
    }

    public function verifyForgotOtp(WP_REST_Request $request)
    {
        $nonceValid = wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest');
        if (! $nonceValid) {
            return new WP_Error('easy_auth_nonce', __('Invalid security token.', 'easy-auth-login'), ['status' => 403]);
        }

        $userLogin = sanitize_text_field((string) $request->get_param('user_login'));
        $otpToken = sanitize_text_field((string) $request->get_param('otp_token'));
        $otpCode = preg_replace('/[^0-9]/', '', (string) $request->get_param('otp_code'));

        if ($userLogin === '' || $otpToken === '' || strlen((string) $otpCode) !== 6) {
            return new WP_Error('easy_auth_invalid_otp_request', __('OTP code, token, and email/username are required.', 'easy-auth-login'), ['status' => 400]);
        }

        $otpData = get_transient($this->getForgotOtpTransientKey($otpToken));
        if (! is_array($otpData)) {
            return new WP_Error('easy_auth_otp_expired', __('OTP expired. Please request a new one.', 'easy-auth-login'), ['status' => 400]);
        }

        $requestedUser = $this->resolveUserByLoginOrEmail($userLogin);
        if (! $requestedUser || empty($otpData['user_id']) || (int) $otpData['user_id'] !== (int) $requestedUser->ID) {
            return new WP_Error('easy_auth_otp_mismatch', __('Invalid OTP request context.', 'easy-auth-login'), ['status' => 400]);
        }

        if ((int) ($otpData['expires_at'] ?? 0) < time()) {
            delete_transient($this->getForgotOtpTransientKey($otpToken));
            return new WP_Error('easy_auth_otp_expired', __('OTP expired. Please request a new one.', 'easy-auth-login'), ['status' => 400]);
        }

        $hashedInput = $this->hashOtp((string) $otpCode);
        if (! hash_equals((string) ($otpData['otp_hash'] ?? ''), $hashedInput)) {
            return new WP_Error('easy_auth_otp_invalid', __('Incorrect OTP code.', 'easy-auth-login'), ['status' => 400]);
        }

        delete_transient($this->getForgotOtpTransientKey($otpToken));

        $result = retrieve_password($requestedUser->user_login);
        if ($result !== true) {
            return new WP_Error('easy_auth_reset_failed', __('Unable to send reset email.', 'easy-auth-login'), ['status' => 400]);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('OTP verified. Password reset email sent.', 'easy-auth-login'),
        ], 200);
    }

    public function logout(WP_REST_Request $request)
    {
        $nonceValid = wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest');
        if (! $nonceValid) {
            return new WP_Error('easy_auth_nonce', __('Invalid security token.', 'easy-auth-login'), ['status' => 403]);
        }

        wp_logout();

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Logged out successfully.', 'easy-auth-login'),
            'redirect_url' => (new RedirectResolver())->resolve('logout'),
        ], 200);
    }

    private function getIpAddress(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return sanitize_text_field((string) $ip);
    }

    private function sanitizeRedirectTo(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $decoded = rawurldecode($url);
        return wp_validate_redirect($decoded, '');
    }

    private function resolveUserByLoginOrEmail(string $value)
    {
        if (is_email($value)) {
            return get_user_by('email', $value);
        }

        return get_user_by('login', $value);
    }

    private function getForgotOtpTransientKey(string $token): string
    {
        return 'easy_auth_forgot_otp_' . $token;
    }

    private function hashOtp(string $otp): string
    {
        return hash('sha256', $otp . wp_salt('auth'));
    }

    private function getEmailSiteName(): string
    {
        $customSiteName = sanitize_text_field((string) Options::get('custom_site_name', ''));
        return $customSiteName !== '' ? $customSiteName : (string) get_bloginfo('name');
    }

    private function sendRegistrationThankYouEmail(int $userId, string $username, string $email): void
    {
        $user = get_user_by('id', $userId);
        if (! $user || ! is_email($user->user_email)) {
            return;
        }

        $siteName = $this->getEmailSiteName();
        $template = (string) Options::get(
            'registration_thank_you_message',
            'Thank you for registering on {site_name}, {username}.'
        );

        $message = strtr($template, [
            '{site_name}' => $siteName,
            '{username}' => $username,
            '{email}' => $email,
        ]);

        $subject = sprintf(__('[%s] Registration Successful', 'easy-auth-login'), $siteName);
        wp_mail($user->user_email, $subject, $message);
    }
}
