<?php

namespace EasyAuthLogin\Auth\Services;

if (! defined('ABSPATH')) {
    exit;
}

class SocialAccountLinker
{
    public function linkAccountToUser(int $userId, string $provider, string $providerId): bool
    {
        $metaKey = $this->getMetaKey($provider);
        if (empty($metaKey)) {
            return false;
        }

        return (bool) update_user_meta($userId, $metaKey, sanitize_text_field($providerId));
    }

    public function getUserByProviderAccount(string $provider, string $providerId): ?object
    {
        $metaKey = $this->getMetaKey($provider);
        if (empty($metaKey)) {
            return null;
        }

        $userId = $this->getUserIdByProviderAccount($provider, $providerId);
        if ($userId > 0) {
            return get_userdata($userId);
        }

        return null;
    }

    public function getUserIdByProviderAccount(string $provider, string $providerId): int
    {
        $metaKey = $this->getMetaKey($provider);
        if (empty($metaKey)) {
            return 0;
        }

        global $wpdb;

        $userId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                $metaKey,
                $providerId
            )
        );

        return (int) $userId;
    }

    public function getProviderAccountFromUser(int $userId, string $provider): ?string
    {
        $metaKey = $this->getMetaKey($provider);
        if (empty($metaKey)) {
            return null;
        }

        $value = get_user_meta($userId, $metaKey, true);
        return ! empty($value) ? (string) $value : null;
    }

    public function createUserFromSocialInfo(array $info, string $provider): ?int
    {
        $email = sanitize_email($info['email'] ?? '');
        $name = sanitize_text_field($info['name'] ?? '');
        $providerId = sanitize_text_field($info['provider_id'] ?? '');

        if (empty($email) || empty($providerId)) {
            return null;
        }

        // Check if email already exists
        $existingUser = get_user_by('email', $email);
        if ($existingUser) {
            // Link provider to existing user
            $this->linkAccountToUser($existingUser->ID, $provider, $providerId);
            return $existingUser->ID;
        }

        // Generate unique username from email
        $username = $this->generateUniqueUsername(explode('@', $email)[0]);
        if (empty($username)) {
            return null;
        }

        $userId = wp_create_user($username, wp_generate_password(32, true, true), $email);
        if (is_wp_error($userId)) {
            return null;
        }

        // Set display name
        if (! empty($name)) {
            wp_update_user([
                'ID' => $userId,
                'display_name' => $name,
                'first_name' => $name,
            ]);
        }

        // Link provider account
        $this->linkAccountToUser($userId, $provider, $providerId);

        return $userId;
    }

    private function generateUniqueUsername(string $base): string
    {
        $base = sanitize_user($base, true);
        if (empty($base)) {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;

        while (username_exists($username)) {
            $username = $base . $counter;
            $counter++;
            if ($counter > 1000) {
                return '';
            }
        }

        return $username;
    }

    private function getMetaKey(string $provider): string
    {
        $mapping = [
            'google' => 'easy_auth_google_id',
            'github' => 'easy_auth_github_id',
        ];

        return $mapping[strtolower($provider)] ?? '';
    }
}
