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
}
