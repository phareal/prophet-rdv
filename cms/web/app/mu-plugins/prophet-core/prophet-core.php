<?php

declare(strict_types=1);

/**
 * Plugin Name: Prophet Core
 * Description: Types de contenu, rendez-vous et intégrations du site du Prophète Jeremiah Nahoum.
 * Version: 1.0.0
 */

// Les register() des tâches suivantes viennent ici.

use Carbon_Fields\Carbon_Fields;
use ProphetCore\Cli\SeedCommand;
use ProphetCore\Don\DonPayable;
use ProphetCore\Don\DonSubmitHandler;
use ProphetCore\Fields\ContenuOptions;
use ProphetCore\Fields\DonFields;
use ProphetCore\Fields\MotifPaiementFields;
use ProphetCore\Fields\PaiementOptions;
use ProphetCore\Fields\PhotoFields;
use ProphetCore\Fields\RdvOptions;
use ProphetCore\Fields\RendezVousFields;
use ProphetCore\Fields\ServiceFields;
use ProphetCore\Fields\TemoignageFields;
use ProphetCore\Paiement\Abandon;
use ProphetCore\Paiement\InitHandler;
use ProphetCore\Paiement\ResolveurDeSujet;
use ProphetCore\Paiement\Webhook;
use ProphetCore\PostTypes\Don;
use ProphetCore\PostTypes\MotifPaiement;
use ProphetCore\PostTypes\Photo;
use ProphetCore\PostTypes\RendezVous;
use ProphetCore\PostTypes\Service;
use ProphetCore\PostTypes\Temoignage;
use ProphetCore\Rdv\RendezVousPayable;
use ProphetCore\Rdv\SubmitHandler;
use ProphetCore\Services\SmtpConfigurator;

add_action('plugins_loaded', static function (): void {
    SmtpConfigurator::register();
});

add_action('after_setup_theme', static function (): void {
    // Carbon Fields auto-detects its own asset URL by matching its filesystem
    // path against WP_PLUGIN_DIR / WP_CONTENT_DIR / ABSPATH. In this Bedrock
    // layout the Composer vendor dir sits outside all three, so the built-in
    // detection returns an empty string and every Carbon Fields JS/CSS asset
    // 404s (no fields render in the admin). Pin the constant to the route
    // added in docker/Caddyfile.cms.dev before booting.
    if (! defined('Carbon_Fields\\URL')) {
        define('Carbon_Fields\\URL', untrailingslashit(home_url('/cf-vendor/carbon-fields')));
    }

    Carbon_Fields::boot();
});

Service::register();
Temoignage::register();
Photo::register();
RendezVous::register();
MotifPaiement::register();
Don::register();

ServiceFields::register();
TemoignageFields::register();
PhotoFields::register();
RendezVousFields::register();
MotifPaiementFields::register();
DonFields::register();
ContenuOptions::register();
RdvOptions::register();
PaiementOptions::register();

add_action('init', static function (): void {
    ResolveurDeSujet::enregistrer(
        static fn (string $ref) => RendezVousPayable::parReference($ref),
        static fn (string $id) => RendezVousPayable::parPaiementId($id),
    );
    ResolveurDeSujet::enregistrer(
        static fn (string $ref) => DonPayable::parReference($ref),
        static fn (string $id) => DonPayable::parPaiementId($id),
    );
}, 5);

add_action('init', static function (): void {
    SubmitHandler::register();
    DonSubmitHandler::register();
    InitHandler::register();
});

Webhook::register();
Abandon::register();

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('prophet seed', SeedCommand::class);
}
