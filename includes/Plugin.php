<?php

namespace EasyAuthLogin;

use EasyAuthLogin\Admin\SettingsPage;
use EasyAuthLogin\Auth\AuthController;
use EasyAuthLogin\Auth\ForceLoginRedirect;
use EasyAuthLogin\Auth\MagicLinkController;
use EasyAuthLogin\Auth\RateLimitService;
use EasyAuthLogin\Frontend\Assets;
use EasyAuthLogin\Frontend\AuthShortcode;
use EasyAuthLogin\Frontend\Block;

if (! defined('ABSPATH')) {
    exit;
}

class Plugin
{
    private static ?Plugin $instance = null;

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate(): void
    {
        Installer::activate();
    }

    public static function deactivate(): void
    {
        // Reserved for cleanup tasks if needed.
    }

    public function boot(): void
    {
        (new SettingsPage())->register();

        $rateLimit = new RateLimitService();
        (new AuthController($rateLimit))->registerRoutes();
        (new MagicLinkController())->registerRoutes();
        (new ForceLoginRedirect())->register();

        (new Assets())->register();
        (new AuthShortcode())->register();
        (new Block())->register();
    }
}
