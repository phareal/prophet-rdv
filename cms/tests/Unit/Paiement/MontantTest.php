<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\Montant;
use ProphetCore\Tests\TestCase;

final class MontantTest extends TestCase
{
    private function service(?string $titre, string $prix = ''): void
    {
        Functions\when('carbon_get_theme_option')->alias(
            static fn (string $cle) => $cle === 'moneroo_actif' ? true : ''
        );
        Functions\when('get_posts')->justReturn(
            $titre === null ? [] : [(object) ['ID' => 7, 'post_title' => $titre]]
        );
        Functions\when('carbon_get_post_meta')->justReturn($prix);
    }

    public function test_le_montant_vient_du_prix_du_service(): void
    {
        $this->service('Mariage', '25000');

        $this->assertSame(
            ['montant' => 25000, 'devise' => 'XOF'],
            Montant::pour('Mariage')
        );
    }

    public function test_un_service_sans_prix_ne_donne_aucun_montant(): void
    {
        $this->service('Mariage', '');

        $this->assertNull(Montant::pour('Mariage'));
    }

    public function test_un_prix_a_zero_ne_donne_aucun_montant(): void
    {
        $this->service('Mariage', '0');

        $this->assertNull(Montant::pour('Mariage'));
    }

    public function test_un_service_introuvable_ne_donne_aucun_montant(): void
    {
        $this->service(null);

        $this->assertNull(Montant::pour('Service supprimé'));
    }

    public function test_une_inscription_a_un_evenement_ne_donne_aucun_montant(): void
    {
        // Les événements ont leurs propres modalités : aucun service ne leur
        // correspond, et on ne va pas interroger la base pour le découvrir.
        Functions\expect('get_posts')->never();
        Functions\when('carbon_get_theme_option')->justReturn(true);

        $this->assertNull(Montant::pour('Inscription — Croisade de Lomé'));
    }

    public function test_aucun_montant_quand_le_paiement_est_desactive(): void
    {
        Functions\when('carbon_get_theme_option')->justReturn('');
        Functions\expect('get_posts')->never();

        $this->assertNull(Montant::pour('Mariage'));
    }
}
