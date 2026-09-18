<?php

declare(strict_types=1);

namespace ProphetCore\Support;

/**
 * Une référence de don ou de rendez-vous n'est pas une donnée personnelle,
 * mais c'est le jeton qui ouvre la page de paiement/retour d'un visiteur
 * (nom, montant, bouton de paiement) — /don/merci/?ref=…, /confirmation/?ref=…
 * Les journaux serveur sont déjà une surface sensible ; tronquer suffit à
 * diagnostiquer un incident (corréler les lignes d'un même événement) sans y
 * faire figurer un jeton exploitable en clair.
 */
final class Journal
{
    private const LONGUEUR_REFERENCE = 8;

    public static function tronquerReference(string $reference): string
    {
        return substr($reference, 0, self::LONGUEUR_REFERENCE);
    }
}
