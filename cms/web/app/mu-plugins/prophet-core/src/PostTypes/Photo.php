<?php

declare(strict_types=1);

namespace ProphetCore\PostTypes;

final class Photo
{
    public const SLUG = 'photo';

    public static function register(): void
    {
        add_action('init', static function (): void {
            register_post_type(self::SLUG, [
                'labels' => [
                    'name' => 'Photos',
                    'singular_name' => 'Photo',
                    'add_new_item' => 'Ajouter une photo',
                    'edit_item' => 'Modifier la photo',
                ],
                'public' => false,
                'show_ui' => true,
                'has_archive' => false,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-format-gallery',
                'supports' => ['title', 'page-attributes'],
            ]);
        });
    }
}
