<?php

namespace EasyAuthLogin\Frontend;

use EasyAuthLogin\Support\Options;

if (! defined('ABSPATH')) {
    exit;
}

class AuthShortcode
{
    private const TEMPLATE_DEFAULT = 'default';
    private const TEMPLATE_SPLIT_PANEL = 'split_panel';
    private const TEMPLATE_GLASS_WAVE = 'glass_wave';

    public function register(): void
    {
        add_shortcode('easy_auth_login', [$this, 'render']);
    }

    public function render(array $attributes = []): string
    {
        $settings = Options::all();
        $template = $this->getTemplateSlug((string) ($settings['ui_template'] ?? self::TEMPLATE_DEFAULT));

        wp_enqueue_style('easy-auth-login');
        wp_enqueue_style('easy-auth-login-template-' . $template);
        wp_enqueue_script('easy-auth-login');

        $redirectTo = wp_validate_redirect(rawurldecode((string) ($_GET['redirect_to'] ?? '')), '');

        if (is_user_logged_in() && ! empty($settings['already_logged_in_view'])) {
            $currentUser = wp_get_current_user();

            return sprintf(
                '<div class="easy-auth-authenticated"><p>%s</p><button class="easy-auth-btn" data-easy-auth-logout="1">%s</button></div>',
                esc_html(sprintf(__('Welcome back, %s', 'easy-auth-login'), $currentUser->display_name ?: $currentUser->user_login)),
                esc_html__('Logout', 'easy-auth-login')
            );
        }

        if ($template === self::TEMPLATE_SPLIT_PANEL) {
            return $this->renderSplitPanelTemplate($redirectTo);
        }

        if ($template === self::TEMPLATE_GLASS_WAVE) {
            return $this->renderGlassWaveTemplate($redirectTo);
        }

        return $this->renderDefaultTemplate($redirectTo);
    }

    private function renderDefaultTemplate(string $redirectTo): string
    {
        ob_start();
        ?>
        <div class="easy-auth-shell" data-easy-auth-root="1" data-easy-auth-template="default">
            <?php $this->renderTabs(); ?>

            <div class="easy-auth-panels">
                <?php $this->renderLoginPanel($redirectTo); ?>
                <?php $this->renderRegisterPanel($redirectTo); ?>
                <?php $this->renderForgotPanel(); ?>
                <?php $this->renderMagicPanel($redirectTo); ?>
            </div>

            <?php $this->renderSocialButtons(); ?>

            <p class="easy-auth-message" data-easy-auth-message="1" aria-live="polite"></p>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function renderSplitPanelTemplate(string $redirectTo): string
    {
        ob_start();
        ?>
        <div class="easy-auth-shell easy-auth-shell--split" data-easy-auth-root="1" data-easy-auth-template="split_panel">
            <div class="easy-auth-template-grid">
                <section class="easy-auth-template-form">
                    <?php $this->renderTabs(); ?>

                    <div class="easy-auth-panels">
                        <?php $this->renderLoginPanel($redirectTo); ?>
                        <?php $this->renderRegisterPanel($redirectTo); ?>
                        <?php $this->renderForgotPanel(); ?>
                        <?php $this->renderMagicPanel($redirectTo); ?>
                    </div>

                    <?php $this->renderSocialButtons(); ?>

                    <p class="easy-auth-message" data-easy-auth-message="1" aria-live="polite"></p>
                </section>

                <aside class="easy-auth-template-side" aria-hidden="true">
                    <h3><?php esc_html_e('Welcome Back!', 'easy-auth-login'); ?></h3>
                    <p><?php esc_html_e('We are happy to have you with us again. If you need anything, we are here to help.', 'easy-auth-login'); ?></p>
                </aside>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function renderGlassWaveTemplate(string $redirectTo): string
    {
        ob_start();
        ?>
        <div class="easy-auth-shell easy-auth-shell--glass" data-easy-auth-root="1" data-easy-auth-template="glass_wave">
            <div class="easy-auth-glass-bg" aria-hidden="true"></div>

            <?php $this->renderTabs(); ?>

            <div class="easy-auth-panels">
                <?php $this->renderLoginPanel($redirectTo); ?>
                <?php $this->renderRegisterPanel($redirectTo); ?>
                <?php $this->renderForgotPanel(); ?>
                <?php $this->renderMagicPanel($redirectTo); ?>
            </div>

            <?php $this->renderSocialButtons(); ?>

            <p class="easy-auth-message" data-easy-auth-message="1" aria-live="polite"></p>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function renderTabs(): void
    {
        ?>
        <nav class="easy-auth-tabs">
            <button type="button" class="easy-auth-tab is-active" data-tab="login"><?php esc_html_e('Login', 'easy-auth-login'); ?></button>
            <button type="button" class="easy-auth-tab" data-tab="register"><?php esc_html_e('Register', 'easy-auth-login'); ?></button>
            <button type="button" class="easy-auth-tab" data-tab="forgot"><?php esc_html_e('Forgot', 'easy-auth-login'); ?></button>
            <button type="button" class="easy-auth-tab" data-tab="magic"><?php esc_html_e('Magic Link', 'easy-auth-login'); ?></button>
        </nav>
        <?php
    }

    private function renderSocialButtons(): void
    {
        ?>
        <div class="easy-auth-social">
            <button type="button" class="easy-auth-social-btn" data-easy-auth-oauth="google"><?php esc_html_e('Continue with Google', 'easy-auth-login'); ?></button>
            <button type="button" class="easy-auth-social-btn" data-easy-auth-oauth="github"><?php esc_html_e('Continue with GitHub', 'easy-auth-login'); ?></button>
        </div>
        <?php
    }

    private function getTemplateSlug(string $template): string
    {
        $allowed = [
            self::TEMPLATE_DEFAULT,
            self::TEMPLATE_SPLIT_PANEL,
            self::TEMPLATE_GLASS_WAVE,
        ];

        return in_array($template, $allowed, true) ? $template : self::TEMPLATE_DEFAULT;
    }

    private function renderLoginPanel(string $redirectTo = ''): void
    {
        ?>
        <section class="easy-auth-panel is-active" data-panel="login">
            <form class="easy-auth-form" data-endpoint="/login">
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirectTo); ?>" />
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

    private function renderRegisterPanel(string $redirectTo = ''): void
    {
        ?>
        <section class="easy-auth-panel" data-panel="register">
            <form class="easy-auth-form" data-endpoint="/register">
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirectTo); ?>" />
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

    private function renderMagicPanel(string $redirectTo = ''): void
    {
        ?>
        <section class="easy-auth-panel" data-panel="magic">
            <form class="easy-auth-form" data-endpoint="/magic-link/request">
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirectTo); ?>" />
                <input type="email" name="email" placeholder="<?php esc_attr_e('Email address', 'easy-auth-login'); ?>" required />
                <button type="submit" class="easy-auth-btn"><?php esc_html_e('Email Magic Link', 'easy-auth-login'); ?></button>
            </form>
        </section>
        <?php
    }
}
