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
        'templates' => 'Templates',
        'gmail_smpt' => 'Gmail Smpt',
        'styling' => 'Styling',
        'security' => 'Security',
    ];

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('wp_ajax_easy_auth_search_pages', [$this, 'searchPages']);
        add_action('wp_ajax_easy_auth_send_test_email', [$this, 'sendTestEmail']);
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
            'gmail_smtp_enabled',
            'gmail_smtp_auth',
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

        $out['enable_redirect_to_next'] = ! empty($input['enable_redirect_to_next']);
        $out['force_login_page_id'] = max(0, (int) ($input['force_login_page_id'] ?? 0));

        $protectedIds = $input['force_login_protected_page_ids'] ?? [];
        if (! is_array($protectedIds)) {
            $protectedIds = array_filter(array_map('trim', explode(',', (string) $protectedIds)));
        }
        $protectedIds = array_values(array_unique(array_filter(array_map(static function ($id): int {
            return max(0, (int) $id);
        }, $protectedIds))));
        $out['force_login_protected_page_ids'] = $protectedIds;

        $out['google_client_id'] = sanitize_text_field((string) ($input['google_client_id'] ?? ''));
        $out['google_client_secret'] = sanitize_text_field((string) ($input['google_client_secret'] ?? ''));
        $out['github_client_id'] = sanitize_text_field((string) ($input['github_client_id'] ?? ''));
        $out['github_client_secret'] = sanitize_text_field((string) ($input['github_client_secret'] ?? ''));

        $out['style_theme'] = in_array(($input['style_theme'] ?? 'inherit'), ['light', 'dark', 'inherit'], true)
            ? $input['style_theme']
            : 'inherit';
        $out['ui_template'] = in_array(($input['ui_template'] ?? 'default'), ['default', 'split_panel', 'glass_wave', 'neo_3d_toggle'], true)
            ? $input['ui_template']
            : 'default';
        $out['button_color'] = sanitize_hex_color((string) ($input['button_color'] ?? '#146ef5')) ?: '#146ef5';
        $out['button_text_color'] = sanitize_hex_color((string) ($input['button_text_color'] ?? '#ffffff')) ?: '#ffffff';
        $out['custom_css'] = wp_strip_all_tags((string) ($input['custom_css'] ?? ''));

        $out['turnstile_site_key'] = sanitize_text_field((string) ($input['turnstile_site_key'] ?? ''));
        $out['turnstile_secret_key'] = sanitize_text_field((string) ($input['turnstile_secret_key'] ?? ''));
        $out['lockout_max_retries'] = max(1, (int) ($input['lockout_max_retries'] ?? 5));
        $out['lockout_minutes'] = max(1, (int) ($input['lockout_minutes'] ?? 15));

        $out['gmail_smtp_host'] = sanitize_text_field((string) ($input['gmail_smtp_host'] ?? 'smtp.gmail.com'));
        $out['gmail_smtp_port'] = max(1, (int) ($input['gmail_smtp_port'] ?? 587));
        $out['gmail_smtp_encryption'] = in_array(($input['gmail_smtp_encryption'] ?? 'tls'), ['tls', 'ssl', 'none'], true)
            ? $input['gmail_smtp_encryption']
            : 'tls';
        $out['gmail_smtp_username'] = sanitize_text_field((string) ($input['gmail_smtp_username'] ?? ''));
        $out['gmail_smtp_password'] = sanitize_text_field((string) ($input['gmail_smtp_password'] ?? ''));
        $out['gmail_from_email'] = sanitize_email((string) ($input['gmail_from_email'] ?? ''));
        $out['gmail_from_name'] = sanitize_text_field((string) ($input['gmail_from_name'] ?? ''));
        $out['custom_site_name'] = sanitize_text_field((string) ($input['custom_site_name'] ?? ''));
        $out['registration_thank_you_message'] = sanitize_textarea_field((string) ($input['registration_thank_you_message'] ?? ''));
        $out['forgot_otp_expiry_seconds'] = max(30, min(300, (int) ($input['forgot_otp_expiry_seconds'] ?? 45)));
        $out['forgot_otp_max_requests'] = max(1, min(10, (int) ($input['forgot_otp_max_requests'] ?? 3)));
        $out['forgot_otp_window_minutes'] = max(5, min(1440, (int) ($input['forgot_otp_window_minutes'] ?? 30)));

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
            'testEmailNonce' => wp_create_nonce('easy_auth_admin_test_email'),
            'noResults' => __('No pages found.', 'easy-auth-login'),
            'searchPlaceholder' => __('Search page by title...', 'easy-auth-login'),
            'clearText' => __('Clear', 'easy-auth-login'),
            'sendingText' => __('Sending...', 'easy-auth-login'),
            'sendTestText' => __('Send Test Email', 'easy-auth-login'),
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

    public function sendTestEmail(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized.', 'easy-auth-login')], 403);
        }

        check_ajax_referer('easy_auth_admin_test_email', 'nonce');

        $recipient = sanitize_email((string) ($_POST['recipient_email'] ?? ''));
        if (! is_email($recipient)) {
            wp_send_json_error(['message' => __('Please enter a valid recipient email.', 'easy-auth-login')], 400);
        }

        $settings = Options::all();
        $siteName = sanitize_text_field((string) ($settings['custom_site_name'] ?? ''));
        if ($siteName === '') {
            $siteName = (string) get_bloginfo('name');
        }

        $subject = sprintf(__('[%s] SMTP Test Email', 'easy-auth-login'), $siteName);
        $message = sprintf(
            __('This is a test email from Easy Auth Login Gmail Smpt settings at %s.', 'easy-auth-login'),
            current_time('mysql')
        );

        $sent = wp_mail($recipient, $subject, $message);
        if (! $sent) {
            wp_send_json_error(['message' => __('Test email could not be sent. Check SMTP settings and app password.', 'easy-auth-login')], 500);
        }

        wp_send_json_success(['message' => __('Test email sent successfully.', 'easy-auth-login')]);
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
                $this->checkboxField('enable_redirect_to_next', 'Enable Redirect To Next Page', $settings);
                $this->pagePickerField('force_login_page_id', 'Force Login Redirect Page', $settings);
                $this->multiPagePickerField('force_login_protected_page_ids', 'Protected Pages (Force Login)', $settings);
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

            case 'templates':
                $this->selectField('ui_template', 'Login Form Template', $settings, [
                    'default' => 'Default (Current Design)',
                    'split_panel' => 'Split Panel (CodePen Inspired)',
                    'glass_wave' => 'Glass Wave',
                    'neo_3d_toggle' => 'Neo 3D Toggle (Dark/Light)',
                ]);
                break;

            case 'gmail_smpt':
                $this->checkboxField('gmail_smtp_enabled', 'Enable Gmail SMTP', $settings);
                $this->textField('gmail_smtp_host', 'SMTP Host', $settings, 'smtp.gmail.com');
                $this->numberField('gmail_smtp_port', 'SMTP Port', $settings, 1, 65535);
                $this->selectField('gmail_smtp_encryption', 'Encryption', $settings, [
                    'tls' => 'TLS',
                    'ssl' => 'SSL',
                    'none' => 'None',
                ]);
                $this->checkboxField('gmail_smtp_auth', 'SMTP Authentication', $settings);
                $this->textField('gmail_smtp_username', 'SMTP Username (Gmail address)', $settings);
                $this->passwordField('gmail_smtp_password', 'SMTP Password / App Password', $settings);
                $this->textField('gmail_from_email', 'From Email', $settings);
                $this->textField('gmail_from_name', 'From Name', $settings);
                $this->textField('custom_site_name', 'Site Name (Email Template)', $settings);
                $this->textareaField('registration_thank_you_message', 'Registration Thank You Message', $settings);
                $this->numberField('forgot_otp_expiry_seconds', 'Forgot OTP Expiry (seconds)', $settings, 30, 300);
                $this->numberField('forgot_otp_max_requests', 'Forgot OTP Max Requests', $settings, 1, 10);
                $this->numberField('forgot_otp_window_minutes', 'Forgot OTP Window (minutes)', $settings, 5, 1440);
                $this->testEmailField('SMTP Test Email');
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

    private function multiPagePickerField(string $key, string $label, array $settings): void
    {
        $ids = $settings[$key] ?? [];
        if (! is_array($ids)) {
            $ids = [];
        }

        $ids = array_values(array_filter(array_map('intval', $ids)));
        $selected = [];

        foreach ($ids as $id) {
            $page = get_post($id);
            if ($page && $page->post_type === 'page') {
                $selected[] = [
                    'id' => $id,
                    'title' => get_the_title($page) ?: ('#' . $id),
                ];
            }
        }
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <input type="hidden" name="<?php echo esc_attr(EASY_AUTH_LOGIN_OPTION_KEY . '[' . $key . ']'); ?>" value="<?php echo esc_attr(implode(',', array_column($selected, 'id'))); ?>" data-easy-auth-page-ids="1" />
                <input type="text" class="regular-text" value="" placeholder="<?php esc_attr_e('Search page by title...', 'easy-auth-login'); ?>" data-easy-auth-pages-search="1" autocomplete="off" />
                <div class="easy-auth-page-picker-selected" data-easy-auth-pages-selected="1" style="margin-top:8px;">
                    <?php foreach ($selected as $item) : ?>
                        <button type="button" class="button" style="margin: 0 6px 6px 0;" data-selected-page-id="<?php echo esc_attr((string) $item['id']); ?>" data-selected-page-title="<?php echo esc_attr($item['title']); ?>">
                            <?php echo esc_html($item['title'] . ' (' . $item['id'] . ')'); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="easy-auth-page-picker-results" data-easy-auth-pages-results="1" style="margin-top:8px; max-width:420px;"></div>
                <p class="description"><?php esc_html_e('Users visiting these pages while logged out will be redirected to the Force Login Redirect Page, then returned after authentication.', 'easy-auth-login'); ?></p>
            </td>
        </tr>
        <?php
    }

    private function testEmailField(string $label): void
    {
        $defaultRecipient = (string) get_option('admin_email', '');
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <input type="email" class="regular-text" value="<?php echo esc_attr($defaultRecipient); ?>" data-easy-auth-test-email="1" placeholder="name@example.com" />
                <button type="button" class="button button-secondary" data-easy-auth-send-test-email="1"><?php esc_html_e('Send Test Email', 'easy-auth-login'); ?></button>
                <p class="description" data-easy-auth-test-email-status="1"></p>
            </td>
        </tr>
        <?php
    }
}
