<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use ProphetCore\Paiement\ResolveurDeSujet;
use ProphetCore\Paiement\SujetPaiement;
use ProphetCore\Tests\TestCase;

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
}
