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

    public function canSendForgotOtp(int $userId): bool
    {
        $state = $this->getForgotOtpRateState($userId);
        return $state['count'] < $state['max_requests'];
    }

    public function recordForgotOtpRequest(int $userId): array
    {
        $state = $this->getForgotOtpRateState($userId);
        $state['count']++;

        $remainingSeconds = max(1, (int) ($state['reset_at'] - current_time('timestamp')));
        set_transient($this->getForgotOtpRateKey($userId), $state, $remainingSeconds);

        return $state;
    }

    public function getForgotOtpRetryAfterSeconds(int $userId): int
    {
        $state = $this->getForgotOtpRateState($userId);
        return max(0, (int) ($state['reset_at'] - current_time('timestamp')));
    }

    private function getForgotOtpRateState(int $userId): array
    {
        $key = $this->getForgotOtpRateKey($userId);
        $state = get_transient($key);
        $windowMinutes = max(5, min(1440, (int) Options::get('forgot_otp_window_minutes', 30)));
        $maxRequests = max(1, min(10, (int) Options::get('forgot_otp_max_requests', 3)));

        if (! is_array($state) || empty($state['reset_at']) || (int) $state['reset_at'] <= current_time('timestamp')) {
            $state = [
                'count' => 0,
                'reset_at' => current_time('timestamp') + ($windowMinutes * 60),
            ];
        }

        $state['count'] = max(0, (int) ($state['count'] ?? 0));
        $state['reset_at'] = (int) $state['reset_at'];
        $state['max_requests'] = $maxRequests;
        $state['window_minutes'] = $windowMinutes;

        return $state;
    }

    private function getForgotOtpRateKey(int $userId): string
    {
        return 'easy_auth_forgot_otp_limit_' . max(1, $userId);
    }
}
