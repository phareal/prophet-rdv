<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit;

use Brain\Monkey\Functions;
use ProphetCore\Options;
use ProphetCore\Tests\TestCase;

final class OptionsTest extends TestCase
{
    public function test_le_numero_vient_des_reglages_quand_il_est_saisi(): void
    {
        Functions\when('carbon_get_theme_option')->justReturn('+22800000000');

        $this->assertSame('+22800000000', Options::phone1());
    }

    public function test_le_numero_retombe_sur_l_environnement_quand_le_reglage_est_vide(): void
    {
        Functions\when('carbon_get_theme_option')->justReturn('');
        $_ENV['PROPHET_PHONE_1'] = '+22897169090';

        $this->assertSame('+22897169090', Options::phone1());

        unset($_ENV['PROPHET_PHONE_1']);
    }

    public function test_les_creneaux_horaires_sont_aplatis_depuis_le_repeteur(): void
    {
        Functions\when('carbon_get_theme_option')->justReturn([
            ['valeur' => '08h00'],
            ['valeur' => '09h00'],
        ]);

        $this->assertSame(['08h00', '09h00'], Options::heures());
    }

    public function test_le_libelle_d_un_mode_de_paiement_est_resolu(): void
    {
        Functions\when('carbon_get_theme_option')->justReturn([
            ['valeur' => 'mobile_money', 'libelle' => 'Mobile Money'],
            ['valeur' => 'crypto', 'libelle' => 'Crypto USDT'],
        ]);

        $this->assertSame('Crypto USDT', Options::modePaiementLabel('crypto'));
    }

    public function test_un_mode_de_paiement_inconnu_renvoie_sa_valeur_brute(): void
    {
        Functions\when('carbon_get_theme_option')->justReturn([]);

        $this->assertSame('inconnu', Options::modePaiementLabel('inconnu'));
    }
}
