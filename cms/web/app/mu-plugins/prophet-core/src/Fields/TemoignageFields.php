<?php

declare(strict_types=1);

namespace ProphetCore\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use ProphetCore\PostTypes\Service;
use ProphetCore\PostTypes\Temoignage;

final class TemoignageFields
{
    public static function register(): void
    {
        add_action('carbon_fields_register_fields', static function (): void {
            Container::make('post_meta', 'Détails du témoignage')
                ->where('post_type', '=', Temoignage::SLUG)
                ->add_fields([
                    Field::make('text', 'temoignage_initiales', 'Initiales')->set_required(true),
                    Field::make('text', 'temoignage_pays', 'Pays')->set_required(true),
                    Field::make('association', 'temoignage_service', 'Service concerné')
                        ->set_types([['type' => 'post', 'post_type' => Service::SLUG]])
                        ->set_max(1),
                    Field::make('select', 'temoignage_etoiles', 'Étoiles')
                        ->set_options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                        ->set_default_value(5),
                    Field::make('color', 'temoignage_couleur', 'Couleur')->set_default_value('#B07A14'),
                    Field::make('textarea', 'temoignage_texte', 'Témoignage')->set_required(true),
                ]);
        });
    }
}
