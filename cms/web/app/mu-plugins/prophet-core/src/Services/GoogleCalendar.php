<?php

declare(strict_types=1);

namespace ProphetCore\Services;

use DateTimeImmutable;
use ProphetCore\Support\Date;

final class GoogleCalendar
{
    public const TRANSIENT = 'prophet_calendar_events';
    public const TTL = 600; // 10 minutes, TTL du bridge Node.
    public const TTL_ECHEC = 60;

    /** Reprises de server/api/calendar/events.get.ts:1-40. */
    public const MOCK_EVENTS = [
        [
            'day' => '18', 'month' => 'Avr', 'year' => '2026',
            'title' => '[MOCK] Nuit de Prophétie & Délivrance',
            'lieu' => 'Paris, France', 'heure' => '20h00 – 00h00',
            'places' => 'Entrée libre', 'type' => 'Croisade', 'featured' => true,
        ],
        [
            'day' => '26', 'month' => 'Avr', 'year' => '2026',
            'title' => '[MOCK] Conférence des Leaders & Entrepreneurs',
            'lieu' => "Abidjan, Côte d'Ivoire", 'heure' => '09h00 – 17h00',
            'places' => 'Places limitées', 'type' => 'Conférence', 'featured' => false,
        ],
        [
            'day' => '10', 'month' => 'Mai', 'year' => '2026',
            'title' => '[MOCK] Crusade Prophétique Internationale',
            'lieu' => 'Lagos, Nigeria', 'heure' => '18h00 – 23h00',
            'places' => 'Entrée libre', 'type' => 'Croisade', 'featured' => false,
        ],
        [
            'day' => '24', 'month' => 'Mai', 'year' => '2026',
            'title' => '[MOCK] Retraite Spirituelle — Activation des Appels',
            'lieu' => 'Montréal, Canada', 'heure' => '2 jours (Sam & Dim)',
            'places' => 'Sur inscription', 'type' => 'Retraite', 'featured' => false,
        ],
    ];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $calendarId,
    ) {
    }

    public function events(): array
    {
        $cached = get_transient(self::TRANSIENT);
        if (is_array($cached)) {
            return $cached;
        }

        $events = $this->fetch();

        if ($events === null) {
            // Un échec ne fige pas les données de démonstration pour 10 minutes :
            // le site se remet tout seul à la minute suivante, sans pour autant
            // rappeler l'API à chaque affichage de page.
            set_transient(self::TRANSIENT, self::MOCK_EVENTS, self::TTL_ECHEC);

            return self::MOCK_EVENTS;
        }

        set_transient(self::TRANSIENT, $events, self::TTL);

        return $events;
    }

    /** Renvoie null en cas d'échec (clé absente, erreur HTTP, réponse inattendue). */
    private function fetch(): ?array
    {
        if ($this->apiKey === '' || $this->calendarId === '') {
            return null;
        }

        $url = 'https://www.googleapis.com/calendar/v3/calendars/'
            . rawurlencode($this->calendarId) . '/events?' . http_build_query([
                'key' => $this->apiKey,
                'timeMin' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
                'orderBy' => 'startTime',
                'singleEvents' => 'true',
                'maxResults' => '10',
            ]);

        $response = wp_remote_get($url, ['timeout' => 8]);

        if (is_wp_error($response)) {
            error_log('[Calendar] appel en échec, retour des données de démonstration');

            return null;
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);

        if (! is_array($payload) || ! isset($payload['items'])) {
            error_log('[Calendar] réponse inattendue, retour des données de démonstration');

            return null;
        }

        return self::parseItems($payload['items']);
    }

    /** Port de api-bridge/src/services/calendar.ts:66-97. */
    public static function parseItems(array $items): array
    {
        $events = [];

        foreach ($items as $item) {
            // Pas `empty()` : en PHP `empty('0')` est vrai, alors que le bridge
            // conserve un titre valant littéralement « 0 ».
            if (($item['summary'] ?? '') === '') {
                continue;
            }

            $description = (string) ($item['description'] ?? '');
            $startRaw = $item['start']['dateTime'] ?? $item['start']['date'] ?? '';
            $start = new DateTimeImmutable($startRaw);
            $endRaw = $item['end']['dateTime'] ?? null;

            if (isset($item['start']['dateTime'])) {
                $heure = $endRaw !== null
                    ? Date::formatTime($start) . ' – ' . Date::formatTime(new DateTimeImmutable($endRaw))
                    : Date::formatTime($start);
            } else {
                $heure = '2 jours';
            }

            $events[] = [
                'day' => str_pad($start->format('j'), 2, '0', STR_PAD_LEFT),
                'month' => Date::monthAbbrFr((int) $start->format('n') - 1),
                'year' => $start->format('Y'),
                'title' => (string) $item['summary'],
                'lieu' => self::parseField($description, 'lieu') ?: (string) ($item['location'] ?? ''),
                'heure' => $heure,
                'places' => self::parseField($description, 'places') ?: 'Entrée libre',
                'type' => self::parseField($description, 'type') ?: 'Conférence',
                'featured' => $events === [],
            ];
        }

        return $events;
    }

    private static function parseField(string $description, string $key): string
    {
        $plain = preg_replace('/<[^>]+>/', "\n", $description) ?? '';
        $plain = str_replace('&nbsp;', ' ', $plain);
        $plain = preg_replace('/\n{2,}/', "\n", $plain) ?? '';

        if (preg_match('/' . preg_quote($key, '/') . ':\s*([^\n]+)/u', $plain, $matches) === 1) {
            return trim($matches[1]);
        }

        return '';
    }
}
