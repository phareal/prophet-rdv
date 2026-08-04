<?php

declare(strict_types=1);

namespace ProphetCore\Don;

use ProphetCore\PostTypes\MotifPaiement;

final class MotifRepository
{
    private const MIN_DEFAUT = 500;
    private const MAX_DEFAUT = 5000000;

    /** @return array<int, array<string, mixed>> */
    public static function actifs(): array
    {
        $posts = get_posts([
            'post_type' => MotifPaiement::SLUG,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);

        $motifs = array_map([self::class, 'depuisPost'], $posts);

        return array_values(array_filter($motifs, static fn (array $m): bool => $m['actif']));
    }

    public static function parId(int $id): ?array
    {
        $post = get_post($id);

        return ($post === null || $post->post_type !== MotifPaiement::SLUG)
            ? null
            : self::depuisPost($post);
    }

    public static function parSlug(string $slug): ?array
    {
        $posts = get_posts([
            'post_type' => MotifPaiement::SLUG,
            'post_status' => 'publish',
            'name' => $slug,
            'posts_per_page' => 1,
            'no_found_rows' => true,
        ]);

        return $posts === [] ? null : self::depuisPost($posts[0]);
    }

    private static function depuisPost(object $post): array
    {
        $lire = static fn (string $champ) => carbon_get_post_meta($post->ID, $champ);

        $suggeres = $lire('motif_montants_suggeres');
        $suggeres = is_array($suggeres)
            ? array_map(static fn (array $ligne): int => (int) ($ligne['valeur'] ?? 0), $suggeres)
            : [];

        return [
            'id' => (int) $post->ID,
            'slug' => (string) get_post_field('post_name', $post->ID),
            'titre' => (string) $post->post_title,
            'description' => (string) $post->post_content,
            'icone' => (string) $lire('motif_icone'),
            'couleur' => (string) $lire('motif_couleur'),
            'regime' => (string) ($lire('motif_regime') ?: MotifPaiement::REGIME_LIBRE),
            'montant_fixe' => (int) $lire('motif_montant_fixe'),
            'suggeres' => array_values(array_filter($suggeres)),
            'min' => (int) $lire('motif_montant_min') ?: self::MIN_DEFAUT,
            'max' => (int) $lire('motif_montant_max') ?: self::MAX_DEFAUT,
            'actif' => (bool) $lire('motif_actif'),
        ];
    }
}
