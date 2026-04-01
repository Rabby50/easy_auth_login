<?php

namespace EasyAuthLogin\Admin;

use EasyAuthLogin\Support\Options;

if (! defined('ABSPATH')) {
    exit;
}

class SettingsPage
{
    private array $tabs = [
        'general' => 'General',
        'redirection' => 'Redirection',
        'social' => 'Social Login',
        'styling' => 'Styling',
        'security' => 'Security',
    ];

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('wp_ajax_easy_auth_search_pages', [$this, 'searchPages']);
    }

    public function addMenu(): void
    {
        add_menu_page(
            __('Easy Auth Login', 'easy-auth-login'),
            __('Easy Auth Login', 'easy-auth-login'),
            'manage_options',
            'easy-auth-login',
            [$this, 'renderPage'],
            'dashicons-lock',
            62
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            'easy_auth_login_settings_group',
            EASY_AUTH_LOGIN_OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitizeSettings'],
                'default' => [],
            ]
        );
    }

    public function sanitizeSettings(array $input): array
    {
        $old = Options::all();
        $out = [];

        $boolKeys = [
            'enable_ajax_login',
            'enable_ajax_registration',
            'already_logged_in_view',
            'password_visibility_toggle',
            'google_enabled',
            'github_enabled',
            'honeypot_enabled',
            'turnstile_enabled',
            'lockout_enabled',
        ];

        foreach ($boolKeys as $key) {
            $out[$key] = ! empty($input[$key]);
        }

        $out['default_login_view'] = in_array(($input['default_login_view'] ?? 'inline'), ['inline', 'modal'], true)
            ? $input['default_login_view']
            : 'inline';

        foreach (['login', 'register', 'logout'] as $flow) {
            $pageIdKey = "redirect_{$flow}_page_id";
            $out[$pageIdKey] = max(0, (int) ($input[$pageIdKey] ?? 0));
        }

        $out['google_client_id'] = sanitize_text_field((string) ($input['google_client_id'] ?? ''));
        $out['google_client_secret'] = sanitize_text_field((string) ($input['google_client_secret'] ?? ''));
        $out['github_client_id'] = sanitize_text_field((string) ($input['github_client_id'] ?? ''));
        $out['github_client_secret'] = sanitize_text_field((string) ($input['github_client_secret'] ?? ''));

        $out['style_theme'] = in_array(($input['style_theme'] ?? 'inherit'), ['light', 'dark', 'inherit'], true)
            ? $input['style_theme']
            : 'inherit';
        $out['button_color'] = sanitize_hex_color((string) ($input['button_color'] ?? '#146ef5')) ?: '#146ef5';
        $out['button_text_color'] = sanitize_hex_color((string) ($input['button_text_color'] ?? '#ffffff')) ?: '#ffffff';
        $out['custom_css'] = wp_strip_all_tags((string) ($input['custom_css'] ?? ''));

        $out['turnstile_site_key'] = sanitize_text_field((string) ($input['turnstile_site_key'] ?? ''));
        $out['turnstile_secret_key'] = sanitize_text_field((string) ($input['turnstile_secret_key'] ?? ''));
        $out['lockout_max_retries'] = max(1, (int) ($input['lockout_max_retries'] ?? 5));
        $out['lockout_minutes'] = max(1, (int) ($input['lockout_minutes'] ?? 15));

        return array_merge($old, $out);
    }

    public function enqueueAdminAssets(string $hook): void
    {
        if ($hook !== 'toplevel_page_easy-auth-login') {
            return;
        }

        wp_enqueue_script(
            'easy-auth-admin-settings',
            EASY_AUTH_LOGIN_URL . 'assets/js/easy-auth-admin-settings.js',
            [],
            EASY_AUTH_LOGIN_VERSION,
            true
        );

        wp_localize_script('easy-auth-admin-settings', 'EasyAuthAdminData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('easy_auth_admin_page_search'),
            'noResults' => __('No pages found.', 'easy-auth-login'),
            'searchPlaceholder' => __('Search page by title...', 'easy-auth-login'),
            'clearText' => __('Clear', 'easy-auth-login'),
        ]);
    }

    public function searchPages(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized.', 'easy-auth-login')], 403);
        }

        check_ajax_referer('easy_auth_admin_page_search', 'nonce');

        $term = sanitize_text_field((string) ($_GET['q'] ?? ''));

        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => 20,
            'orderby' => 'title',
            'order' => 'ASC',
            's' => $term,
        ]);

        $results = [];
        foreach ($pages as $page) {
            $results[] = [
                'id' => (int) $page->ID,
                'title' => get_the_title($page),
                'url' => get_permalink($page),
            ];
        }

        wp_send_json_success($results);
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $activeTab = sanitize_key($_GET['tab'] ?? 'general');
        if (! isset($this->tabs[$activeTab])) {
            $activeTab = 'general';
        }

        $settings = Options::all();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Easy Auth Login', 'easy-auth-login'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <?php foreach ($this->tabs as $tab => $label) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=easy-auth-login&tab=' . $tab)); ?>" class="nav-tab <?php echo $activeTab === $tab ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <form method="post" action="options.php">
                <?php settings_fields('easy_auth_login_settings_group'); ?>

                <table class="form-table" role="presentation">
                    <?php $this->renderTabFields($activeTab, $settings); ?>
                </table>

                <?php submit_button(__('Save Settings', 'easy-auth-login')); ?>
            </form>
        </div>
        <?php
    }

    private function renderTabFields(string $tab, array $settings): void
    {
        switch ($tab) {
            case 'general':
                $this->checkboxField('enable_ajax_login', 'Enable AJAX Login', $settings);
                $this->checkboxField('enable_ajax_registration', 'Enable AJAX Registration', $settings);
                $this->selectField('default_login_view', 'Default Login View', $settings, [
                    'inline' => 'Inline Form',
                    'modal' => 'Modal Popup',
                ]);
                $this->checkboxField('already_logged_in_view', 'Already Logged In View', $settings);
                $this->checkboxField('password_visibility_toggle', 'Password Visibility Toggle', $settings);
                break;

            case 'redirection':
                foreach (['login' => 'Login', 'register' => 'Registration', 'logout' => 'Logout'] as $flow => $label) {
                    $this->pagePickerField("redirect_{$flow}_page_id", "After {$label} Redirect Page", $settings);
                }
                break;

            case 'social':
                $this->checkboxField('google_enabled', 'Enable Google Login', $settings);
                $this->textField('google_client_id', 'Google Client ID', $settings);
                $this->passwordField('google_client_secret', 'Google Client Secret', $settings);
                $this->checkboxField('github_enabled', 'Enable GitHub Login', $settings);
                $this->textField('github_client_id', 'GitHub Client ID', $settings);
                $this->passwordField('github_client_secret', 'GitHub Client Secret', $settings);
                break;

            case 'styling':
                $this->selectField('style_theme', 'Form Style', $settings, [
                    'light' => 'Light',
                    'dark' => 'Dark',
                    'inherit' => 'Inherit from Theme',
                ]);
                $this->colorField('button_color', 'Primary Button Color', $settings);
                $this->colorField('button_text_color', 'Primary Button Text Color', $settings);
                $this->textareaField('custom_css', 'Custom CSS', $settings);
                break;

            case 'security':
                $this->checkboxField('honeypot_enabled', 'Anti-Spam Honeypot', $settings);
                $this->checkboxField('turnstile_enabled', 'Enable Cloudflare Turnstile', $settings);
                $this->textField('turnstile_site_key', 'Turnstile Site Key', $settings);
                $this->passwordField('turnstile_secret_key', 'Turnstile Secret Key', $settings);
                $this->checkboxField('lockout_enabled', 'Enable Login Lockout', $settings);
                $this->numberField('lockout_max_retries', 'Max Retries', $settings, 1, 20);
                $this->numberField('lockout_minutes', 'Lockout Duration (minutes)', $settings, 1, 120);
                break;
        }
    }

    private function checkboxField(string $key, string $label, array $settings): void
    {
        $checked = ! empty($settings[$key]);
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="<?php echo esc_attr(EASY_AUTH_LOGIN_OPTION_KEY . '[' . $key . ']'); ?>" value="1" <?php checked($checked); ?> />
                    <?php esc_html_e('Enabled', 'easy-auth-login'); ?>
                </label>
            </td>
        </tr>
        <?php
    }

    private function selectField(string $key, string $label, array $settings, array $options): void
    {
        $value = (string) ($settings[$key] ?? '');
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <select name="<?php echo esc_attr(EASY_AUTH_LOGIN_OPTION_KEY . '[' . $key . ']'); ?>">
                    <?php foreach ($options as $optionValue => $optionLabel) : ?>
                        <option value="<?php echo esc_attr($optionValue); ?>" <?php selected($value, $optionValue); ?>><?php echo esc_html($optionLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php
    }

    private function textField(string $key, string $label, array $settings, string $placeholder = ''): void
    {
        $value = (string) ($settings[$key] ?? '');
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <input type="text" class="regular-text" name="<?php echo esc_attr(EASY_AUTH_LOGIN_OPTION_KEY . '[' . $key . ']'); ?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" />
            </td>
        </tr>
        <?php
    }

    private function passwordField(string $key, string $label, array $settings): void
    {
        $value = (string) ($settings[$key] ?? '');
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <input type="password" class="regular-text" name="<?php echo esc_attr(EASY_AUTH_LOGIN_OPTION_KEY . '[' . $key . ']'); ?>" value="<?php echo esc_attr($value); ?>" autocomplete="new-password" />
            </td>
        </tr>
        <?php
    }

    private function colorField(string $key, string $label, array $settings): void
    {
        $value = (string) ($settings[$key] ?? '');
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <input type="color" name="<?php echo esc_attr(EASY_AUTH_LOGIN_OPTION_KEY . '[' . $key . ']'); ?>" value="<?php echo esc_attr($value); ?>" />
            </td>
        </tr>
        <?php
    }

    private function textareaField(string $key, string $label, array $settings): void
    {
        $value = (string) ($settings[$key] ?? '');
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <textarea class="large-text code" rows="8" name="<?php echo esc_attr(EASY_AUTH_LOGIN_OPTION_KEY . '[' . $key . ']'); ?>"><?php echo esc_textarea($value); ?></textarea>
            </td>
        </tr>
        <?php
    }

    private function numberField(string $key, string $label, array $settings, int $min, int $max): void
    {
        $value = (int) ($settings[$key] ?? $min);
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <input type="number" min="<?php echo esc_attr((string) $min); ?>" max="<?php echo esc_attr((string) $max); ?>" name="<?php echo esc_attr(EASY_AUTH_LOGIN_OPTION_KEY . '[' . $key . ']'); ?>" value="<?php echo esc_attr((string) $value); ?>" />
            </td>
        </tr>
        <?php
    }

    private function pagePickerField(string $key, string $label, array $settings): void
    {
        $value = (int) ($settings[$key] ?? 0);
        $selectedTitle = '';
        if ($value > 0) {
            $page = get_post($value);
            if ($page && $page->post_type === 'page') {
                $selectedTitle = get_the_title($page) ?: ('#' . $value);
            }
        }
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <input type="hidden" name="<?php echo esc_attr(EASY_AUTH_LOGIN_OPTION_KEY . '[' . $key . ']'); ?>" value="<?php echo esc_attr((string) $value); ?>" data-easy-auth-page-id="1" />
                <input type="text" class="regular-text" value="<?php echo esc_attr($selectedTitle); ?>" placeholder="<?php esc_attr_e('Search page by title...', 'easy-auth-login'); ?>" data-easy-auth-page-search="1" autocomplete="off" />
                <button type="button" class="button" data-easy-auth-page-clear="1"><?php esc_html_e('Clear', 'easy-auth-login'); ?></button>
                <div class="easy-auth-page-picker-results" data-easy-auth-page-results="1" style="margin-top:8px; max-width:420px;"></div>
                <p class="description"><?php esc_html_e('Leave empty to use default redirect behavior.', 'easy-auth-login'); ?></p>
            </td>
        </tr>
        <?php
    }
}
