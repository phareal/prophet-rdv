<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use ProphetCore\Paiement\InitHandler;
use ProphetCore\Paiement\MonerooException;
use ProphetCore\Paiement\ResolveurDeSujet;
use ProphetCore\Paiement\SujetPaiement;
use ProphetCore\Rdv\RendezVousPayable;
use ProphetCore\Tests\TestCase;

final class InitHandlerTest extends TestCase
{
    private array $charge = [];

    protected function setUp(): void
    {
        parent::setUp();
        ResolveurDeSujet::reinitialiser();
    }

    private function contexte(string $statutExistant = 'non_requis', string $prix = '25000'): void
    {
        Functions\when('carbon_get_theme_option')->alias(
            static fn (string $cle) => match ($cle) {
                'moneroo_actif' => true,
                'moneroo_devise' => 'XOF',
                default => '',
            }
        );
        Functions\when('carbon_get_post_meta')->justReturn($prix);
        Functions\when('get_posts')->justReturn([(object) ['ID' => 7, 'post_title' => 'Mariage']]);
        Functions\when('get_post_meta')->alias(
            static fn ($id, $cle, $single) => str_ends_with($cle, 'paiement_statut')
                ? $statutExistant
                : ''
        );
        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('current_time')->justReturn('2026-08-03 10:00:00');
        Functions\when('home_url')->alias(static fn ($c = '/') => 'https://exemple.test' . $c);
        Functions\when('add_query_arg')->alias(
            static fn ($args, $url) => $url . '?' . http_build_query($args)
        );
        Functions\when('wp_json_encode')->alias(static fn ($v) => json_encode($v));
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn(json_encode([
            'data' => ['id' => 'tx_1', 'checkout_url' => 'https://checkout.moneroo.io/tx_1'],
        ]));
        Functions\when('wp_remote_post')->alias(function ($url, $args) {
            $this->charge = json_decode($args['body'], true);

            return [];
        });
        $_ENV['MONEROO_SECRET_KEY'] = 'cle_test';
    }

    private function rdvFactice(): array
    {
        return [
            'nom' => 'Doe', 'prenom' => 'Jane', 'email' => 'jane@example.test',
            'telephone' => '+22890000000', 'pays' => 'Togo',
            'date' => new DateTimeImmutable('2026-08-11'), 'heure' => '09h00',
            'type_consultation' => 'Mariage', 'mode_paiement' => 'mobile_money',
            'message' => '', 'ref' => 'REF123', 'post_id' => 7,
        ];
    }

    public function test_le_montant_envoye_vient_du_service_et_non_de_l_appelant(): void
    {
        $this->contexte();
        $handler = $this->handler($this->rdvFactice());

        $handler->demarrer('REF123');

        $this->assertSame(25000, $this->charge['amount']);
        $this->assertSame('XOF', $this->charge['currency']);
    }

    public function test_l_url_de_retour_porte_la_reference_et_non_l_identifiant_de_publication(): void
    {
        $this->contexte();

        $this->handler($this->rdvFactice())->demarrer('REF123');

        $this->assertStringContainsString('/confirmation/', $this->charge['return_url']);
        $this->assertStringContainsString('REF123', $this->charge['return_url']);
        $this->assertStringNotContainsString('post_id', $this->charge['return_url']);
    }

    public function test_la_reference_voyage_en_metadonnee(): void
    {
        $this->contexte();

        $this->handler($this->rdvFactice())->demarrer('REF123');

        $this->assertSame('REF123', $this->charge['metadata']['ref']);
    }

    public function test_un_rendez_vous_deja_regle_refuse_une_seconde_initialisation(): void
    {
        $this->contexte(statutExistant: 'paye');

        $this->expectException(MonerooException::class);

        $this->handler($this->rdvFactice())->demarrer('REF123');
    }

    public function test_une_reference_inconnue_leve_une_exception(): void
    {
        $this->contexte();

        $this->expectException(MonerooException::class);

        $this->handler(null)->demarrer('INEXISTANTE');
    }

    public function test_un_service_sans_prix_leve_une_exception(): void
    {
        $this->contexte(prix: '');

        $this->expectException(MonerooException::class);

        $this->handler($this->rdvFactice())->demarrer('REF123');
    }

    private function handler(?array $rdv): InitHandler
    {
        ResolveurDeSujet::enregistrer(
            static fn (string $ref): ?SujetPaiement => ($rdv !== null && $ref === 'REF123')
                ? new RendezVousPayable($rdv)
                : null,
            static fn (string $id): ?SujetPaiement => null,
        );

        return new InitHandler();
    }
}
