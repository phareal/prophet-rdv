<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\PostTypes;

use Brain\Monkey\Functions;
use ProphetCore\PostTypes\MotifPaiement;
use ProphetCore\Tests\TestCase;

final class MotifPaiementTest extends TestCase
{
    private string $repertoire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repertoire = sys_get_temp_dir() . '/motif-qr-test-' . uniqid();
        mkdir($this->repertoire . '/qr', 0777, true);

        Functions\when('wp_upload_dir')->justReturn([
            'basedir' => $this->repertoire,
            'baseurl' => 'https://exemple.test/uploads',
        ]);
        Functions\when('get_permalink')->justReturn('https://exemple.test/don/dimes/');
        Functions\when('wp_mkdir_p')->justReturn(true);
        Functions\when('esc_url')->returnArg();
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

    /**
     * Le HTML est partagé entre la colonne de la liste et l'écran d'édition
     * (défaut D2 de la recette : le QR n'était visible que dans la liste) :
     * un seul point de génération garantit que les deux affichent strictement
     * le même lien et les deux mêmes téléchargements.
     */
    public function test_le_html_contient_le_lien_et_les_deux_telechargements(): void
    {
        $html = MotifPaiement::lienEtQrHtml(65);

        $this->assertStringContainsString('href="https://exemple.test/don/dimes/"', $html);
        // Le nom de fichier inclut un hachage court de l'URL encodée (voir
        // QrCode::chemin()) : le nom exact n'est pas figé ici, seul le
        // motif l'est — QrCodeTest couvre le hachage lui-même.
        $this->assertMatchesRegularExpression(
            '#https://exemple\.test/uploads/qr/motif-65-[0-9a-f]{8}\.png#',
            $html
        );
        $this->assertMatchesRegularExpression(
            '#https://exemple\.test/uploads/qr/motif-65-[0-9a-f]{8}\.svg#',
            $html
        );
        $this->assertStringContainsString('Télécharger PNG', $html);
        $this->assertStringContainsString('Télécharger SVG', $html);
    }

    /**
     * La colonne « Lien et QR » de la liste des motifs (utile pour récupérer
     * un lien vite fait) doit continuer à fonctionner exactement comme avant
     * — ce n'est pas parce que le QR arrive sur l'écran d'édition qu'il doit
     * disparaître de la liste.
     */
    public function test_la_colonne_de_liste_affiche_toujours_le_lien_et_le_qr(): void
    {
        ob_start();
        MotifPaiement::renderColumn('motif_lien_qr', 65);
        $sortie = ob_get_clean();

        $this->assertSame(MotifPaiement::lienEtQrHtml(65), $sortie);
    }

    /**
     * La page de remerciement du don (/don/merci/) vit sous le même préfixe
     * /don/ que le lien profond de chaque motif. La règle générique posée
     * par le rewrite du CPT (don/([^/]+)/?$) matcherait /don/merci/ en
     * premier et tenterait d'y résoudre un motif nommé « merci » — 404 avant
     * même d'envisager la page. Sans une règle statique prioritaire
     * ('top'), la page de remerciement ne serait jamais atteinte.
     */
    public function test_une_regle_prioritaire_reserve_don_merci_a_la_page(): void
    {
        $reglesAjoutees = [];

        Functions\when('register_post_type')->justReturn(true);
        Functions\when('add_rewrite_rule')->alias(function ($regex, $query, $after = 'bottom') use (&$reglesAjoutees) {
            $reglesAjoutees[] = [$regex, $query, $after];
        });
        Functions\when('add_action')->alias(function ($hook, $callback) {
            if ($hook === 'init') {
                $callback();
            }
        });
        Functions\when('add_filter')->justReturn(true);

        MotifPaiement::register();

        $this->assertContains(
            ['^don/merci/?$', 'index.php?pagename=don/merci', 'top'],
            $reglesAjoutees
        );
    }
}
