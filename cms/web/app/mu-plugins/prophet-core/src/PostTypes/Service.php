<?php

declare(strict_types=1);

namespace ProphetCore\PostTypes;

final class Service
{
    public const SLUG = 'service';

    public static function register(): void
    {
        add_action('init', static function (): void {
            register_post_type(self::SLUG, [
                'labels' => [
                    'name' => 'Services',
                    'singular_name' => 'Service',
                    'add_new_item' => 'Ajouter un service',
                    'edit_item' => 'Modifier le service',
                ],
                'public' => true,
                'has_archive' => false,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-star-filled',
                'supports' => ['title', 'editor', 'page-attributes'],
                'rewrite' => ['slug' => 'services'],
            ]);
        });
    }
}
