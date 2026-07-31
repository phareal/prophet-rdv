<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Rdv;

use Brain\Monkey\Functions;
use ProphetCore\Rdv\SubmitHandler;
use ProphetCore\Tests\TestCase;

final class SubmitHandlerTest extends TestCase
{
    public function test_la_cle_de_limitation_depend_de_l_adresse_ip(): void
    {
        $this->assertSame(
            'prophet_rdv_ip_' . md5('203.0.113.7'),
            SubmitHandler::rateLimitKey('203.0.113.7'),
        );
    }

    public function test_un_pot_de_miel_rempli_est_traite_comme_un_robot(): void
    {
        $this->assertTrue(SubmitHandler::isBot(['prophet_website' => 'http://spam.test']));
        $this->assertFalse(SubmitHandler::isBot(['prophet_website' => '']));
        $this->assertFalse(SubmitHandler::isBot([]));
    }

    public function test_un_nonce_invalide_renvoie_vers_le_formulaire_sans_rien_enregistrer(): void
    {
        $redirection = null;

        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\when('wp_unslash')->returnArg();
        Functions\when('home_url')->alias(fn($chemin = '/') => 'https://exemple.test' . $chemin);
        Functions\when('add_query_arg')->alias(
            fn($args, $url) => $url . '?' . http_build_query($args),
        );
        Functions\when('wp_generate_password')->justReturn('CLE');
        Functions\when('set_transient')->justReturn(true);
        Functions\when('wp_safe_redirect')->alias(function ($url) use (&$redirection) {
            $redirection = $url;
        });
        Functions\expect('wp_insert_post')->never();

        $handler = new class extends SubmitHandler {
            protected function terminer(): void {}
        };
        $handler->process(['prophet_nonce' => 'faux'], '203.0.113.7');

        $this->assertStringContainsString('/rdv?', (string) $redirection);
        $this->assertStringContainsString('e=CLE', (string) $redirection);
    }
}
