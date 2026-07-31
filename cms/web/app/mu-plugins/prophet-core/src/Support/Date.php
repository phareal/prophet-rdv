<?php

declare(strict_types=1);

namespace ProphetCore\Support;

use DateTimeImmutable;
use IntlDateFormatter;

final class Date
{
    /** Table reprise de api-bridge/src/services/calendar.ts:6-9. */
    private const MOIS_ABREGES = [
        'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin',
        'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc',
    ];

    /**
     * Reproduit Intl.DateTimeFormat('fr-FR', {weekday, year, month, day})
     * utilisé par lib/utils.ts:13-20.
     */
    public static function formatFr(DateTimeImmutable $date): string
    {
        $formatter = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $date->getTimezone(),
            IntlDateFormatter::GREGORIAN,
            'EEEE d MMMM y'
        );

        return $formatter->format($date);
    }

    public static function monthAbbrFr(int $monthIndexZeroBased): string
    {
        return self::MOIS_ABREGES[$monthIndexZeroBased];
    }

    public static function formatTime(DateTimeImmutable $date): string
    {
        return $date->format('H') . 'h' . $date->format('i');
    }
}
