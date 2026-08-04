<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use Brain\Monkey\Functions;
use ProphetCore\Don\MotifRepository;
use ProphetCore\PostTypes\MotifPaiement;
use ProphetCore\Tests\TestCase;

final class MotifRepositoryTest extends TestCase
{
    private function motif(array $metas): void
    {
        Functions\when('get_posts')->justReturn([
            (object) ['ID' => 3, 'post_title' => 'Dîmes', 'post_content' => 'Description'],
        ]);
        Functions\when('carbon_get_post_meta')->alias(
            static fn ($id, $cle) => $metas[$cle] ?? ''
        );
        Functions\when('get_post_field')->justReturn('dimes');
    }

    public function test_un_motif_actif_est_lu_avec_tous_ses_champs(): void
    {
        $this->motif([
            'motif_icone' => 'hand-coins',
            'motif_couleur' => '#B07A14',
            'motif_regime' => MotifPaiement::REGIME_LIBRE,
            'motif_montant_min' => '500',
            'motif_montant_max' => '5000000',
            'motif_actif' => true,
        ]);

        $motifs = MotifRepository::actifs();

        $this->assertCount(1, $motifs);
        $this->assertSame('Dîmes', $motifs[0]['titre']);
        $this->assertSame('hand-coins', $motifs[0]['icone']);
        $this->assertSame(MotifPaiement::REGIME_LIBRE, $motifs[0]['regime']);
        $this->assertSame(500, $motifs[0]['min']);
        $this->assertSame(5000000, $motifs[0]['max']);
    }

    public function test_les_bornes_absentes_prennent_leurs_valeurs_par_defaut(): void
    {
        $this->motif(['motif_regime' => MotifPaiement::REGIME_LIBRE, 'motif_actif' => true]);

        $motifs = MotifRepository::actifs();

        $this->assertSame(500, $motifs[0]['min']);
        $this->assertSame(5000000, $motifs[0]['max']);
    }

    public function test_les_montants_suggeres_sont_aplatis_en_entiers(): void
    {
        $this->motif([
            'motif_regime' => MotifPaiement::REGIME_SUGGERE,
            'motif_montants_suggeres' => [['valeur' => '1000'], ['valeur' => '5000']],
            'motif_actif' => true,
        ]);

        $this->assertSame([1000, 5000], MotifRepository::actifs()[0]['suggeres']);
    }

    public function test_la_requete_ne_demande_que_les_motifs_publies_dans_l_ordre_du_menu(): void
    {
        $capture = [];
        Functions\when('get_posts')->alias(function ($args) use (&$capture) {
            $capture = $args;

            return [];
        });

        MotifRepository::actifs();

        $this->assertSame(MotifPaiement::SLUG, $capture['post_type']);
        $this->assertSame('publish', $capture['post_status']);
        $this->assertSame('menu_order', $capture['orderby']);
    }
}
