<?php

namespace EasyAuthLogin;

if (! defined('ABSPATH')) {
    exit;
}

class Installer
{
    public static function activate(): void
    {
        self::createLogsTable();
        self::setDefaultOptions();
    }

    private static function createLogsTable(): void
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'easy_auth_logs';
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ip_address VARCHAR(100) NOT NULL,
            username_attempted VARCHAR(190) NOT NULL,
            time DATETIME NOT NULL,
            lockout_expiry DATETIME NULL,
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY username_attempted (username_attempted),
            KEY lockout_expiry (lockout_expiry)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    private static function setDefaultOptions(): void
    {
        $defaults = [
            'enable_ajax_login' => true,
            'enable_ajax_registration' => true,
            'default_login_view' => 'inline',
            'already_logged_in_view' => true,
            'password_visibility_toggle' => true,
            'redirect_login_page_id' => 0,
            'redirect_register_page_id' => 0,
            'redirect_logout_page_id' => 0,
            'enable_redirect_to_next' => false,
            'force_login_page_id' => 0,
            'force_login_protected_page_ids' => [],
            'google_enabled' => false,
            'google_client_id' => '',
            'google_client_secret' => '',
            'github_enabled' => false,
            'github_client_id' => '',
            'github_client_secret' => '',
            'style_theme' => 'inherit',
            'button_color' => '#146ef5',
            'button_text_color' => '#ffffff',
            'custom_css' => '',
            'honeypot_enabled' => true,
            'turnstile_enabled' => false,
            'turnstile_site_key' => '',
            'turnstile_secret_key' => '',
            'lockout_enabled' => true,
            'lockout_max_retries' => 5,
            'lockout_minutes' => 15,
        ];

        if (! get_option(EASY_AUTH_LOGIN_OPTION_KEY)) {
            add_option(EASY_AUTH_LOGIN_OPTION_KEY, $defaults);
        }
    }
}
