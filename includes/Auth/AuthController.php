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
                'callback' => [$this, 'forgotPassword'],
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

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Login successful.', 'easy-auth-login'),
            'redirect_url' => (new RedirectResolver())->resolve('login'),
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

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Registration successful.', 'easy-auth-login'),
            'redirect_url' => (new RedirectResolver())->resolve('register'),
        ], 201);
    }

    public function forgotPassword(WP_REST_Request $request)
    {
        $nonceValid = wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest');
        if (! $nonceValid) {
            return new WP_Error('easy_auth_nonce', __('Invalid security token.', 'easy-auth-login'), ['status' => 403]);
        }

        $userLogin = sanitize_text_field((string) $request->get_param('user_login'));
        if (empty($userLogin)) {
            return new WP_Error('easy_auth_required', __('Email or username is required.', 'easy-auth-login'), ['status' => 400]);
        }

        $result = retrieve_password($userLogin);
        if ($result === true) {
            return new WP_REST_Response([
                'success' => true,
                'message' => __('Password reset email sent.', 'easy-auth-login'),
            ], 200);
        }

        return new WP_Error('easy_auth_reset_failed', __('Unable to send reset email.', 'easy-auth-login'), ['status' => 400]);
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
}
