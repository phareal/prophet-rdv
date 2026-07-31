<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\PostTypes;

use Brain\Monkey\Functions;
use ProphetCore\PostTypes\RendezVous;
use ProphetCore\Tests\TestCase;

final class RendezVousTest extends TestCase
{
    public function test_le_type_n_est_pas_public_mais_visible_en_administration(): void
    {
        $capture = [];
        $statuts = [];

        Functions\when('register_post_type')->alias(function ($slug, $args) use (&$capture) {
            $capture = ['slug' => $slug, 'args' => $args];
        });
        Functions\when('register_post_status')->alias(function ($slug) use (&$statuts) {
            $statuts[] = $slug;
        });
        Functions\when('add_action')->alias(fn ($hook, $callback) => $callback());
        Functions\when('add_filter')->justReturn(true);
        Functions\when('_n_noop')->returnArg();

        RendezVous::register();

        $this->assertSame('rendez_vous', $capture['slug']);
        $this->assertFalse($capture['args']['public']);
        $this->assertTrue($capture['args']['show_ui']);
        $this->assertFalse($capture['args']['publicly_queryable']);
        $this->assertSame(['title'], $capture['args']['supports']);
        $this->assertContains('prophet_en_attente', $statuts);
        $this->assertContains('prophet_confirme', $statuts);
        $this->assertContains('prophet_annule', $statuts);
    }

    public function test_les_colonnes_d_administration_sont_definies(): void
    {
        $colonnes = RendezVous::adminColumns([]);

        $this->assertSame(
            ['cb', 'title', 'rdv_date', 'rdv_type', 'rdv_statut'],
            array_keys($colonnes)
        );
    }

    public function test_le_prefixe_de_meta_correspond_a_celui_impose_par_carbon_fields(): void
    {
        $this->assertSame('_rdv_', RendezVous::META_PREFIX);
    }

    public function test_meta_key_prefixe_le_champ_donne(): void
    {
        $this->assertSame('_rdv_date', RendezVous::metaKey('date'));
    }
}
