<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (! defined('WP_CONTENT_DIR')) {
    // ProphetCore\Cli\SeedCommand::sideloadImage() construit un chemin depuis
    // cette constante, normalement définie par config/application.php.
    define('WP_CONTENT_DIR', __DIR__ . '/../web/app');
}

if (! class_exists('WP_CLI')) {
    // Stub minimal : ProphetCore\Cli\SeedCommand appelle WP_CLI::log()/
    // ::warning()/::success(), indisponibles hors du runtime WP-CLI réel.
    class WP_CLI
    {
        public static function log(string $message): void
        {
        }

        public static function warning(string $message): void
        {
        }

        public static function success(string $message): void
        {
        }
    }
}
