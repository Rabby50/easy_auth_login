<?php

namespace EasyAuthLogin\Frontend;

use EasyAuthLogin\Support\Options;

if (! defined('ABSPATH')) {
    exit;
}

class Assets
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        wp_register_style(
            'easy-auth-login',
            EASY_AUTH_LOGIN_URL . 'assets/css/easy-auth-login.css',
            [],
            EASY_AUTH_LOGIN_VERSION
        );

        wp_register_script(
            'easy-auth-login',
            EASY_AUTH_LOGIN_URL . 'assets/js/easy-auth-login.js',
            [],
            EASY_AUTH_LOGIN_VERSION,
            true
        );

        $settings = Options::all();

        wp_localize_script('easy-auth-login', 'EasyAuthLoginData', [
            'restUrl' => esc_url_raw(rest_url('easy-auth/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'turnstileSiteKey' => (string) ($settings['turnstile_site_key'] ?? ''),
            'passwordToggleEnabled' => ! empty($settings['password_visibility_toggle']),
            'buttonColor' => (string) ($settings['button_color'] ?? '#146ef5'),
            'buttonTextColor' => (string) ($settings['button_text_color'] ?? '#ffffff'),
            'messages' => [
                'networkError' => __('Request failed. Please try again.', 'easy-auth-login'),
            ],
        ]);
    }
}
