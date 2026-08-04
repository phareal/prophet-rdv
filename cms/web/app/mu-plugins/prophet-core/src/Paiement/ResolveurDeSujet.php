<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

/**
 * Chaque type payable s'enregistre avec deux fabriques : l'une résout une
 * référence publique, l'autre un identifiant de transaction Moneroo. Les points
 * d'entrée (initialisation, retour, webhook) n'ont ainsi plus à connaître les
 * types concrets.
 */
final class ResolveurDeSujet
{
    /** @var array<int, array{0: callable, 1: callable}> */
    private static array $fabriques = [];

    public static function enregistrer(callable $parReference, callable $parPaiementId): void
    {
        self::$fabriques[] = [$parReference, $parPaiementId];
    }

    public static function reinitialiser(): void
    {
        self::$fabriques = [];
    }

    public static function parReference(string $ref): ?SujetPaiement
    {
        foreach (self::$fabriques as [$parReference, $_]) {
            $sujet = $parReference($ref);

            if ($sujet instanceof SujetPaiement) {
                return $sujet;
            }
        }

        return null;
    }

    public static function parPaiementId(string $paiementId): ?SujetPaiement
    {
        foreach (self::$fabriques as [$_, $parPaiementId]) {
            $sujet = $parPaiementId($paiementId);

            if ($sujet instanceof SujetPaiement) {
                return $sujet;
            }
        }

        return null;
    }
}
