<?php

declare(strict_types=1);

namespace App\View\Composers;

use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\Options;
use ProphetCore\Rdv\Validator;
use ProphetCore\Services\GoogleCalendar;
use ProphetCore\Support\Date;
use Roots\Acorn\View\Composer;

class Events extends Composer
{
    protected static $views = ['sections.events'];

    public function with(): array
    {
        $service = new GoogleCalendar(
            Options::env('GOOGLE_API_KEY'),
            Options::env('GOOGLE_CALENDAR_ID')
        );

        $events = array_map(
            static fn (array $event): array => $event + ['lien' => self::lien($event)],
            $service->events()
        );

        return [
            'events' => $events,
            'pageSize' => 4,
        ];
    }

    /** Reproduit EventsSection.vue:14-25. */
    private static function lien(array $event): string
    {
        if ($event['places'] !== 'Sur inscription') {
            return home_url('/rdv');
        }

        $mois = array_search($event['month'], array_map(
            static fn (int $i): string => Date::monthAbbrFr($i),
            range(0, 11)
        ), true);

        $date = new DateTimeImmutable(
            sprintf('%s-%02d-%02d', $event['year'], (int) $mois + 1, (int) $event['day']),
            new DateTimeZone('UTC')
        );

        return add_query_arg([
            'event' => $event['title'],
            'eventDate' => $date->format('Y-m-d'),
            'eventLieu' => $event['lieu'],
            'eventHeure' => $event['heure'],
        ], home_url('/rdv'));
    }
}
