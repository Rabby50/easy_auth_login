<?php

namespace EasyAuthLogin\Auth;

use EasyAuthLogin\Auth\Providers\GoogleProvider;
use EasyAuthLogin\Auth\Providers\GitHubProvider;
use EasyAuthLogin\Auth\Services\SocialAccountLinker;
use EasyAuthLogin\Auth\Services\AvatarSync;
use EasyAuthLogin\Support\Options;

if (! defined('ABSPATH')) {
    exit;
}

class OAuthCallbackHandler
{
    public function register(): void
    {
        add_action('init', [$this, 'handleCallback']);
    }

    public function handleCallback(): void
    {
        $provider = sanitize_key($_GET['easy_auth_provider'] ?? '');
        $code = sanitize_text_field((string) ($_GET['code'] ?? ''));
        $state = sanitize_text_field((string) ($_GET['state'] ?? ''));

        if (empty($provider) || empty($code) || empty($state)) {
            return;
        }

        if (! $this->verifyState($state)) {
            $this->error(__('Invalid OAuth state. Please try again.', 'easy-auth-login'));
            return;
        }

        $providerInstance = $this->getProviderInstance($provider);
        if (! $providerInstance) {
            $this->error(__('Provider is not configured.', 'easy-auth-login'));
            return;
        }

        $redirectUri = $this->getCallbackUrl($provider);
        $tokenData = $providerInstance->exchangeCodeForToken($code, $redirectUri);

        if (! $tokenData) {
            $this->error(__('Failed to exchange code for token.', 'easy-auth-login'));
            return;
        }

        $userInfo = $providerInstance->getUserInfo($tokenData['access_token']);
        if (! $userInfo) {
            $this->error(__('Failed to retrieve user information.', 'easy-auth-login'));
            return;
        }

        $linker = new SocialAccountLinker();
        $existingUser = $linker->getUserByProviderAccount($provider, $userInfo['provider_id']);

        if ($existingUser) {
            // User exists, link and login
            $this->loginUser($existingUser->ID, $provider, $userInfo);
        } else {
            // Create new user from social info
            $userId = $linker->createUserFromSocialInfo($userInfo, $provider);
            if ($userId) {
                $this->loginUser($userId, $provider, $userInfo);
            } else {
                $this->error(__('Failed to create user account.', 'easy-auth-login'));
            }
        }
    }

    public function getAuthorizationUrl(string $provider): string
    {
        $providerInstance = $this->getProviderInstance($provider);
        if (! $providerInstance) {
            return '';
        }

        $state = wp_generate_password(32, false, false);
        set_transient('easy_auth_oauth_state_' . $state, 1, 15 * MINUTE_IN_SECONDS);

        $redirectUri = $this->getCallbackUrl($provider);

        return $providerInstance->getAuthorizationUrl($redirectUri, $state);
    }

    private function getProviderInstance(string $provider)
    {
        $settings = Options::all();

        switch ($provider) {
            case 'google':
                $clientId = (string) ($settings['google_client_id'] ?? '');
                $clientSecret = (string) ($settings['google_client_secret'] ?? '');
                if (empty($clientId) || empty($clientSecret)) {
                    return null;
                }
                return new GoogleProvider($clientId, $clientSecret);

            case 'github':
                $clientId = (string) ($settings['github_client_id'] ?? '');
                $clientSecret = (string) ($settings['github_client_secret'] ?? '');
                if (empty($clientId) || empty($clientSecret)) {
                    return null;
                }
                return new GitHubProvider($clientId, $clientSecret);

            default:
                return null;
        }
    }

    private function getCallbackUrl(string $provider): string
    {
        return add_query_arg([
            'easy_auth_oauth_callback' => '1',
            'easy_auth_provider' => $provider,
        ], home_url('/'));
    }

    private function verifyState(string $state): bool
    {
        $transient = get_transient('easy_auth_oauth_state_' . $state);
        if (! $transient) {
            return false;
        }

        delete_transient('easy_auth_oauth_state_' . $state);
        return true;
    }

    private function loginUser(int $userId, string $provider, array $userInfo): void
    {
        // Sync avatar
        $avatar = new AvatarSync();
        if (! empty($userInfo['picture'])) {
            $avatar->downloadAndSetAvatar($userId, $userInfo['picture']);
        }

        // Set current user and auth cookie
        wp_set_current_user($userId);
        wp_set_auth_cookie($userId, true);

        // Determine redirect URL
        $redirectTo = '';
        if (! empty($_GET['state'])) {
            // State might contain redirect info (advanced scenario)
        }

        $redirectResolver = new RedirectResolver();
        $redirectUrl = $redirectTo ?: $redirectResolver->resolve('login');

        wp_safe_redirect($redirectUrl);
        exit;
    }

    private function error(string $message): void
    {
        wp_die(
            esc_html($message),
            esc_html__('OAuth Error', 'easy-auth-login'),
            ['response' => 400]
        );
    }
}
