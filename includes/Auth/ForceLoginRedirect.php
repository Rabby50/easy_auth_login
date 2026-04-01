<?php

namespace EasyAuthLogin\Auth;

use EasyAuthLogin\Support\Options;

if (! defined('ABSPATH')) {
    exit;
}

class ForceLoginRedirect
{
    public function register(): void
    {
        add_action('template_redirect', [$this, 'maybeRedirect']);
    }

    public function maybeRedirect(): void
    {
        if (is_user_logged_in() || is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        if (! Options::get('enable_redirect_to_next', false)) {
            return;
        }

        $loginPageId = (int) Options::get('force_login_page_id', 0);
        if ($loginPageId <= 0) {
            return;
        }

        $protectedIds = Options::get('force_login_protected_page_ids', []);
        if (! is_array($protectedIds)) {
            $protectedIds = array_filter(array_map('trim', explode(',', (string) $protectedIds)));
        }

        $protectedIds = array_values(array_filter(array_map('intval', $protectedIds)));
        if (empty($protectedIds)) {
            return;
        }

        $currentPageId = get_queried_object_id();
        if ($currentPageId <= 0 || $currentPageId === $loginPageId) {
            return;
        }

        if (! in_array($currentPageId, $protectedIds, true)) {
            return;
        }

        $loginUrl = get_permalink($loginPageId);
        if (! $loginUrl) {
            return;
        }

        $requestedUrl = get_permalink($currentPageId);
        if (empty($requestedUrl)) {
            $requestedUrl = home_url('/');
        }

        $redirectUrl = add_query_arg('redirect_to', $requestedUrl, $loginUrl);

        wp_safe_redirect($redirectUrl);
        exit;
    }
}
