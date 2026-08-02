<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\PostTypes\Service;
use Roots\Acorn\View\Composer;

class Services extends Composer
{
    protected static $views = ['sections.services', 'partials.rdv-form'];

    public function with(): array
    {
        return ['services' => self::all()];
    }

    /** @return array<int, array{titre: string, description: string, icone: string, couleur: string}> */
    public static function all(): array
    {
        $posts = get_posts([
            'post_type' => Service::SLUG,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);

        return array_map(static fn ($post): array => [
            'titre' => (string) $post->post_title,
            'description' => (string) $post->post_content,
            'icone' => (string) carbon_get_post_meta($post->ID, 'service_icone'),
            'couleur' => (string) carbon_get_post_meta($post->ID, 'service_couleur'),
        ], $posts);
    }
}
