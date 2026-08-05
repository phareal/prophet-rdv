<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use Brain\Monkey\Functions;
use ProphetCore\Don\QrCode;
use ProphetCore\Tests\TestCase;

final class QrCodeTest extends TestCase
{
    private string $repertoire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repertoire = sys_get_temp_dir() . '/qr-test-' . uniqid();
        mkdir($this->repertoire . '/qr', 0777, true);

        Functions\when('wp_upload_dir')->justReturn([
            'basedir' => $this->repertoire,
            'baseurl' => 'https://exemple.test/uploads',
        ]);
        Functions\when('get_permalink')->justReturn('https://exemple.test/don/dimes/');
        Functions\when('wp_mkdir_p')->justReturn(true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->repertoire . '/qr/*') ?: [] as $fichier) {
            unlink($fichier);
        }

        rmdir($this->repertoire . '/qr');
        rmdir($this->repertoire);

        parent::tearDown();
    }

    public function test_le_fichier_est_genere_a_la_premiere_demande(): void
    {
        $url = QrCode::pour(3, 'png');

        $this->assertFileExists(QrCode::chemin(3, 'png'));
        $this->assertStringStartsWith('https://exemple.test/uploads/qr/', $url);
    }

    public function test_le_fichier_n_est_pas_regenere_s_il_existe(): void
    {
        QrCode::pour(3, 'png');
        $premiere = filemtime(QrCode::chemin(3, 'png'));

        QrCode::pour(3, 'png');

        $this->assertSame($premiere, filemtime(QrCode::chemin(3, 'png')));
    }

    public function test_l_invalidation_supprime_les_deux_formats(): void
    {
        QrCode::pour(3, 'png');
        QrCode::pour(3, 'svg');

        QrCode::invalider(3);

        $this->assertFileDoesNotExist(QrCode::chemin(3, 'png'));
        $this->assertFileDoesNotExist(QrCode::chemin(3, 'svg'));
    }

    public function test_le_qr_encode_bien_le_lien_du_motif(): void
    {
        QrCode::pour(3, 'svg');

        $svg = (string) file_get_contents(QrCode::chemin(3, 'svg'));

        $this->assertNotSame('', $svg);
        $this->assertStringContainsString('<svg', $svg);
    }

    /**
     * Migrer dev → production (ou changer l'identifiant à la main avant
     * publication, cas que MotifPaiementFields::invalider() sur
     * post_updated ne couvre pas) laisse le nom de fichier en cache
     * dépendre, via son hachage, de l'URL encodée : une URL différente
     * manque simplement le cache au lieu de continuer à servir un QR qui
     * mène vers un domaine disparu.
     */
    public function test_un_changement_d_url_fait_manquer_le_cache_et_regenere(): void
    {
        QrCode::pour(3, 'png');
        $ancienChemin = QrCode::chemin(3, 'png');
        $this->assertFileExists($ancienChemin);

        Functions\when('get_permalink')->justReturn('https://nouveau-domaine.test/don/dimes/');
        $nouveauChemin = QrCode::chemin(3, 'png');

        $this->assertNotSame($ancienChemin, $nouveauChemin);

        $url = QrCode::pour(3, 'png');

        $this->assertFileExists($nouveauChemin);
        $this->assertStringContainsString(basename($nouveauChemin), $url);
    }

    /**
     * Sans ce nettoyage, chaque changement d'URL laisserait un fichier
     * orphelin derrière lui — jamais réclamé, jamais supprimé : l'annuaire
     * uploads/qr grossirait indéfiniment au fil des migrations et des
     * changements d'identifiant.
     */
    public function test_un_changement_d_url_nettoie_l_ancien_fichier(): void
    {
        QrCode::pour(3, 'png');
        $ancienChemin = QrCode::chemin(3, 'png');

        Functions\when('get_permalink')->justReturn('https://nouveau-domaine.test/don/dimes/');
        QrCode::pour(3, 'png');

        $this->assertFileDoesNotExist($ancienChemin);
    }
}
