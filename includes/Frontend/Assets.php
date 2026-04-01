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

        wp_register_style(
            'easy-auth-login-template-default',
            EASY_AUTH_LOGIN_URL . 'template/default.css',
            ['easy-auth-login'],
            (string) filemtime(EASY_AUTH_LOGIN_PATH . 'template/default.css')
        );

        wp_register_style(
            'easy-auth-login-template-split_panel',
            EASY_AUTH_LOGIN_URL . 'template/split-panel.css',
            ['easy-auth-login'],
            (string) filemtime(EASY_AUTH_LOGIN_PATH . 'template/split-panel.css')
        );

        wp_register_style(
            'easy-auth-login-template-glass_wave',
            EASY_AUTH_LOGIN_URL . 'template/glass-wave.css',
            ['easy-auth-login'],
            (string) filemtime(EASY_AUTH_LOGIN_PATH . 'template/glass-wave.css')
        );

        wp_register_style(
            'easy-auth-login-template-neo_3d_toggle',
            EASY_AUTH_LOGIN_URL . 'template/neo-3d-toggle.css',
            ['easy-auth-login'],
            (string) filemtime(EASY_AUTH_LOGIN_PATH . 'template/neo-3d-toggle.css')
        );

        wp_register_script(
            'easy-auth-login',
            EASY_AUTH_LOGIN_URL . 'assets/js/easy-auth-login.js',
            [],
            EASY_AUTH_LOGIN_VERSION,
            true
        );

        $settings = Options::all();
        $selectedTemplate = (string) ($settings['ui_template'] ?? 'default');

        wp_localize_script('easy-auth-login', 'EasyAuthLoginData', [
            'restUrl' => esc_url_raw(rest_url('easy-auth/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'turnstileSiteKey' => (string) ($settings['turnstile_site_key'] ?? ''),
            'passwordToggleEnabled' => ! empty($settings['password_visibility_toggle']),
            'template' => $selectedTemplate,
            'buttonColor' => (string) ($settings['button_color'] ?? '#146ef5'),
            'buttonTextColor' => (string) ($settings['button_text_color'] ?? '#ffffff'),
            'messages' => [
                'networkError' => __('Request failed. Please try again.', 'easy-auth-login'),
            ],
        ]);
    }
}
