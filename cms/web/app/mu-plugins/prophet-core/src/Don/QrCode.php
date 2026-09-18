<?php

declare(strict_types=1);

namespace ProphetCore\Don;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Génération côté serveur et mise en cache dans les uploads. Un QR produit en
 * JavaScript ne s'imprime pas et ne se partage pas — or c'est précisément
 * l'usage : statut WhatsApp, projection pendant un culte, flyer.
 */
final class QrCode
{
    public static function pour(int $motifId, string $format = 'png'): string
    {
        $chemin = self::chemin($motifId, $format);

        if (! file_exists($chemin)) {
            self::nettoyerVersionsPerimees($motifId, $format, $chemin);
            self::generer($motifId, $format, $chemin);
        }

        $uploads = wp_upload_dir();

        return $uploads['baseurl'] . '/qr/' . basename($chemin);
    }

    /**
     * Le nom de fichier inclut un hachage court de l'URL encodée : migrer
     * dev → production, ou changer l'identifiant du motif à la main avant
     * publication (post_name déjà renseigné, donc pas couvert par le seul
     * déclencheur post_updated de MotifPaiementFields), laissait autrement
     * un PNG/SVG en cache qui encode l'ancienne URL — il s'affiche et se
     * télécharge très bien, il ne mène simplement plus nulle part. Un
     * changement d'URL fait ainsi manquer le cache et régénérer, sans
     * dépendre d'un déclencheur explicite.
     */
    public static function chemin(int $motifId, string $format): string
    {
        $uploads = wp_upload_dir();

        return $uploads['basedir'] . '/qr/motif-' . $motifId . '-' . self::hachage($motifId) . '.' . $format;
    }

    private static function hachage(int $motifId): string
    {
        return substr(md5((string) get_permalink($motifId)), 0, 8);
    }

    /**
     * Sans ce nettoyage, chaque changement d'URL laisserait derrière lui un
     * fichier orphelin : l'ancien hachage ne correspondant plus à rien, plus
     * jamais réclamé, plus jamais supprimé — l'annuaire uploads/qr grossirait
     * indéfiniment.
     */
    private static function nettoyerVersionsPerimees(int $motifId, string $format, string $cheminActuel): void
    {
        $uploads = wp_upload_dir();
        $anciens = glob($uploads['basedir'] . '/qr/motif-' . $motifId . '-*.' . $format) ?: [];

        foreach ($anciens as $fichier) {
            if ($fichier !== $cheminActuel && file_exists($fichier)) {
                unlink($fichier);
            }
        }
    }

    public static function invalider(int $motifId): void
    {
        foreach (['png', 'svg'] as $format) {
            $chemin = self::chemin($motifId, $format);

            if (file_exists($chemin)) {
                unlink($chemin);
            }
        }
    }

    private static function generer(int $motifId, string $format, string $chemin): void
    {
        wp_mkdir_p(dirname($chemin));

        // endroid/qr-code 6.0.9 (6.1+ exige PHP 8.4) : le Builder est une
        // classe readonly construite par arguments nommés, sans forme
        // fluide Builder::create()->writer()->data()->build().
        $builder = new Builder(
            writer: $format === 'svg' ? new SvgWriter() : new PngWriter(),
            data: (string) get_permalink($motifId),
            size: 600,
            margin: 16,
        );

        $resultat = $builder->build();

        $resultat->saveToFile($chemin);
    }
}
