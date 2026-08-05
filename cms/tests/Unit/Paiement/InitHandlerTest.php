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
        Functions\when('wp_unslash')->returnArg();
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

    /**
     * L'écran de remerciement du don propose « Réessayer le paiement » vers
     * cette action. Si l'erreur redirige toujours vers /confirmation/ (la
     * page du rendez-vous), un donateur atterrit sur une page qui ne
     * connaît pas sa référence et affiche « lien invalide » — il croira son
     * don perdu.
     */
    public function test_une_erreur_redirige_vers_l_url_de_retour_du_sujet_et_non_vers_confirmation(): void
    {
        $this->contexte();
        Functions\when('error_log')->justReturn(true);
        $_GET['ref'] = 'REFDON';
        $redirection = null;
        Functions\when('wp_safe_redirect')->alias(function ($url) use (&$redirection) {
            $redirection = $url;
        });

        $sujet = new class implements SujetPaiement {
            public function postId(): int
            {
                return 42;
            }

            public function reference(): string
            {
                return 'REFDON';
            }

            public function description(): string
            {
                return 'Don';
            }

            public function montant(): ?array
            {
                return null;
            }

            public function client(): array
            {
                return ['email' => '', 'prenom' => '', 'nom' => '', 'telephone' => ''];
            }

            public function urlRetour(): string
            {
                return 'https://exemple.test/don/merci/?ref=REFDON';
            }

            public function metaKey(string $champ): string
            {
                return '_don_' . $champ;
            }

            public function typeDePublication(): string
            {
                return 'don';
            }
        };

        ResolveurDeSujet::enregistrer(
            static fn (string $ref): ?SujetPaiement => $ref === 'REFDON' ? $sujet : null,
            static fn (string $id): ?SujetPaiement => null,
        );

        $handler = new class extends InitHandler {
            protected function terminer(): void
            {
            }
        };
        $handler->handle();

        unset($_GET['ref']);

        $this->assertStringContainsString('/don/merci/', (string) $redirection);
        $this->assertStringContainsString('paiement=erreur', (string) $redirection);
        $this->assertStringNotContainsString('/confirmation/', (string) $redirection);
    }

    public function test_une_reference_qui_ne_resout_aucun_sujet_retombe_sur_confirmation(): void
    {
        $this->contexte();
        // Un type est bien enregistré (comme en production) ; c'est cette
        // référence précise qu'aucun type ne reconnaît — distinct du cas
        // « aucun type n'a jamais été enregistré » (ResolveurDeSujetTest).
        ResolveurDeSujet::enregistrer(
            static fn (string $ref): ?SujetPaiement => null,
            static fn (string $id): ?SujetPaiement => null,
        );
        Functions\when('error_log')->justReturn(true);
        $_GET['ref'] = 'INCONNUE';
        $redirection = null;
        Functions\when('wp_safe_redirect')->alias(function ($url) use (&$redirection) {
            $redirection = $url;
        });

        $handler = new class extends InitHandler {
            protected function terminer(): void
            {
            }
        };
        $handler->handle();

        unset($_GET['ref']);

        $this->assertStringContainsString('/confirmation/', (string) $redirection);
        $this->assertStringContainsString('ref=INCONNUE', (string) $redirection);
    }

    /**
     * La référence est le jeton qui ouvre /don/merci/?ref=…, pas une donnée
     * personnelle — mais un jeton exploitable tout de même. Les journaux
     * serveur sont déjà une surface sensible : huit caractères suffisent à
     * corréler les lignes d'un même incident sans y faire figurer le jeton
     * complet.
     */
    public function test_le_journal_ne_contient_que_les_huit_premiers_caracteres_de_la_reference(): void
    {
        $this->contexte();
        ResolveurDeSujet::enregistrer(
            static fn (string $ref): ?SujetPaiement => null,
            static fn (string $id): ?SujetPaiement => null,
        );
        $ref = 'ABCDEFGH1234567890IJKLMNOPQRSTUV';
        $_GET['ref'] = $ref;
        $messages = [];
        Functions\when('error_log')->alias(function ($message) use (&$messages) {
            $messages[] = $message;

            return true;
        });
        Functions\when('wp_safe_redirect')->justReturn(true);

        $handler = new class extends InitHandler {
            protected function terminer(): void
            {
            }
        };
        $handler->handle();

        unset($_GET['ref']);

        $this->assertNotEmpty($messages);
        $this->assertStringContainsString(substr($ref, 0, 8), $messages[0]);
        $this->assertStringNotContainsString(substr($ref, 8), $messages[0]);
    }
}
