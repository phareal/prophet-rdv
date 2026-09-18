<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\PostTypes\Temoignage;
use Roots\Acorn\View\Composer;

class Testimonials extends Composer
{
    // partials.hero-left : HeroLeft.vue porte son propre carrousel de
    // témoignages (voir hero-left.blade.php), alimenté par les mêmes données.
    protected static $views = ['sections.testimonials', 'partials.hero-left'];

    public function with(): array
    {
        $posts = get_posts([
            'post_type' => Temoignage::SLUG,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);

        return [
            'temoignages' => array_map(static fn ($post): array => [
                'nom' => (string) $post->post_title,
                'initiales' => (string) carbon_get_post_meta($post->ID, 'temoignage_initiales'),
                'pays' => (string) carbon_get_post_meta($post->ID, 'temoignage_pays'),
                'consultation' => self::serviceLie($post->ID),
                'couleur' => (string) carbon_get_post_meta($post->ID, 'temoignage_couleur'),
                'etoiles' => (int) carbon_get_post_meta($post->ID, 'temoignage_etoiles'),
                'texte' => (string) carbon_get_post_meta($post->ID, 'temoignage_texte'),
            ], $posts),
        ];
    }

    private static function serviceLie(int $postId): string
    {
        $association = carbon_get_post_meta($postId, 'temoignage_service');

        if (! is_array($association) || $association === []) {
            return '';
        }

        return (string) get_the_title((int) $association[0]['id']);
    }
}
