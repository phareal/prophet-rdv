<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use ProphetCore\Don\DonSubmitHandler;
use ProphetCore\Tests\TestCase;

final class DonSubmitHandlerTest extends TestCase
{
    public function test_la_limite_porte_sur_le_couple_ip_et_email(): void
    {
        // Une limite par IP seule bloquerait une assemblée entière : deux cents
        // personnes qui scannent le même QR pendant un culte sortent par la
        // même adresse publique.
        $a = DonSubmitHandler::cleDeLimite('203.0.113.7', 'jane@example.test');
        $b = DonSubmitHandler::cleDeLimite('203.0.113.7', 'marc@example.test');

        $this->assertNotSame($a, $b);
        $this->assertSame($a, DonSubmitHandler::cleDeLimite('203.0.113.7', 'jane@example.test'));
    }

    public function test_l_email_est_normalise_dans_la_cle(): void
    {
        $this->assertSame(
            DonSubmitHandler::cleDeLimite('203.0.113.7', 'Jane@Example.test'),
            DonSubmitHandler::cleDeLimite('203.0.113.7', 'jane@example.test'),
        );
    }

    public function test_un_pot_de_miel_rempli_est_traite_comme_un_robot(): void
    {
        $this->assertTrue(DonSubmitHandler::isBot(['prophet_site' => 'http://spam.test']));
        $this->assertFalse(DonSubmitHandler::isBot(['prophet_site' => '']));
        $this->assertFalse(DonSubmitHandler::isBot([]));
    }
}
