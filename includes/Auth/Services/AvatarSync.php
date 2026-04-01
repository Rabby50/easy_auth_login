<?php

namespace EasyAuthLogin\Auth\Services;

if (! defined('ABSPATH')) {
    exit;
}

class AvatarSync
{
    public function syncAvatarFromUrl(int $userId, string $imageUrl): bool
    {
        if (empty($imageUrl)) {
            return false;
        }

        $imageUrl = esc_url_raw($imageUrl);
        if (empty($imageUrl)) {
            return false;
        }

        return (bool) update_user_meta($userId, 'easy_auth_social_avatar_url', $imageUrl);
    }

    public function getAvatarUrl(int $userId): string
    {
        $customUrl = (string) get_user_meta($userId, 'easy_auth_social_avatar_url', true);

        if (! empty($customUrl)) {
            return $customUrl;
        }

        return '';
    }

    public function downloadAndSetAvatar(int $userId, string $imageUrl): bool
    {
        if (empty($imageUrl)) {
            return false;
        }

        // Store the URL in usermeta for later use
        $this->syncAvatarFromUrl($userId, $imageUrl);

        // Attempt to download and create a local attachment
        return $this->createAttachmentFromUrl($userId, $imageUrl);
    }

    private function createAttachmentFromUrl(int $userId, string $imageUrl): bool
    {
        // Check if WP handles remote images
        if (defined('ALLOW_UNFILTERED_UPLOADS') && ! ALLOW_UNFILTERED_UPLOADS) {
            return false;
        }

        $response = wp_remote_get($imageUrl, [
            'timeout' => 10,
            'sslverify' => true,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $contentType = wp_remote_retrieve_header($response, 'content-type');
        $body = wp_remote_retrieve_body($response);

        if (empty($body) || empty($contentType)) {
            return false;
        }

        // Determine file extension
        $ext = $this->getExtensionFromContentType($contentType);
        if (empty($ext)) {
            return false;
        }

        $user = get_userdata($userId);
        if (! $user) {
            return false;
        }

        $filename = 'avatar-' . $userId . '-' . time() . '.' . $ext;

        $upload = wp_upload_bits($filename, null, $body);
        if (! empty($upload['error'])) {
            return false;
        }

        // Create attachment post
        $attachment = [
            'post_mime_type' => $contentType,
            'post_title' => sprintf(__('Avatar for %s', 'easy-auth-login'), $user->user_login),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attachmentId = wp_insert_attachment($attachment, $upload['file']);
        if (is_wp_error($attachmentId)) {
            return false;
        }

        // Generate attachment metadata
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata($attachmentId, $upload['file']);
        wp_update_attachment_metadata($attachmentId, $metadata);

        // Set as user profile picture (via attachment ID in usermeta is non-standard)
        // WordPress doesn't have native support for profile pictures, so we store the attachment URL
        update_user_meta($userId, 'easy_auth_avatar_attachment_id', $attachmentId);

        return true;
    }

    private function getExtensionFromContentType(string $contentType): string
    {
        $mapping = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];

        return $mapping[$contentType] ?? '';
    }
}
