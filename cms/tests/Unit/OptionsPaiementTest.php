<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit;

use Brain\Monkey\Functions;
use ProphetCore\Options;
use ProphetCore\Tests\TestCase;

final class OptionsPaiementTest extends TestCase
{
    private function options(array $valeurs): void
    {
        Functions\when('carbon_get_theme_option')->alias(
            static fn (string $cle) => $valeurs[$cle] ?? ''
        );
    }

    public function test_le_paiement_est_inactif_par_defaut(): void
    {
        $this->options([]);

        $this->assertFalse(Options::paiementActif());
    }

    public function test_le_paiement_s_active_par_la_case_a_cocher(): void
    {
        $this->options(['moneroo_actif' => true]);

        $this->assertTrue(Options::paiementActif());
    }

    public function test_la_devise_par_defaut_est_le_franc_cfa(): void
    {
        $this->options([]);

        $this->assertSame('XOF', Options::devise());
    }

    public function test_le_moment_par_defaut_est_facultatif(): void
    {
        // Le défaut sûr : un paiement exigé ferait perdre les demandes de tous
        // les visiteurs dont le règlement échoue.
        $this->options([]);

        $this->assertSame('facultatif', Options::momentPaiement());
    }

    public function test_le_moment_exige_est_lu_quand_il_est_configure(): void
    {
        $this->options(['moneroo_moment' => 'exige']);

        $this->assertSame('exige', Options::momentPaiement());
    }

    public function test_un_moment_inconnu_retombe_sur_facultatif(): void
    {
        $this->options(['moneroo_moment' => 'n_importe_quoi']);

        $this->assertSame('facultatif', Options::momentPaiement());
    }

    public function test_le_delai_d_abandon_vaut_trente_minutes_par_defaut(): void
    {
        $this->options([]);

        $this->assertSame(30, Options::delaiAbandon());
    }

    public function test_un_delai_d_abandon_absurde_retombe_sur_le_defaut(): void
    {
        $this->options(['moneroo_delai_abandon' => '0']);

        $this->assertSame(30, Options::delaiAbandon());
    }
}
