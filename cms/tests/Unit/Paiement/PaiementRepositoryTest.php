<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\PaiementRepository;
use ProphetCore\Paiement\Statut;
use ProphetCore\PostTypes\RendezVous;
use ProphetCore\Tests\TestCase;

final class PaiementRepositoryTest extends TestCase
{
    private array $metas = [];

    private function base(array $metasInitiales = []): void
    {
        $this->metas = $metasInitiales;

        Functions\when('get_post_meta')->alias(
            fn ($id, $cle, $single) => $this->metas[$cle] ?? ''
        );
        Functions\when('update_post_meta')->alias(
            function ($id, $cle, $valeur) {
                $this->metas[$cle] = $valeur;

                return true;
            }
        );
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('current_time')->justReturn('2026-08-03 10:00:00');
    }

    public function test_l_initialisation_fige_l_identifiant_le_montant_et_la_devise(): void
    {
        $this->base();

        (new PaiementRepository())->enregistrerInitialisation(7, 'tx_1', 25000, 'XOF');

        $this->assertSame('tx_1', $this->metas[RendezVous::metaKey('paiement_id')]);
        $this->assertSame(25000, $this->metas[RendezVous::metaKey('paiement_montant')]);
        $this->assertSame('XOF', $this->metas[RendezVous::metaKey('paiement_devise')]);
        $this->assertSame(Statut::EN_ATTENTE, $this->metas[RendezVous::metaKey('paiement_statut')]);
    }

    public function test_le_statut_par_defaut_est_non_requis(): void
    {
        $this->base();

        $this->assertSame(Statut::NON_REQUIS, (new PaiementRepository())->statut(7));
    }

    public function test_appliquer_un_statut_le_stocke_avec_le_moyen_et_l_horodatage(): void
    {
        $this->base([RendezVous::metaKey('paiement_statut') => Statut::EN_ATTENTE]);

        $applique = (new PaiementRepository())->appliquerStatut(7, Statut::PAYE, 'mtn_bj');

        $this->assertTrue($applique);
        $this->assertSame(Statut::PAYE, $this->metas[RendezVous::metaKey('paiement_statut')]);
        $this->assertSame('mtn_bj', $this->metas[RendezVous::metaKey('paiement_methode')]);
        $this->assertSame('2026-08-03 10:00:00', $this->metas[RendezVous::metaKey('paiement_verifie_le')]);
    }

    public function test_reappliquer_le_meme_statut_final_ne_fait_rien(): void
    {
        // Le webhook et le retour navigateur portent la même information : le
        // second arrivé ne doit produire aucun effet.
        $this->base([RendezVous::metaKey('paiement_statut') => Statut::PAYE]);

        $applique = (new PaiementRepository())->appliquerStatut(7, Statut::PAYE, 'mtn_bj');

        $this->assertFalse($applique);
    }

    public function test_un_statut_final_ne_peut_pas_etre_ecrase(): void
    {
        // Un paiement reçu ne redevient jamais « en attente » sur un webhook tardif.
        $this->base([RendezVous::metaKey('paiement_statut') => Statut::PAYE]);

        $applique = (new PaiementRepository())->appliquerStatut(7, Statut::EN_ATTENTE);

        $this->assertFalse($applique);
        $this->assertSame(Statut::PAYE, $this->metas[RendezVous::metaKey('paiement_statut')]);
    }

    public function test_la_recherche_par_identifiant_de_transaction(): void
    {
        $capture = [];
        Functions\when('get_posts')->alias(function ($args) use (&$capture) {
            $capture = $args;

            return [42];
        });

        $id = (new PaiementRepository())->trouverParPaiementId('tx_1');

        $this->assertSame(42, $id);
        $this->assertSame(RendezVous::SLUG, $capture['post_type']);
        $this->assertSame(
            RendezVous::metaKey('paiement_id'),
            $capture['meta_query'][0]['key']
        );
    }

    public function test_un_identifiant_de_transaction_inconnu_renvoie_null(): void
    {
        Functions\when('get_posts')->justReturn([]);

        $this->assertNull((new PaiementRepository())->trouverParPaiementId('inconnu'));
    }
}
