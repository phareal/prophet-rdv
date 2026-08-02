<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\PostTypes\Photo;
use Roots\Acorn\View\Composer;

class Gallery extends Composer
{
    protected static $views = ['sections.gallery'];

    public function with(): array
    {
        $posts = get_posts([
            'post_type' => Photo::SLUG,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);

        return [
            'photos' => array_map(static fn ($post): array => [
                'url' => (string) wp_get_attachment_image_url(
                    (int) carbon_get_post_meta($post->ID, 'photo_image'),
                    'large'
                ),
                'legende' => (string) carbon_get_post_meta($post->ID, 'photo_legende'),
                'format' => (string) carbon_get_post_meta($post->ID, 'photo_format') ?: 'normal',
            ], $posts),
        ];
    }
}
