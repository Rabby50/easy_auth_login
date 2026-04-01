<?php

namespace EasyAuthLogin\Auth;

use EasyAuthLogin\Support\Options;

if (! defined('ABSPATH')) {
    exit;
}

class RateLimitService
{
    public function isLockedOut(string $ipAddress): bool
    {
        if (! Options::get('lockout_enabled', true)) {
            return false;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'easy_auth_logs';
        $now = current_time('mysql');

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE ip_address = %s AND lockout_expiry IS NOT NULL AND lockout_expiry > %s",
            $ipAddress,
            $now
        );

        $count = (int) $wpdb->get_var($query);
        return $count > 0;
    }

    public function recordFailedAttempt(string $ipAddress, string $username): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'easy_auth_logs';
        $maxRetries = max(1, (int) Options::get('lockout_max_retries', 5));
        $minutes = max(1, (int) Options::get('lockout_minutes', 15));

        $windowStart = gmdate('Y-m-d H:i:s', time() - ($minutes * 60));

        $failedCount = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE ip_address = %s AND time >= %s",
                $ipAddress,
                $windowStart
            )
        );

        $lockoutExpiry = null;
        if ($failedCount + 1 >= $maxRetries) {
            $lockoutExpiry = gmdate('Y-m-d H:i:s', time() + ($minutes * 60));
        }

        $wpdb->insert(
            $table,
            [
                'ip_address' => $ipAddress,
                'username_attempted' => sanitize_text_field($username),
                'time' => current_time('mysql'),
                'lockout_expiry' => $lockoutExpiry,
            ],
            ['%s', '%s', '%s', '%s']
        );
    }

    public function clearAttempts(string $ipAddress): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'easy_auth_logs';
        $wpdb->delete($table, ['ip_address' => $ipAddress], ['%s']);
    }
}
