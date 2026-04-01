<?php

namespace EasyAuthLogin;

use EasyAuthLogin\Admin\SettingsPage;
use EasyAuthLogin\Auth\AuthController;
use EasyAuthLogin\Auth\ForceLoginRedirect;
use EasyAuthLogin\Auth\MagicLinkController;
use EasyAuthLogin\Auth\OAuthCallbackHandler;
use EasyAuthLogin\Auth\OAuthController;
use EasyAuthLogin\Auth\RateLimitService;
use EasyAuthLogin\Frontend\Assets;
use EasyAuthLogin\Frontend\AuthShortcode;
use EasyAuthLogin\Frontend\Block;
use EasyAuthLogin\Support\GmailSmtp;

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
        (new GmailSmtp())->register();

        $rateLimit = new RateLimitService();
        (new AuthController($rateLimit))->registerRoutes();
        (new MagicLinkController())->registerRoutes();
        (new OAuthCallbackHandler())->register();
        (new OAuthController())->registerRoutes();
        (new ForceLoginRedirect())->register();

        (new Assets())->register();
        (new AuthShortcode())->register();
        (new Block())->register();
    }
}
