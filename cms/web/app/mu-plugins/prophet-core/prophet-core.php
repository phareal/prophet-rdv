<?php

declare(strict_types=1);

/**
 * Plugin Name: Prophet Core
 * Description: Types de contenu, rendez-vous et intégrations du site du Prophète Jeremiah Nahoum.
 * Version: 1.0.0
 */

// Les register() des tâches suivantes viennent ici.

use ProphetCore\Services\SmtpConfigurator;

add_action('plugins_loaded', static function (): void {
    SmtpConfigurator::register();
});
