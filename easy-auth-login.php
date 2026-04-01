<?php
/**
 * Plugin Name: Easy Auth Login
 * Description: Lightweight AJAX auth flows for WordPress with social-ready architecture.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Easy Auth
 * Text Domain: easy-auth-login
 */

if (! defined('ABSPATH')) {
    exit;
}

define('EASY_AUTH_LOGIN_VERSION', '0.1.0');
define('EASY_AUTH_LOGIN_FILE', __FILE__);
define('EASY_AUTH_LOGIN_PATH', plugin_dir_path(__FILE__));
define('EASY_AUTH_LOGIN_URL', plugin_dir_url(__FILE__));
define('EASY_AUTH_LOGIN_OPTION_KEY', 'easy_auth_login_settings');

require_once EASY_AUTH_LOGIN_PATH . 'includes/Autoloader.php';

EasyAuthLogin\Autoloader::register();

register_activation_hook(EASY_AUTH_LOGIN_FILE, ['EasyAuthLogin\\Plugin', 'activate']);
register_deactivation_hook(EASY_AUTH_LOGIN_FILE, ['EasyAuthLogin\\Plugin', 'deactivate']);

add_action('plugins_loaded', static function () {
    EasyAuthLogin\Plugin::instance()->boot();
});
