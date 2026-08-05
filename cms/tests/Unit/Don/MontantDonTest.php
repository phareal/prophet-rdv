<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use Brain\Monkey\Functions;
use ProphetCore\Don\MontantDon;
use ProphetCore\PostTypes\MotifPaiement;
use ProphetCore\Tests\TestCase;

final class MontantDonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('carbon_get_theme_option')->alias(
            static fn (string $cle) => $cle === 'moneroo_devise' ? 'XOF' : ''
        );
    }

    private function motif(string $regime, array $surcharges = []): array
    {
        return array_merge([
            'regime' => $regime,
            'montant_fixe' => 10000,
            'suggeres' => [1000, 5000],
            'min' => 500,
            'max' => 5000000,
        ], $surcharges);
    }

    public function test_en_regime_libre_le_montant_soumis_est_retenu(): void
    {
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_LIBRE), '2500');

        $this->assertSame(2500, $resultat['montant']);
        $this->assertSame('XOF', $resultat['devise']);
    }

    public function test_en_regime_fixe_le_montant_soumis_est_ignore(): void
    {
        // Un champ caché serait modifiable : le montant vient du motif.
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_FIXE), '1');

        $this->assertSame(10000, $resultat['montant']);
    }

    /**
     * Le champ motif_montant_fixe n'a pas de valeur par défaut : un motif
     * basculé en régime fixe sans être rempli renverrait 0, que Moneroo
     * refuserait sans qu'aucun message n'explique au donateur pourquoi.
     */
    public function test_en_regime_fixe_un_montant_du_motif_a_zero_est_refuse(): void
    {
        $resultat = MontantDon::resoudre(
            $this->motif(MotifPaiement::REGIME_FIXE, ['montant_fixe' => 0]),
            '1'
        );

        $this->assertArrayHasKey('erreur', $resultat);
    }

    public function test_en_regime_fixe_un_montant_du_motif_negatif_est_refuse(): void
    {
        $resultat = MontantDon::resoudre(
            $this->motif(MotifPaiement::REGIME_FIXE, ['montant_fixe' => -100]),
            '1'
        );

        $this->assertArrayHasKey('erreur', $resultat);
    }

    public function test_en_regime_fixe_un_montant_du_motif_sous_le_minimum_est_refuse(): void
    {
        $resultat = MontantDon::resoudre(
            $this->motif(MotifPaiement::REGIME_FIXE, ['montant_fixe' => 100, 'min' => 500]),
            '1'
        );

        $this->assertArrayHasKey('erreur', $resultat);
    }

    public function test_en_regime_fixe_un_montant_du_motif_au_dessus_du_maximum_est_refuse(): void
    {
        $resultat = MontantDon::resoudre(
            $this->motif(MotifPaiement::REGIME_FIXE, ['montant_fixe' => 9999999, 'max' => 5000000]),
            '1'
        );

        $this->assertArrayHasKey('erreur', $resultat);
    }

    public function test_en_regime_suggere_une_valeur_hors_palier_est_acceptee(): void
    {
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_SUGGERE), '3000');

        $this->assertSame(3000, $resultat['montant']);
    }

    public function test_un_montant_sous_le_minimum_est_refuse(): void
    {
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_LIBRE), '100');

        $this->assertSame('Le montant minimum est de 500 XOF', $resultat['erreur']);
    }

    public function test_un_montant_au_dessus_du_maximum_est_refuse(): void
    {
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_LIBRE), '9999999');

        $this->assertSame('Le montant maximum est de 5000000 XOF', $resultat['erreur']);
    }

    public function test_un_montant_negatif_est_refuse(): void
    {
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_LIBRE), '-500');

        $this->assertArrayHasKey('erreur', $resultat);
    }

    public function test_un_montant_non_entier_est_refuse(): void
    {
        foreach (['2500,50', '2500.50', '1e5', 'beaucoup', ''] as $saisie) {
            $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_LIBRE), $saisie);

            $this->assertArrayHasKey('erreur', $resultat, 'Refusé attendu pour : ' . $saisie);
        }
    }

    public function test_les_espaces_de_saisie_sont_tolerés(): void
    {
        // « 2 500 » est ce qu'un francophone tape naturellement.
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_LIBRE), ' 2 500 ');

        $this->assertSame(2500, $resultat['montant']);
    }
}
