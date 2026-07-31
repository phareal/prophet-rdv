<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Rdv;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\PostTypes\RendezVous;
use ProphetCore\Rdv\Repository;
use ProphetCore\Tests\TestCase;

final class RepositoryTest extends TestCase
{
    public function test_un_creneau_deja_pris_est_detecte(): void
    {
        Functions\when('get_posts')->justReturn([42]);

        $this->assertTrue((new Repository())->slotTaken('2026-08-05', '09h00'));
    }

    public function test_un_creneau_libre_n_est_pas_detecte(): void
    {
        Functions\when('get_posts')->justReturn([]);

        $this->assertFalse((new Repository())->slotTaken('2026-08-05', '09h00'));
    }

    public function test_la_recherche_exclut_les_rendez_vous_annules(): void
    {
        $capture = [];

        Functions\when('get_posts')->alias(function ($args) use (&$capture) {
            $capture = $args;

            return [];
        });

        (new Repository())->slotTaken('2026-08-05', '09h00');

        $this->assertSame(RendezVous::SLUG, $capture['post_type']);
        $this->assertSame(
            [RendezVous::STATUT_EN_ATTENTE, RendezVous::STATUT_CONFIRME],
            $capture['post_status']
        );
    }

    public function test_la_creation_enregistre_les_metadonnees_et_renvoie_une_reference(): void
    {
        $metas = [];

        Functions\when('wp_generate_password')->justReturn('REF0123456789');
        Functions\when('wp_insert_post')->justReturn(7);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('update_post_meta')->alias(function ($id, $key, $value) use (&$metas) {
            $metas[$key] = $value;

            return true;
        });

        $ref = (new Repository())->create([
            'nom' => 'Doe', 'prenom' => 'Jane', 'email' => 'jane@example.test',
            'telephone' => '+22890000000', 'pays' => 'Togo',
            'date' => new DateTimeImmutable('2026-08-05', new DateTimeZone('UTC')),
            'heure' => '09h00', 'type_consultation' => 'Mariage',
            'mode_paiement' => 'mobile_money', 'message' => 'Merci.',
        ]);

        $this->assertSame('REF0123456789', $ref);
        $this->assertSame('2026-08-05', $metas['_rdv_date']);
        $this->assertSame('09h00', $metas['_rdv_heure']);
        $this->assertSame('Mariage', $metas['_rdv_type_consultation']);
        $this->assertSame('REF0123456789', $metas['_rdv_ref']);

        // Le préfixe de Carbon Fields n'est pas cosmétique : sans lui, le
        // panneau d'administration du rendez-vous s'affiche vide.
        foreach (array_keys($metas) as $cle) {
            $this->assertStringStartsWith('_rdv_', $cle);
        }
    }

    public function test_une_reference_inconnue_renvoie_null(): void
    {
        Functions\when('get_posts')->justReturn([]);

        $this->assertNull((new Repository())->findByRef('INCONNUE'));
    }
}
