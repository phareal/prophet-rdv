<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use RuntimeException;

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

    /**
     * En pratique, l'enregistrement n'a lieu qu'une fois par requête, sur
     * `init` (voir prophet-core.php) : un doublon est donc sans effet
     * observable aujourd'hui. Il est tout de même ignoré plutôt qu'empilé —
     * la même paire de fabriques enregistrée deux fois ne doit pas faire
     * résoudre le même sujet deux fois pour rien.
     */
    public static function enregistrer(callable $parReference, callable $parPaiementId): void
    {
        foreach (self::$fabriques as $fabrique) {
            if ($fabrique[0] == $parReference && $fabrique[1] == $parPaiementId) {
                return;
            }
        }

        self::$fabriques[] = [$parReference, $parPaiementId];
    }

    public static function reinitialiser(): void
    {
        self::$fabriques = [];
    }

    /** Nombre de types actuellement enregistrés — introspection, pour les tests. */
    public static function nombreDeTypes(): int
    {
        return count(self::$fabriques);
    }

    public static function parReference(string $ref): ?SujetPaiement
    {
        self::verifierEnregistrement();

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
        self::verifierEnregistrement();

        foreach (self::$fabriques as [$_, $parPaiementId]) {
            $sujet = $parPaiementId($paiementId);

            if ($sujet instanceof SujetPaiement) {
                return $sujet;
            }
        }

        return null;
    }

    /**
     * Sans ce garde-fou, un résolveur jamais alimenté (erreur de câblage :
     * l'enregistrement sur `init` retiré ou jamais atteint) renverrait
     * silencieusement null pour toute référence et tout identifiant de
     * transaction. Webhook::traiter() prend alors ce null pour « transaction
     * qui ne nous concerne pas » et répond 200 à tout — Moneroo ne
     * réessaierait donc jamais un paiement qui, en réalité, n'a jamais été
     * appliqué. Une panne totale et silencieuse. Ici, elle est bruyante.
     */
    private static function verifierEnregistrement(): void
    {
        if (self::$fabriques === []) {
            throw new RuntimeException(
                'ResolveurDeSujet : aucun type payable enregistré. '
                . 'ResolveurDeSujet::enregistrer() n\'a jamais été appelé — vérifier le câblage sur `init`.'
            );
        }
    }
}
