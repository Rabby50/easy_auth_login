<?php

namespace EasyAuthLogin\Auth\Providers;

if (! defined('ABSPATH')) {
    exit;
}

class GoogleProvider extends BaseProvider
{
    private const OAUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const USERINFO_ENDPOINT = 'https://www.googleapis.com/oauth2/v2/userinfo';

    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        return add_query_arg([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
        ], self::OAUTH_ENDPOINT);
    }

    public function exchangeCodeForToken(string $code, string $redirectUri): ?array
    {
        $response = self::makePostRequest(self::TOKEN_ENDPOINT, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if (! $response || empty($response['access_token'])) {
            return null;
        }

        return [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? null,
            'expires_in' => (int) ($response['expires_in'] ?? 3600),
        ];
    }

    public function getUserInfo(string $accessToken): ?array
    {
        $response = self::makeRequest(self::USERINFO_ENDPOINT . '?access_token=' . rawurlencode($accessToken));

        if (! $response || empty($response['id'])) {
            return null;
        }

        return [
            'provider_id' => $response['id'],
            'email' => $response['email'] ?? '',
            'name' => $response['name'] ?? '',
            'picture' => $response['picture'] ?? '',
        ];
    }

    public function getProviderName(): string
    {
        return 'google';
    }

    public function getMetaKeyForProviderId(): string
    {
        return 'easy_auth_google_id';
    }
}
