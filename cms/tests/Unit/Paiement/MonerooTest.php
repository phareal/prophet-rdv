<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\Moneroo;
use ProphetCore\Paiement\MonerooException;
use ProphetCore\Tests\TestCase;

final class MonerooTest extends TestCase
{
    private array $appel = [];

    private function reponse(array $corps, int $code = 200): void
    {
        Functions\when('wp_remote_post')->alias(function ($url, $args) use ($corps, $code) {
            $this->appel = ['url' => $url, 'args' => $args];

            return ['body' => json_encode($corps), 'response' => ['code' => $code]];
        });
        Functions\when('wp_remote_get')->alias(function ($url, $args) use ($corps, $code) {
            $this->appel = ['url' => $url, 'args' => $args];

            return ['body' => json_encode($corps), 'response' => ['code' => $code]];
        });
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_body')->alias(
            static fn ($r) => $r['body']
        );
        Functions\when('wp_remote_retrieve_response_code')->alias(
            static fn ($r) => $r['response']['code']
        );
    }

    public function test_l_initialisation_renvoie_l_identifiant_et_l_url_de_paiement(): void
    {
        $this->reponse([
            'message' => 'Transaction initialized successfully',
            'data' => ['id' => '5f7b1b2c', 'checkout_url' => 'https://checkout.moneroo.io/5f7b1b2c'],
        ]);

        $resultat = (new Moneroo('cle_test'))->initialiser(['amount' => 25000]);

        $this->assertSame('5f7b1b2c', $resultat['id']);
        $this->assertSame('https://checkout.moneroo.io/5f7b1b2c', $resultat['checkout_url']);
    }

    public function test_la_cle_secrete_part_en_bearer_et_le_corps_en_json(): void
    {
        $this->reponse(['data' => ['id' => 'x', 'checkout_url' => 'https://c/x']]);

        (new Moneroo('cle_test'))->initialiser(['amount' => 25000, 'currency' => 'XOF']);

        $this->assertSame('https://api.moneroo.io/v1/payments/initialize', $this->appel['url']);
        $this->assertSame('Bearer cle_test', $this->appel['args']['headers']['Authorization']);
        $this->assertSame('application/json', $this->appel['args']['headers']['Content-Type']);
        $this->assertSame(
            ['amount' => 25000, 'currency' => 'XOF'],
            json_decode($this->appel['args']['body'], true)
        );
    }

    public function test_une_erreur_reseau_leve_une_exception(): void
    {
        Functions\when('wp_remote_post')->justReturn('erreur');
        Functions\when('is_wp_error')->justReturn(true);
        Functions\when('wp_remote_retrieve_body')->justReturn('');

        $this->expectException(MonerooException::class);

        (new Moneroo('cle_test'))->initialiser(['amount' => 1]);
    }

    public function test_un_code_http_d_erreur_leve_une_exception(): void
    {
        $this->reponse(['message' => 'Invalid amount'], 422);

        $this->expectException(MonerooException::class);

        (new Moneroo('cle_test'))->initialiser(['amount' => -1]);
    }

    public function test_une_reponse_sans_url_de_paiement_leve_une_exception(): void
    {
        $this->reponse(['data' => ['id' => 'x']]);

        $this->expectException(MonerooException::class);

        (new Moneroo('cle_test'))->initialiser(['amount' => 1]);
    }

    public function test_la_recuperation_interroge_la_transaction_par_son_identifiant(): void
    {
        $this->reponse(['data' => ['id' => 'abc', 'status' => 'success', 'amount' => 25000]]);

        $donnees = (new Moneroo('cle_test'))->recuperer('abc');

        $this->assertSame('https://api.moneroo.io/v1/payments/abc', $this->appel['url']);
        $this->assertSame('success', $donnees['status']);
    }

    public function test_une_cle_secrete_vide_leve_une_exception_sans_appel_reseau(): void
    {
        Functions\expect('wp_remote_post')->never();

        $this->expectException(MonerooException::class);

        (new Moneroo(''))->initialiser(['amount' => 1]);
    }
}
