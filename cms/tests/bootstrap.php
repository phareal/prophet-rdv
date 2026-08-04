<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (! defined('WP_CONTENT_DIR')) {
    // ProphetCore\Cli\SeedCommand::sideloadImage() construit un chemin depuis
    // cette constante, normalement définie par config/application.php.
    define('WP_CONTENT_DIR', __DIR__ . '/../web/app');
}

// WordPress core (wp-includes/default-constants.php) définit ces constantes de
// durée dès le chargement ; hors runtime WP, ProphetCore\Paiement\Abandon en a
// besoin pour son calcul de délai.
if (! defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

if (! defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS);
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
