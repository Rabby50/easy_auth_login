<?php

namespace EasyAuthLogin\Frontend;

if (! defined('ABSPATH')) {
    exit;
}

class Block
{
    public function register(): void
    {
        add_action('init', [$this, 'registerBlock']);
    }

    public function registerBlock(): void
    {
        wp_register_script(
            'easy-auth-login-block-editor',
            EASY_AUTH_LOGIN_URL . 'assets/js/easy-auth-login-block.js',
            ['wp-blocks', 'wp-element', 'wp-editor'],
            EASY_AUTH_LOGIN_VERSION,
            true
        );

        register_block_type('easy-auth/login-form', [
            'editor_script' => 'easy-auth-login-block-editor',
            'render_callback' => [$this, 'renderBlock'],
        ]);
    }

    public function renderBlock(array $attributes = []): string
    {
        return do_shortcode('[easy_auth_login]');
    }
}
