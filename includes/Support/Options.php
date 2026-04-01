<?php

namespace EasyAuthLogin\Support;

if (! defined('ABSPATH')) {
    exit;
}

class Options
{
    public static function all(): array
    {
        $options = get_option(EASY_AUTH_LOGIN_OPTION_KEY, []);
        return is_array($options) ? $options : [];
    }

    public static function get(string $key, $default = null)
    {
        $options = self::all();
        return $options[$key] ?? $default;
    }

    public static function update(array $newOptions): bool
    {
        return update_option(EASY_AUTH_LOGIN_OPTION_KEY, $newOptions);
    }
}
