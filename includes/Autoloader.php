<?php

namespace EasyAuthLogin;

if (! defined('ABSPATH')) {
    exit;
}

class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([static::class, 'autoload']);
    }

    private static function autoload(string $className): void
    {
        if (strpos($className, __NAMESPACE__ . '\\') !== 0) {
            return;
        }

        $relative = str_replace(__NAMESPACE__ . '\\', '', $className);
        $relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
        $filePath = EASY_AUTH_LOGIN_PATH . 'includes/' . $relative . '.php';

        if (file_exists($filePath)) {
            require_once $filePath;
        }
    }
}
