<?php

declare(strict_types=1);

namespace ProphetCore\Services;

final class Youtube
{
    public const TRANSIENT = 'prophet_youtube_videos';
    public const TTL = 1800; // 30 minutes, TTL du bridge Node.
    public const TTL_ECHEC = 60; // Un échec n'est mis en cache qu'une minute.

    /** Reprises de server/api/youtube/latest.get.ts:1-27. */
    public const MOCK_VIDEOS = [
        [
            'id' => 'dQw4w9WgXcQ',
            'title' => '[MOCK] Prophétie sur les nations — 2026',
            'desc' => 'Le Prophète Jeremiah annonce ce que Dieu prépare pour les nations en cette nouvelle saison.',
            'thumbnail' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            'type' => 'Prophétie', 'duration' => '',
        ],
        [
            'id' => 'dQw4w9WgXcQ',
            'title' => '[MOCK] Consultation prophétique en direct',
            'desc' => 'Session de consultations prophétiques en direct avec des révélations précises et vérifiables.',
            'thumbnail' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            'type' => 'En direct', 'duration' => '',
        ],
        [
            'id' => 'dQw4w9WgXcQ',
            'title' => '[MOCK] Témoignages — Prophéties accomplies',
            'desc' => 'Des personnes témoignent de la précision des prophéties reçues lors de leurs consultations.',
            'thumbnail' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            'type' => 'Témoignages', 'duration' => '',
        ],
    ];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $channelId,
    ) {
    }

    public function videos(): array
    {
        $cached = get_transient(self::TRANSIENT);
        if (is_array($cached)) {
            return $cached;
        }

        $videos = $this->fetch();

        if ($videos === null) {
            // Même règle que pour l'agenda : un échec ne fige pas les données de
            // démonstration pour 30 minutes.
            set_transient(self::TRANSIENT, self::MOCK_VIDEOS, self::TTL_ECHEC);

            return self::MOCK_VIDEOS;
        }

        set_transient(self::TRANSIENT, $videos, self::TTL);

        return $videos;
    }

    /** playlistItems.list coûte 1 unité de quota contre 100 pour search.list. */
    public static function uploadsPlaylistId(string $channelId): string
    {
        return preg_replace('/^UC/', 'UU', $channelId, 1) ?? $channelId;
    }

    /** Renvoie null en cas d'échec, pour que l'appelant distingue succès et repli. */
    private function fetch(): ?array
    {
        if ($this->apiKey === '' || $this->channelId === '') {
            return null;
        }

        $url = 'https://www.googleapis.com/youtube/v3/playlistItems?' . http_build_query([
            'key' => $this->apiKey,
            'playlistId' => self::uploadsPlaylistId($this->channelId),
            'part' => 'snippet',
            'maxResults' => '3',
        ]);

        $response = wp_remote_get($url, ['timeout' => 8]);

        if (is_wp_error($response)) {
            error_log('[YouTube] appel en échec, retour des données de démonstration');

            return null;
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);

        if (! is_array($payload) || ! isset($payload['items'])) {
            error_log('[YouTube] réponse inattendue, retour des données de démonstration');

            return null;
        }

        return self::parseItems($payload['items']);
    }

    /** Port de api-bridge/src/services/youtube.ts:52-68. */
    public static function parseItems(array $items): array
    {
        $videos = [];

        foreach ($items as $item) {
            $snippet = $item['snippet'] ?? [];
            $videoId = (string) ($snippet['resourceId']['videoId'] ?? '');

            $videos[] = [
                'id' => $videoId,
                'title' => (string) ($snippet['title'] ?? ''),
                'desc' => (string) ($snippet['description'] ?? ''),
                'thumbnail' => $snippet['thumbnails']['high']['url']
                    ?? $snippet['thumbnails']['medium']['url']
                    ?? 'https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg',
                'type' => 'Vidéo',
                'duration' => '',
            ];
        }

        return $videos;
    }
}
