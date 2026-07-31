<?php

declare(strict_types=1);

namespace ProphetCore\Rdv;

use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\PostTypes\RendezVous;

final class Repository
{
    /** Règle métier reprise de server/api/rdv.post.ts:41-56. */
    public function slotTaken(string $dateYmd, string $heure): bool
    {
        $existants = get_posts([
            'post_type' => RendezVous::SLUG,
            'post_status' => [RendezVous::STATUT_EN_ATTENTE, RendezVous::STATUT_CONFIRME],
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [
                ['key' => RendezVous::metaKey('date'), 'value' => $dateYmd],
                ['key' => RendezVous::metaKey('heure'), 'value' => $heure],
            ],
        ]);

        return $existants !== [];
    }

    public function create(array $data): string
    {
        $ref = wp_generate_password(32, false);

        $postId = wp_insert_post([
            'post_type' => RendezVous::SLUG,
            'post_status' => RendezVous::STATUT_EN_ATTENTE,
            'post_title' => sprintf(
                '%s %s — %s %s',
                $data['prenom'],
                $data['nom'],
                $data['date']->format('d/m/Y'),
                $data['heure']
            ),
        ], true);

        if (is_wp_error($postId)) {
            throw new \RuntimeException('Création du rendez-vous impossible');
        }

        $metas = [
            'nom' => sanitize_text_field($data['nom']),
            'prenom' => sanitize_text_field($data['prenom']),
            'email' => sanitize_email($data['email']),
            'telephone' => sanitize_text_field($data['telephone']),
            'pays' => sanitize_text_field($data['pays']),
            'date' => $data['date']->format('Y-m-d'),
            'heure' => sanitize_text_field($data['heure']),
            'type_consultation' => sanitize_text_field($data['type_consultation']),
            'mode_paiement' => sanitize_text_field($data['mode_paiement']),
            'message' => sanitize_textarea_field($data['message']),
            'ref' => $ref,
        ];

        // Les clés passent par RendezVous::metaKey() : Carbon Fields impose son
        // préfixe, et une clé littérale ici produirait un rendez-vous que le
        // panneau d'administration afficherait vide.
        foreach ($metas as $champ => $valeur) {
            update_post_meta((int) $postId, RendezVous::metaKey($champ), $valeur);
        }

        return $ref;
    }

    public function findByRef(string $ref): ?array
    {
        $posts = get_posts([
            'post_type' => RendezVous::SLUG,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [['key' => RendezVous::metaKey('ref'), 'value' => $ref]],
        ]);

        if ($posts === []) {
            return null;
        }

        $postId = (int) $posts[0];

        $lire = static fn (string $champ): string => (string) get_post_meta(
            $postId,
            RendezVous::metaKey($champ),
            true
        );

        return [
            'nom' => $lire('nom'),
            'prenom' => $lire('prenom'),
            'email' => $lire('email'),
            'telephone' => $lire('telephone'),
            'pays' => $lire('pays'),
            'date' => new DateTimeImmutable($lire('date'), new DateTimeZone('UTC')),
            'heure' => $lire('heure'),
            'type_consultation' => $lire('type_consultation'),
            'mode_paiement' => $lire('mode_paiement'),
            'message' => $lire('message'),
        ];
    }
}
