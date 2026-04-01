<?php

namespace EasyAuthLogin\Auth\Providers;

if (! defined('ABSPATH')) {
    exit;
}

abstract class BaseProvider
{
    protected string $clientId = '';
    protected string $clientSecret = '';

    public function __construct(string $clientId = '', string $clientSecret = '')
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    abstract public function getAuthorizationUrl(string $redirectUri, string $state): string;

    abstract public function exchangeCodeForToken(string $code, string $redirectUri): ?array;

    abstract public function getUserInfo(string $accessToken): ?array;

    abstract public function getProviderName(): string;

    abstract public function getMetaKeyForProviderId(): string;

    protected static function makeRequest(string $url, array $options = []): ?array
    {
        $defaultOptions = [
            'timeout' => 10,
            'sslverify' => true,
        ];

        $response = wp_remote_get($url, array_merge($defaultOptions, $options));

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    protected static function makePostRequest(string $url, array $body = [], array $headers = []): ?array
    {
        $options = [
            'timeout' => 10,
            'sslverify' => true,
            'body' => wp_json_encode($body),
            'headers' => array_merge(['Content-Type' => 'application/json'], $headers),
        ];

        $response = wp_remote_post($url, $options);

        if (is_wp_error($response)) {
            return null;
        }

        $responseBody = wp_remote_retrieve_body($response);
        return json_decode($responseBody, true);
    }
}
