<?php

declare(strict_types=1);

namespace ProphetCore\PostTypes;

final class MotifPaiement
{
    public const SLUG = 'motif_paiement';
    public const META_PREFIX = '_motif_';

    public const REGIME_LIBRE = 'libre';
    public const REGIME_FIXE = 'fixe';
    public const REGIME_SUGGERE = 'suggere';

    public static function metaKey(string $champ): string
    {
        return self::META_PREFIX . $champ;
    }

    public static function register(): void
    {
        add_action('init', static function (): void {
            register_post_type(self::SLUG, [
                'labels' => [
                    'name' => 'Motifs de paiement',
                    'singular_name' => 'Motif de paiement',
                    'add_new_item' => 'Ajouter un motif',
                    'edit_item' => 'Modifier le motif',
                ],
                // Public : c'est l'URL du motif qui sert de lien partageable et
                // que le QR code encode.
                'public' => true,
                'has_archive' => false,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-money-alt',
                'supports' => ['title', 'editor', 'page-attributes'],
                'rewrite' => ['slug' => 'don', 'with_front' => false],
            ]);
        });
    }
}
