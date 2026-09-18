<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use ProphetCore\Paiement\ResolveurDeSujet;
use ProphetCore\Paiement\SujetPaiement;
use ProphetCore\Tests\TestCase;
use RuntimeException;

final class ResolveurDeSujetTest extends TestCase
{
    private function sujetFactice(string $ref, string $paiementId = ''): SujetPaiement
    {
        return new class ($ref, $paiementId) implements SujetPaiement {
            public function __construct(
                private readonly string $ref,
                private readonly string $paiementId,
            ) {
            }

            public function postId(): int
            {
                return 7;
            }

            public function reference(): string
            {
                return $this->ref;
            }

            public function description(): string
            {
                return 'Factice';
            }

            public function montant(): ?array
            {
                return ['montant' => 1000, 'devise' => 'XOF'];
            }

            public function client(): array
            {
                return ['email' => 'a@b.test', 'prenom' => 'A', 'nom' => 'B', 'telephone' => ''];
            }

            public function urlRetour(): string
            {
                return 'https://exemple.test/retour';
            }

            public function metaKey(string $champ): string
            {
                return '_factice_' . $champ;
            }

            public function typeDePublication(): string
            {
                return 'factice';
            }
        };
    }

    protected function setUp(): void
    {
        parent::setUp();
        ResolveurDeSujet::reinitialiser();
    }

    public function test_une_reference_connue_donne_son_sujet(): void
    {
        ResolveurDeSujet::enregistrer(
            fn (string $ref): ?SujetPaiement => $ref === 'REF1' ? $this->sujetFactice('REF1') : null,
            fn (string $id): ?SujetPaiement => null,
        );

        $sujet = ResolveurDeSujet::parReference('REF1');

        $this->assertNotNull($sujet);
        $this->assertSame('REF1', $sujet->reference());
    }

    public function test_une_reference_inconnue_donne_null(): void
    {
        ResolveurDeSujet::enregistrer(
            fn (string $ref): ?SujetPaiement => null,
            fn (string $id): ?SujetPaiement => null,
        );

        $this->assertNull(ResolveurDeSujet::parReference('INCONNUE'));
    }

    public function test_le_premier_type_qui_reconnait_la_reference_gagne(): void
    {
        ResolveurDeSujet::enregistrer(
            fn (string $ref): ?SujetPaiement => null,
            fn (string $id): ?SujetPaiement => null,
        );
        ResolveurDeSujet::enregistrer(
            fn (string $ref): ?SujetPaiement => $this->sujetFactice('DEUXIEME'),
            fn (string $id): ?SujetPaiement => null,
        );

        $this->assertSame('DEUXIEME', ResolveurDeSujet::parReference('peu importe')?->reference());
    }

    public function test_la_recherche_par_identifiant_de_transaction_interroge_chaque_type(): void
    {
        ResolveurDeSujet::enregistrer(
            fn (string $ref): ?SujetPaiement => null,
            fn (string $id): ?SujetPaiement => $id === 'tx_1' ? $this->sujetFactice('REF1', 'tx_1') : null,
        );

        $this->assertSame('REF1', ResolveurDeSujet::parPaiementId('tx_1')?->reference());
        $this->assertNull(ResolveurDeSujet::parPaiementId('tx_inconnue'));
    }

    /**
     * Sans ce garde-fou, un résolveur jamais alimenté (erreur de câblage)
     * renverrait silencieusement null pour toute référence : Webhook::traiter()
     * prendrait ce null pour « transaction qui ne nous concerne pas » et
     * répondrait 200 à tout événement — Moneroo ne réessaierait jamais un
     * paiement qui, en réalité, n'a jamais été appliqué.
     */
    public function test_resoudre_par_reference_sans_aucun_type_enregistre_est_bruyant(): void
    {
        $this->expectException(RuntimeException::class);

        ResolveurDeSujet::parReference('REF1');
    }

    public function test_resoudre_par_identifiant_de_transaction_sans_aucun_type_enregistre_est_bruyant(): void
    {
        $this->expectException(RuntimeException::class);

        ResolveurDeSujet::parPaiementId('tx_1');
    }

    /**
     * En pratique, l'enregistrement n'a lieu qu'une fois par requête (voir
     * prophet-core.php) : un doublon est sans effet observable aujourd'hui.
     * Il doit néanmoins être ignoré plutôt qu'empilé, pour ne pas résoudre
     * deux fois le même sujet pour rien si ce jour venait à changer.
     */
    public function test_une_registration_identique_est_ignoree_plutot_qu_empilee(): void
    {
        $parReference = fn (string $ref): ?SujetPaiement => $ref === 'REF1' ? $this->sujetFactice('REF1') : null;
        $parPaiementId = fn (string $id): ?SujetPaiement => null;

        ResolveurDeSujet::enregistrer($parReference, $parPaiementId);
        ResolveurDeSujet::enregistrer($parReference, $parPaiementId);

        $this->assertSame(1, ResolveurDeSujet::nombreDeTypes());
    }

    public function test_deux_registrations_distinctes_sont_toutes_deux_conservees(): void
    {
        ResolveurDeSujet::enregistrer(
            fn (string $ref): ?SujetPaiement => null,
            fn (string $id): ?SujetPaiement => null,
        );
        ResolveurDeSujet::enregistrer(
            fn (string $ref): ?SujetPaiement => null,
            fn (string $id): ?SujetPaiement => null,
        );

        $this->assertSame(2, ResolveurDeSujet::nombreDeTypes());
    }
}
