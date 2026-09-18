<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

final class Statut
{
    public const NON_REQUIS = 'non_requis';
    public const EN_ATTENTE = 'en_attente';
    public const PAYE = 'paye';
    public const ECHOUE = 'echoue';
    public const ANNULE = 'annule';

    private const DEPUIS_MONEROO = [
        'initiated' => self::EN_ATTENTE,
        'pending' => self::EN_ATTENTE,
        'success' => self::PAYE,
        'failed' => self::ECHOUE,
        'cancelled' => self::ANNULE,
    ];

    private const LIBELLES = [
        self::NON_REQUIS => 'Non requis',
        self::EN_ATTENTE => 'En attente',
        self::PAYE => 'Réglé',
        self::ECHOUE => 'Échoué',
        self::ANNULE => 'Annulé',
    ];

    /**
     * Un statut inconnu retombe sur « en attente » : jamais sur « réglé », qui
     * créditerait à tort, ni sur un échec définitif, qui fermerait la porte.
     */
    public static function depuisMoneroo(string $statutMoneroo): string
    {
        return self::DEPUIS_MONEROO[$statutMoneroo] ?? self::EN_ATTENTE;
    }

    public static function estFinal(string $statut): bool
    {
        return in_array($statut, [self::PAYE, self::ECHOUE, self::ANNULE], true);
    }

    public static function libelle(string $statut): string
    {
        return self::LIBELLES[$statut] ?? $statut;
    }
}
