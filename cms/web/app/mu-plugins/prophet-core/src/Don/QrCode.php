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
            self::generer($motifId, $format, $chemin);
        }

        $uploads = wp_upload_dir();

        return $uploads['baseurl'] . '/qr/' . basename($chemin);
    }

    public static function chemin(int $motifId, string $format): string
    {
        $uploads = wp_upload_dir();

        return $uploads['basedir'] . '/qr/motif-' . $motifId . '.' . $format;
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
