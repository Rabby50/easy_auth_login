<?php

namespace EasyAuthLogin\Auth;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

class OAuthController
{
    public function registerRoutes(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('easy-auth/v1', '/oauth/authorize', [
                'methods' => 'POST',
                'callback' => [$this, 'authorize'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    public function authorize(WP_REST_Request $request)
    {
        $provider = sanitize_key((string) $request->get_param('provider'));

        if (! in_array($provider, ['google', 'github'], true)) {
            return new WP_Error('easy_auth_invalid_provider', __('Invalid provider.', 'easy-auth-login'), ['status' => 400]);
        }

        $oauthHandler = new OAuthCallbackHandler();
        $authUrl = $oauthHandler->getAuthorizationUrl($provider);

        if (empty($authUrl)) {
            return new WP_Error('easy_auth_provider_not_configured', __('Provider is not configured.', 'easy-auth-login'), ['status' => 400]);
        }

        return new WP_REST_Response([
            'success' => true,
            'auth_url' => $authUrl,
        ], 200);
    }
}
