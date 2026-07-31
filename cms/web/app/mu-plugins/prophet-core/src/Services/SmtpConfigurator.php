<?php

declare(strict_types=1);

namespace ProphetCore\Services;

use ProphetCore\Options;

final class SmtpConfigurator
{
    public static function register(): void
    {
        add_action('phpmailer_init', static function ($phpmailer): void {
            $host = Options::env('SMTP_HOST');
            $user = Options::env('SMTP_USER');

            if ($host === '' || $user === '') {
                return; // Sans SMTP configuré, WordPress garde son envoi par défaut.
            }

            $phpmailer->isSMTP();
            $phpmailer->Host = $host;
            $phpmailer->Port = (int) Options::env('SMTP_PORT', '587');
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $user;
            $phpmailer->Password = Options::env('SMTP_PASS');
            $phpmailer->SMTPSecure = Options::env('SMTP_SECURE') === 'true' ? 'ssl' : 'tls';
            $phpmailer->CharSet = 'UTF-8';
        });
    }
}
