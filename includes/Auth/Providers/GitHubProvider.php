<?php

namespace EasyAuthLogin\Auth\Providers;

if (! defined('ABSPATH')) {
    exit;
}

class GitHubProvider extends BaseProvider
{
    private const OAUTH_ENDPOINT = 'https://github.com/login/oauth/authorize';
    private const TOKEN_ENDPOINT = 'https://github.com/login/oauth/access_token';
    private const USERINFO_ENDPOINT = 'https://api.github.com/user';

    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        return add_query_arg([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'user:email',
            'state' => $state,
        ], self::OAUTH_ENDPOINT);
    }

    public function exchangeCodeForToken(string $code, string $redirectUri): ?array
    {
        $response = self::makePostRequest(self::TOKEN_ENDPOINT, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ], ['Accept' => 'application/json']);

        if (! $response || empty($response['access_token'])) {
            return null;
        }

        return [
            'access_token' => $response['access_token'],
            'refresh_token' => null,
            'expires_in' => null,
        ];
    }

    public function getUserInfo(string $accessToken): ?array
    {
        $response = self::makeRequest(self::USERINFO_ENDPOINT, [
            'headers' => [
                'Authorization' => 'token ' . $accessToken,
                'Accept' => 'application/json',
            ],
        ]);

        if (! $response || empty($response['id'])) {
            return null;
        }

        return [
            'provider_id' => (string) $response['id'],
            'email' => $response['email'] ?? '',
            'name' => $response['name'] ?? $response['login'] ?? '',
            'picture' => $response['avatar_url'] ?? '',
        ];
    }

    public function getProviderName(): string
    {
        return 'github';
    }

    public function getMetaKeyForProviderId(): string
    {
        return 'easy_auth_github_id';
    }
}
