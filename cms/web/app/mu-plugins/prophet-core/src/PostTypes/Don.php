<?php

declare(strict_types=1);

namespace ProphetCore\PostTypes;

final class Don
{
    public const SLUG = 'don';
    public const META_PREFIX = '_don_';

    public const STATUT_EN_ATTENTE = 'don_en_attente';
    public const STATUT_RECU = 'don_recu';
    public const STATUT_ECHOUE = 'don_echoue';

    private const STATUTS = [
        self::STATUT_EN_ATTENTE => 'En attente',
        self::STATUT_RECU => 'Reçu',
        self::STATUT_ECHOUE => 'Échoué',
    ];

    public static function metaKey(string $champ): string
    {
        return self::META_PREFIX . $champ;
    }

    public static function statutLabel(string $statut): string
    {
        return self::STATUTS[$statut] ?? $statut;
    }

    public static function register(): void
    {
        add_action('init', static function (): void {
            register_post_type(self::SLUG, [
                'labels' => [
                    'name' => 'Dons',
                    'singular_name' => 'Don',
                    'edit_item' => 'Détail du don',
                    'search_items' => 'Rechercher un don',
                ],
                'public' => false,
                'show_ui' => true,
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'menu_icon' => 'dashicons-heart',
                'supports' => ['title'],
                'capabilities' => ['create_posts' => 'do_not_allow'],
                'map_meta_cap' => true,
            ]);

            foreach (self::STATUTS as $slug => $libelle) {
                register_post_status($slug, [
                    'label' => $libelle,
                    'public' => false,
                    'internal' => false,
                    'protected' => true,
                    'show_in_admin_all_list' => true,
                    'show_in_admin_status_list' => true,
                    'label_count' => _n_noop($libelle . ' (%s)', $libelle . ' (%s)'),
                ]);
            }
        });
    }
}
