<?php

declare(strict_types=1);

namespace ProphetCore\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use ProphetCore\PostTypes\Photo;

final class PhotoFields
{
    public static function register(): void
    {
        add_action('carbon_fields_register_fields', static function (): void {
            Container::make('post_meta', 'Détails de la photo')
                ->where('post_type', '=', Photo::SLUG)
                ->add_fields([
                    Field::make('image', 'photo_image', 'Image')->set_value_type('id')->set_required(true),
                    Field::make('text', 'photo_legende', 'Légende'),
                    Field::make('select', 'photo_format', 'Format')
                        ->set_options(['normal' => 'Normal', 'wide' => 'Large'])
                        ->set_default_value('normal'),
                ]);
        });
    }
}
