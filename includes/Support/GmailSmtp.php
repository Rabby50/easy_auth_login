<?php

namespace EasyAuthLogin\Support;

use PHPMailer\PHPMailer\PHPMailer;

if (! defined('ABSPATH')) {
    exit;
}

class GmailSmtp
{
    public function register(): void
    {
        add_action('phpmailer_init', [$this, 'configureMailer']);
        add_filter('wp_mail_from', [$this, 'filterFromEmail']);
        add_filter('wp_mail_from_name', [$this, 'filterFromName']);
    }

    public function configureMailer(PHPMailer $phpmailer): void
    {
        if (! Options::get('gmail_smtp_enabled', false)) {
            return;
        }

        $host = (string) Options::get('gmail_smtp_host', 'smtp.gmail.com');
        $port = (int) Options::get('gmail_smtp_port', 587);
        $encryption = (string) Options::get('gmail_smtp_encryption', 'tls');
        $username = (string) Options::get('gmail_smtp_username', '');
        $password = (string) Options::get('gmail_smtp_password', '');
        $smtpAuth = (bool) Options::get('gmail_smtp_auth', true);

        if ($host === '' || $port <= 0) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $host;
        $phpmailer->Port = $port;
        $phpmailer->SMTPAuth = $smtpAuth;

        if ($encryption === 'tls') {
            $phpmailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $phpmailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $phpmailer->SMTPSecure = '';
        }

        if ($username !== '') {
            $phpmailer->Username = $username;
        }

        if ($password !== '') {
            $phpmailer->Password = $password;
        }

        $fromEmail = $this->filterFromEmail((string) get_option('admin_email'));
        $fromName = $this->filterFromName((string) get_bloginfo('name'));

        if ($fromEmail !== '') {
            $phpmailer->setFrom($fromEmail, $fromName, false);
        }
    }

    public function filterFromEmail(string $email): string
    {
        $fromEmail = sanitize_email((string) Options::get('gmail_from_email', ''));
        return $fromEmail !== '' ? $fromEmail : $email;
    }

    public function filterFromName(string $name): string
    {
        $fromName = sanitize_text_field((string) Options::get('gmail_from_name', ''));
        if ($fromName !== '') {
            return $fromName;
        }

        $siteName = sanitize_text_field((string) Options::get('custom_site_name', ''));
        return $siteName !== '' ? $siteName : $name;
    }
}
