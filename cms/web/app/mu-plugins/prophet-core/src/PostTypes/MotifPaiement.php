<?php

declare(strict_types=1);

namespace ProphetCore\PostTypes;

use ProphetCore\Don\QrCode;

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

            // La page de remerciement du don (/don/merci/, voir
            // DonPayable::urlRetour() et DonSubmitHandler) vit sous ce même
            // préfixe /don/. Sans cette règle explicite en tête de table, la
            // règle générique posée par le rewrite ci-dessus
            // (don/([^/]+)/?$ → motif_paiement) intercepte /don/merci/ en
            // premier : WordPress essaie de résoudre « merci » comme un
            // motif, n'en trouve pas et répond 404 avant même d'envisager la
            // page. add_rewrite_rule(..., 'top') fait matcher celle-ci
            // d'abord, comme n'importe quelle règle statique prioritaire.
            add_rewrite_rule('^don/merci/?$', 'index.php?pagename=don/merci', 'top');
        });

        add_filter('manage_' . self::SLUG . '_posts_columns', [self::class, 'adminColumns']);
        add_action('manage_' . self::SLUG . '_posts_custom_column', [self::class, 'renderColumn'], 10, 2);
    }

    /**
     * Ajoute une colonne au jeu par défaut (cb, titre, date…) : contrairement
     * aux dons et rendez-vous, la liste des motifs n'a pas besoin d'être
     * redéfinie entièrement.
     */
    public static function adminColumns(array $colonnes): array
    {
        $colonnes['motif_lien_qr'] = 'Lien et QR';

        return $colonnes;
    }

    public static function renderColumn(string $colonne = '', int $postId = 0): void
    {
        if ($colonne !== 'motif_lien_qr') {
            return;
        }

        $lien = (string) get_permalink($postId);
        $png = QrCode::pour($postId, 'png');
        $svg = QrCode::pour($postId, 'svg');

        printf(
            '<a href="%1$s" target="_blank" rel="noopener">%1$s</a><br>'
            . '<a href="%2$s" download>Télécharger PNG</a> · <a href="%3$s" download>Télécharger SVG</a>',
            esc_url($lien),
            esc_url($png),
            esc_url($svg)
        );
    }
}
