<?php

namespace EasyAuthLogin\Auth;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

class MagicLinkController
{
    public function registerRoutes(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('easy-auth/v1', '/magic-link/request', [
                'methods' => 'POST',
                'callback' => [$this, 'requestLink'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('easy-auth/v1', '/magic-link/consume', [
                'methods' => 'POST',
                'callback' => [$this, 'consumeLink'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    public function requestLink(WP_REST_Request $request)
    {
        $nonceValid = wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest');
        if (! $nonceValid) {
            return new WP_Error('easy_auth_nonce', __('Invalid security token.', 'easy-auth-login'), ['status' => 403]);
        }

        $email = sanitize_email((string) $request->get_param('email'));
        if (! is_email($email)) {
            return new WP_Error('easy_auth_email_invalid', __('Valid email is required.', 'easy-auth-login'), ['status' => 400]);
        }

        $user = get_user_by('email', $email);
        if (! $user) {
            return new WP_REST_Response([
                'success' => true,
                'message' => __('If that account exists, a magic link has been sent.', 'easy-auth-login'),
            ], 200);
        }

        $token = wp_generate_password(32, false, false);
        $hashed = wp_hash_password($token);
        $expiry = time() + (15 * MINUTE_IN_SECONDS);

        update_user_meta($user->ID, 'easy_auth_magic_hash', $hashed);
        update_user_meta($user->ID, 'easy_auth_magic_expiry', $expiry);

        $consumeUrl = add_query_arg([
            'easy_auth_magic' => rawurlencode($token),
            'easy_auth_user' => $user->ID,
        ], home_url('/'));

        wp_mail(
            $user->user_email,
            __('Your Magic Login Link', 'easy-auth-login'),
            sprintf(__('Click to sign in: %s', 'easy-auth-login'), esc_url($consumeUrl))
        );

        return new WP_REST_Response([
            'success' => true,
            'message' => __('If that account exists, a magic link has been sent.', 'easy-auth-login'),
        ], 200);
    }

    public function consumeLink(WP_REST_Request $request)
    {
        $userId = (int) $request->get_param('user_id');
        $token = sanitize_text_field((string) $request->get_param('token'));

        if ($userId <= 0 || empty($token)) {
            return new WP_Error('easy_auth_magic_invalid', __('Invalid magic link payload.', 'easy-auth-login'), ['status' => 400]);
        }

        $hash = (string) get_user_meta($userId, 'easy_auth_magic_hash', true);
        $expiry = (int) get_user_meta($userId, 'easy_auth_magic_expiry', true);

        if (empty($hash) || $expiry < time() || ! wp_check_password($token, $hash)) {
            return new WP_Error('easy_auth_magic_expired', __('Magic link is invalid or expired.', 'easy-auth-login'), ['status' => 401]);
        }

        delete_user_meta($userId, 'easy_auth_magic_hash');
        delete_user_meta($userId, 'easy_auth_magic_expiry');

        wp_set_current_user($userId);
        wp_set_auth_cookie($userId, true);

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Magic link accepted.', 'easy-auth-login'),
            'redirect_url' => (new RedirectResolver())->resolve('login'),
        ], 200);
    }
}
