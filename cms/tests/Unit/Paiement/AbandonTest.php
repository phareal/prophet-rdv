<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\Abandon;
use ProphetCore\Paiement\Statut;
use ProphetCore\PostTypes\RendezVous;
use ProphetCore\Tests\TestCase;

final class AbandonTest extends TestCase
{
    private array $misAJour = [];

    private function contexte(string $moment, array $candidats): void
    {
        $this->misAJour = [];

        Functions\when('carbon_get_theme_option')->alias(
            static fn (string $cle) => match ($cle) {
                'moneroo_moment' => $moment,
                'moneroo_delai_abandon' => '30',
                default => '',
            }
        );
        Functions\when('get_posts')->justReturn($candidats);
        Functions\when('get_post_meta')->justReturn(Statut::EN_ATTENTE);
        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('current_time')->justReturn('2026-08-03 10:00:00');
        Functions\when('wp_update_post')->alias(function ($args) {
            $this->misAJour[] = $args;

            return $args['ID'];
        });
        Functions\when('error_log')->justReturn(true);
    }

    public function test_en_mode_facultatif_aucun_rendez_vous_n_est_annule(): void
    {
        // Le paiement n'y conditionne pas la demande : l'annuler serait absurde.
        $this->contexte('facultatif', [7, 8]);
        Functions\expect('wp_update_post')->never();

        $this->assertSame(0, (new Abandon())->passer());
    }

    public function test_en_mode_exige_les_rendez_vous_en_souffrance_sont_annules(): void
    {
        $this->contexte('exige', [7, 8]);

        $annules = (new Abandon())->passer();

        $this->assertSame(2, $annules);
        $this->assertSame(RendezVous::STATUT_ANNULE, $this->misAJour[0]['post_status']);
        $this->assertSame(7, $this->misAJour[0]['ID']);
    }

    public function test_l_annulation_marque_aussi_le_paiement_comme_annule(): void
    {
        $this->contexte('exige', [7]);
        $ecrites = [];
        Functions\when('update_post_meta')->alias(function ($id, $cle, $v) use (&$ecrites) {
            $ecrites[$cle] = $v;

            return true;
        });

        (new Abandon())->passer();

        $this->assertSame(Statut::ANNULE, $ecrites[RendezVous::metaKey('paiement_statut')]);
    }

    public function test_la_requete_ne_retient_que_les_paiements_en_attente(): void
    {
        $capture = [];
        $this->contexte('exige', []);
        Functions\when('get_posts')->alias(function ($args) use (&$capture) {
            $capture = $args;

            return [];
        });

        (new Abandon())->passer();

        $this->assertSame(RendezVous::SLUG, $capture['post_type']);
        $this->assertSame([RendezVous::STATUT_EN_ATTENTE], $capture['post_status']);
        $this->assertSame(
            RendezVous::metaKey('paiement_statut'),
            $capture['meta_query'][0]['key']
        );
        $this->assertSame(Statut::EN_ATTENTE, $capture['meta_query'][0]['value']);
    }
}
