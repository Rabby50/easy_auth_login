<?php

namespace EasyAuthLogin\Frontend;

if (! defined('ABSPATH')) {
    exit;
}

class AuthShortcode
{
    public function register(): void
    {
        add_shortcode('easy_auth_login', [$this, 'render']);
    }

    public function render(array $attributes = []): string
    {
        wp_enqueue_style('easy-auth-login');
        wp_enqueue_script('easy-auth-login');

        if (is_user_logged_in()) {
            $currentUser = wp_get_current_user();

            return sprintf(
                '<div class="easy-auth-authenticated"><p>%s</p><button class="easy-auth-btn" data-easy-auth-logout="1">%s</button></div>',
                esc_html(sprintf(__('Welcome back, %s', 'easy-auth-login'), $currentUser->display_name ?: $currentUser->user_login)),
                esc_html__('Logout', 'easy-auth-login')
            );
        }

        ob_start();
        ?>
        <div class="easy-auth-shell" data-easy-auth-root="1">
            <nav class="easy-auth-tabs">
                <button type="button" class="easy-auth-tab is-active" data-tab="login"><?php esc_html_e('Login', 'easy-auth-login'); ?></button>
                <button type="button" class="easy-auth-tab" data-tab="register"><?php esc_html_e('Register', 'easy-auth-login'); ?></button>
                <button type="button" class="easy-auth-tab" data-tab="forgot"><?php esc_html_e('Forgot', 'easy-auth-login'); ?></button>
                <button type="button" class="easy-auth-tab" data-tab="magic"><?php esc_html_e('Magic Link', 'easy-auth-login'); ?></button>
            </nav>

            <div class="easy-auth-panels">
                <?php $this->renderLoginPanel(); ?>
                <?php $this->renderRegisterPanel(); ?>
                <?php $this->renderForgotPanel(); ?>
                <?php $this->renderMagicPanel(); ?>
            </div>

            <div class="easy-auth-social">
                <button type="button" class="easy-auth-social-btn" disabled><?php esc_html_e('Continue with Google', 'easy-auth-login'); ?></button>
                <button type="button" class="easy-auth-social-btn" disabled><?php esc_html_e('Continue with GitHub', 'easy-auth-login'); ?></button>
            </div>

            <p class="easy-auth-message" data-easy-auth-message="1" aria-live="polite"></p>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function renderLoginPanel(): void
    {
        ?>
        <section class="easy-auth-panel is-active" data-panel="login">
            <form class="easy-auth-form" data-endpoint="/login">
                <input type="text" name="username" placeholder="<?php esc_attr_e('Username or email', 'easy-auth-login'); ?>" required />
                <div class="easy-auth-password-wrap">
                    <input type="password" name="password" placeholder="<?php esc_attr_e('Password', 'easy-auth-login'); ?>" required />
                    <button type="button" class="easy-auth-toggle" data-password-toggle="1">Show</button>
                </div>
                <button type="submit" class="easy-auth-btn"><?php esc_html_e('Login', 'easy-auth-login'); ?></button>
            </form>
        </section>
        <?php
    }

    private function renderRegisterPanel(): void
    {
        ?>
        <section class="easy-auth-panel" data-panel="register">
            <form class="easy-auth-form" data-endpoint="/register">
                <input type="text" name="username" placeholder="<?php esc_attr_e('Username', 'easy-auth-login'); ?>" required />
                <input type="email" name="email" placeholder="<?php esc_attr_e('Email', 'easy-auth-login'); ?>" required />
                <div class="easy-auth-password-wrap">
                    <input type="password" name="password" placeholder="<?php esc_attr_e('Password', 'easy-auth-login'); ?>" required data-password-strength="1" />
                    <button type="button" class="easy-auth-toggle" data-password-toggle="1">Show</button>
                </div>
                <div class="easy-auth-strength" data-strength-bar="1"><span></span></div>
                <input type="text" name="company_name" tabindex="-1" autocomplete="off" class="easy-auth-honeypot" />
                <button type="submit" class="easy-auth-btn"><?php esc_html_e('Register', 'easy-auth-login'); ?></button>
            </form>
        </section>
        <?php
    }

    private function renderForgotPanel(): void
    {
        ?>
        <section class="easy-auth-panel" data-panel="forgot">
            <form class="easy-auth-form" data-endpoint="/forgot-password">
                <input type="text" name="user_login" placeholder="<?php esc_attr_e('Username or email', 'easy-auth-login'); ?>" required />
                <button type="submit" class="easy-auth-btn"><?php esc_html_e('Send Reset Link', 'easy-auth-login'); ?></button>
            </form>
        </section>
        <?php
    }

    private function renderMagicPanel(): void
    {
        ?>
        <section class="easy-auth-panel" data-panel="magic">
            <form class="easy-auth-form" data-endpoint="/magic-link/request">
                <input type="email" name="email" placeholder="<?php esc_attr_e('Email address', 'easy-auth-login'); ?>" required />
                <button type="submit" class="easy-auth-btn"><?php esc_html_e('Email Magic Link', 'easy-auth-login'); ?></button>
            </form>
        </section>
        <?php
    }
}
