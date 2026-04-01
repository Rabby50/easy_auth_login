<?php

namespace EasyAuthLogin\Auth;

use EasyAuthLogin\Support\Options;

if (! defined('ABSPATH')) {
    exit;
}

class RedirectResolver
{
    public function resolve(string $flow): string
    {
        $pageId = (int) Options::get('redirect_' . $flow . '_page_id', 0);

        if ($pageId > 0) {
            $url = get_permalink($pageId);
            if ($url) {
                return $url;
            }
        }

        if ($flow === 'logout') {
            return home_url('/');
        }

        if ($flow === 'login' && current_user_can('administrator')) {
            return admin_url();
        }

        return home_url('/');
    }
}
