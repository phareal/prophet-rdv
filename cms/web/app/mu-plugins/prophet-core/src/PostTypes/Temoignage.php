<?php

declare(strict_types=1);

namespace ProphetCore\PostTypes;

final class Temoignage
{
    public const SLUG = 'temoignage';

    public static function register(): void
    {
        add_action('init', static function (): void {
            register_post_type(self::SLUG, [
                'labels' => [
                    'name' => 'Témoignages',
                    'singular_name' => 'Témoignage',
                    'add_new_item' => 'Ajouter un témoignage',
                    'edit_item' => 'Modifier le témoignage',
                ],
                'public' => false,
                'show_ui' => true,
                'has_archive' => false,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-format-quote',
                'supports' => ['title', 'page-attributes'],
            ]);
        });
    }
}
