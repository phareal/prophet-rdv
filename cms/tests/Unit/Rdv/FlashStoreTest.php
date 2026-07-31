<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Rdv;

use Brain\Monkey\Functions;
use ProphetCore\Rdv\FlashStore;
use ProphetCore\Tests\TestCase;

final class FlashStoreTest extends TestCase
{
    public function test_la_cle_est_aleatoire_et_le_contenu_stocke_avec_un_ttl_court(): void
    {
        $capture = [];

        Functions\when('wp_generate_password')->justReturn('CLE123');
        Functions\when('set_transient')->alias(function ($key, $value, $ttl) use (&$capture) {
            $capture = compact('key', 'value', 'ttl');

            return true;
        });

        $cle = FlashStore::put(['errors' => ['nom' => 'requis']]);

        $this->assertSame('CLE123', $cle);
        $this->assertSame('prophet_flash_CLE123', $capture['key']);
        $this->assertSame(300, $capture['ttl']);
        $this->assertSame(['errors' => ['nom' => 'requis']], $capture['value']);
    }

    public function test_la_lecture_supprime_le_transient(): void
    {
        $supprime = null;

        Functions\when('get_transient')->justReturn(['errors' => []]);
        Functions\when('delete_transient')->alias(function ($key) use (&$supprime) {
            $supprime = $key;

            return true;
        });

        $payload = FlashStore::pull('CLE123');

        $this->assertSame(['errors' => []], $payload);
        $this->assertSame('prophet_flash_CLE123', $supprime);
    }

    public function test_une_cle_inconnue_renvoie_null(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('delete_transient')->justReturn(true);

        $this->assertNull(FlashStore::pull('INCONNUE'));
    }
}
