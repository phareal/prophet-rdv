<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\PostTypes;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\Statut;
use ProphetCore\PostTypes\Don;
use ProphetCore\Tests\TestCase;

final class DonTest extends TestCase
{
    /**
     * register_post_type() active la réécriture par défaut même pour un type
     * non public, avec le nom du type comme slug — ici « don », identique au
     * slug explicite du CPT public motif_paiement. Sans rewrite=false, la
     * règle de ce type (privé, jamais destiné à être visité par URL) écrase
     * silencieusement celle de motif_paiement dans la table fusionnée, et le
     * lien profond /don/<slug>/ ne résout plus le motif du tout.
     */
    public function test_le_type_n_a_pas_de_reecriture_pour_ne_pas_ecraser_celle_du_motif(): void
    {
        $capture = [];
        Functions\when('register_post_type')->alias(function ($slug, $args) use (&$capture) {
            $capture = $args;
        });
        Functions\when('register_post_status')->justReturn(true);
        Functions\when('add_action')->alias(function ($hook, $callback) {
            if ($hook === 'init') {
                $callback();
            }
        });
        Functions\when('add_filter')->justReturn(true);
        Functions\when('_n_noop')->returnArg();

        Don::register();

        $this->assertFalse($capture['rewrite']);
    }

    /**
     * Simule le filtrage réel de WP_Query sur meta_query : seuls les dons
     * dont la meta correspond à chaque clause survivent. C'est ce filtrage,
     * pas une simple relecture des arguments, qui pince la régression.
     */
    private function simulerGetPosts(array $donsParId): void
    {
        Functions\when('get_posts')->alias(function (array $args) use ($donsParId) {
            $clauses = array_filter(
                $args['meta_query'] ?? [],
                static fn ($clause) => is_array($clause)
            );

            return array_values(array_filter(
                array_keys($donsParId),
                static function (int $id) use ($donsParId, $clauses) {
                    foreach ($clauses as $clause) {
                        if (($donsParId[$id][$clause['key']] ?? null) !== $clause['value']) {
                            return false;
                        }
                    }

                    return true;
                }
            ));
        });

        Functions\when('get_post_meta')->alias(
            static fn (int $id, string $cle) => $donsParId[$id][$cle] ?? ''
        );
    }

    public function test_le_total_n_additionne_que_les_dons_payes(): void
    {
        $this->simulerGetPosts([
            1 => [Don::metaKey('montant') => 1000, Don::metaKey('paiement_statut') => Statut::PAYE],
            2 => [Don::metaKey('montant') => 500, Don::metaKey('paiement_statut') => Statut::EN_ATTENTE],
            3 => [Don::metaKey('montant') => 2000, Don::metaKey('paiement_statut') => Statut::ECHOUE],
        ]);

        $this->assertSame(1000, Don::totalPeriodeVisible());
    }

    public function test_plusieurs_dons_payes_sont_tous_additionnes(): void
    {
        $this->simulerGetPosts([
            1 => [Don::metaKey('montant') => 1000, Don::metaKey('paiement_statut') => Statut::PAYE],
            2 => [Don::metaKey('montant') => 3000, Don::metaKey('paiement_statut') => Statut::PAYE],
            3 => [Don::metaKey('montant') => 2000, Don::metaKey('paiement_statut') => Statut::ECHOUE],
        ]);

        $this->assertSame(4000, Don::totalPeriodeVisible());
    }

    public function test_la_requete_impose_le_statut_paye_en_plus_des_filtres_actifs(): void
    {
        $_GET['motif'] = '3';
        $capture = [];
        Functions\when('get_posts')->alias(function (array $args) use (&$capture) {
            $capture = $args;

            return [];
        });

        Don::totalPeriodeVisible();

        unset($_GET['motif']);

        $clauses = array_values(array_filter($capture['meta_query'], 'is_array'));
        $cles = array_column($clauses, 'key');

        $this->assertContains(Don::metaKey('paiement_statut'), $cles);
        $this->assertContains(Don::metaKey('motif'), $cles);

        $clauseStatut = $clauses[array_search(Don::metaKey('paiement_statut'), $cles, true)];

        $this->assertSame(Statut::PAYE, $clauseStatut['value']);
    }

    /**
     * Les trois chemins qui peuvent régler un paiement (retour navigateur,
     * webhook, changement manuel dans l'admin) écrivent tous la méta
     * `_don_paiement_statut` via update_post_meta()/update_metadata() — c'est
     * ce point d'écriture unique, pas chacun des trois chemins, que ce
     * gestionnaire observe.
     */
    public function test_un_paiement_regle_fait_passer_le_don_a_recu(): void
    {
        Functions\when('get_post_type')->justReturn(Don::SLUG);
        Functions\when('get_post_status')->justReturn(Don::STATUT_EN_ATTENTE);
        $appels = [];
        Functions\when('wp_update_post')->alias(function ($args) use (&$appels) {
            $appels[] = $args;

            return (int) $args['ID'];
        });

        Don::synchroniserStatutDepuisPaiement(1, 42, Don::metaKey('paiement_statut'), Statut::PAYE);

        $this->assertSame([['ID' => 42, 'post_status' => Don::STATUT_RECU]], $appels);
    }

    public function test_un_paiement_echoue_fait_passer_le_don_a_echoue(): void
    {
        Functions\when('get_post_type')->justReturn(Don::SLUG);
        Functions\when('get_post_status')->justReturn(Don::STATUT_EN_ATTENTE);
        $appels = [];
        Functions\when('wp_update_post')->alias(function ($args) use (&$appels) {
            $appels[] = $args;

            return (int) $args['ID'];
        });

        Don::synchroniserStatutDepuisPaiement(1, 42, Don::metaKey('paiement_statut'), Statut::ECHOUE);

        $this->assertSame([['ID' => 42, 'post_status' => Don::STATUT_ECHOUE]], $appels);
    }

    public function test_un_paiement_annule_fait_aussi_passer_le_don_a_echoue(): void
    {
        Functions\when('get_post_type')->justReturn(Don::SLUG);
        Functions\when('get_post_status')->justReturn(Don::STATUT_EN_ATTENTE);
        $appels = [];
        Functions\when('wp_update_post')->alias(function ($args) use (&$appels) {
            $appels[] = $args;

            return (int) $args['ID'];
        });

        Don::synchroniserStatutDepuisPaiement(1, 42, Don::metaKey('paiement_statut'), Statut::ANNULE);

        $this->assertSame([['ID' => 42, 'post_status' => Don::STATUT_ECHOUE]], $appels);
    }

    public function test_un_paiement_en_attente_ne_change_pas_le_statut_du_don(): void
    {
        Functions\when('get_post_type')->justReturn(Don::SLUG);
        Functions\when('get_post_status')->justReturn(Don::STATUT_EN_ATTENTE);
        $appels = [];
        Functions\when('wp_update_post')->alias(function ($args) use (&$appels) {
            $appels[] = $args;

            return (int) $args['ID'];
        });

        Don::synchroniserStatutDepuisPaiement(1, 42, Don::metaKey('paiement_statut'), Statut::EN_ATTENTE);

        $this->assertSame([], $appels);
    }

    /**
     * Pas d'effet de bord si le statut natif reflète déjà la cible : couvre
     * le cas où appliquerStatut() a déjà été refusé (statut final) mais où le
     * hook serait quand même invoqué, et le cas d'une ré-application manuelle
     * du même statut par le prophète dans l'admin.
     */
    public function test_aucun_second_effet_si_le_statut_natif_reflete_deja_la_cible(): void
    {
        Functions\when('get_post_type')->justReturn(Don::SLUG);
        Functions\when('get_post_status')->justReturn(Don::STATUT_RECU);
        $appels = [];
        Functions\when('wp_update_post')->alias(function ($args) use (&$appels) {
            $appels[] = $args;

            return (int) $args['ID'];
        });

        Don::synchroniserStatutDepuisPaiement(1, 42, Don::metaKey('paiement_statut'), Statut::PAYE);

        $this->assertSame([], $appels);
    }

    public function test_une_meta_qui_n_est_pas_le_statut_de_paiement_est_ignoree(): void
    {
        Functions\when('get_post_type')->justReturn(Don::SLUG);
        $appels = [];
        Functions\when('wp_update_post')->alias(function ($args) use (&$appels) {
            $appels[] = $args;

            return (int) $args['ID'];
        });

        Don::synchroniserStatutDepuisPaiement(1, 42, Don::metaKey('montant'), Statut::PAYE);

        $this->assertSame([], $appels);
    }

    public function test_une_meta_sur_un_autre_type_de_publication_est_ignoree(): void
    {
        Functions\when('get_post_type')->justReturn('rendez_vous');
        $appels = [];
        Functions\when('wp_update_post')->alias(function ($args) use (&$appels) {
            $appels[] = $args;

            return (int) $args['ID'];
        });

        Don::synchroniserStatutDepuisPaiement(1, 42, Don::metaKey('paiement_statut'), Statut::PAYE);

        $this->assertSame([], $appels);
    }

    /**
     * Le gestionnaire est bien câblé sur les deux actions WordPress qui
     * suivent une écriture de méta post (ajout ou mise à jour) : une méta qui
     * n'existait pas encore (première écriture après webhook/retour) déclenche
     * `added_post_meta`, une méta déjà présente (ré-écriture manuelle par le
     * prophète) déclenche `updated_post_meta`.
     */
    public function test_le_gestionnaire_est_cable_sur_l_ajout_et_la_mise_a_jour_de_meta(): void
    {
        $hooksEnregistres = [];
        Functions\when('register_post_type')->justReturn(true);
        Functions\when('register_post_status')->justReturn(true);
        Functions\when('add_action')->alias(function ($hook, $callback) use (&$hooksEnregistres) {
            $hooksEnregistres[] = $hook;
        });
        Functions\when('add_filter')->justReturn(true);
        Functions\when('_n_noop')->returnArg();

        Don::register();

        $this->assertContains('updated_post_meta', $hooksEnregistres);
        $this->assertContains('added_post_meta', $hooksEnregistres);
    }
}
