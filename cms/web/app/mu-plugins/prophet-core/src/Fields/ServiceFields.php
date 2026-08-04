<?php

declare(strict_types=1);

namespace ProphetCore\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use ProphetCore\PostTypes\Service;

final class ServiceFields
{
    public static function register(): void
    {
        add_action('carbon_fields_register_fields', static function (): void {
            Container::make('post_meta', 'Détails du service')
                ->where('post_type', '=', Service::SLUG)
                ->add_fields([
                    Field::make('text', 'service_icone', 'Icône')
                        ->set_help_text('Nom lucide en minuscules : heart, sparkles, mic-2, landmark…')
                        ->set_required(true),
                    Field::make('color', 'service_couleur', 'Couleur')
                        ->set_default_value('#B07A14'),
                    Field::make('text', 'service_prix', 'Prix')
                        ->set_attribute('type', 'number')
                        ->set_help_text(
                            'Montant en unités entières de la devise configurée '
                            . '(pour le franc CFA, des francs sans décimale). '
                            . 'Laisser vide pour ne pas proposer de paiement en '
                            . 'ligne sur ce service.'
                        ),
                ]);
        });
    }
}
