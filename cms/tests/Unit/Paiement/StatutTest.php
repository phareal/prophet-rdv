<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use ProphetCore\Paiement\Statut;
use ProphetCore\Tests\TestCase;

final class StatutTest extends TestCase
{
    public function test_les_cinq_statuts_moneroo_sont_traduits(): void
    {
        $this->assertSame(Statut::EN_ATTENTE, Statut::depuisMoneroo('initiated'));
        $this->assertSame(Statut::EN_ATTENTE, Statut::depuisMoneroo('pending'));
        $this->assertSame(Statut::PAYE, Statut::depuisMoneroo('success'));
        $this->assertSame(Statut::ECHOUE, Statut::depuisMoneroo('failed'));
        $this->assertSame(Statut::ANNULE, Statut::depuisMoneroo('cancelled'));
    }

    public function test_un_statut_moneroo_inconnu_reste_en_attente(): void
    {
        // Prudence délibérée : un statut inconnu ne doit jamais être pris pour
        // un paiement reçu ni pour un échec définitif.
        $this->assertSame(Statut::EN_ATTENTE, Statut::depuisMoneroo('quelque_chose_de_neuf'));
    }

    public function test_seuls_paye_echoue_et_annule_sont_finaux(): void
    {
        $this->assertTrue(Statut::estFinal(Statut::PAYE));
        $this->assertTrue(Statut::estFinal(Statut::ECHOUE));
        $this->assertTrue(Statut::estFinal(Statut::ANNULE));
        $this->assertFalse(Statut::estFinal(Statut::EN_ATTENTE));
        $this->assertFalse(Statut::estFinal(Statut::NON_REQUIS));
    }

    public function test_les_libelles_sont_en_francais(): void
    {
        $this->assertSame('Non requis', Statut::libelle(Statut::NON_REQUIS));
        $this->assertSame('En attente', Statut::libelle(Statut::EN_ATTENTE));
        $this->assertSame('Réglé', Statut::libelle(Statut::PAYE));
        $this->assertSame('Échoué', Statut::libelle(Statut::ECHOUE));
        $this->assertSame('Annulé', Statut::libelle(Statut::ANNULE));
    }

    public function test_un_statut_interne_inconnu_renvoie_sa_propre_valeur(): void
    {
        $this->assertSame('inattendu', Statut::libelle('inattendu'));
    }
}
