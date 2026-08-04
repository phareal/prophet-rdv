<?php

declare(strict_types=1);

namespace ProphetCore\Don;

use ProphetCore\PostTypes\Don;
use RuntimeException;

final class DonRepository
{
    /** @param array<string, mixed> $donnees */
    public function creer(array $donnees): string
    {
        $ref = wp_generate_password(32, false);

        $postId = wp_insert_post([
            'post_type' => Don::SLUG,
            'post_status' => Don::STATUT_EN_ATTENTE,
            'post_title' => sprintf(
                '%s %s — %s %d %s',
                $donnees['prenom'],
                $donnees['nom'],
                $donnees['motif_titre'],
                $donnees['montant'],
                $donnees['devise'],
            ),
        ], true);

        if (is_wp_error($postId)) {
            throw new RuntimeException('Création du don impossible');
        }

        $metas = [
            'motif' => (int) $donnees['motif_id'],
            'motif_titre' => sanitize_text_field((string) $donnees['motif_titre']),
            'montant' => (int) $donnees['montant'],
            'devise' => sanitize_text_field((string) $donnees['devise']),
            'nom' => sanitize_text_field((string) $donnees['nom']),
            'prenom' => sanitize_text_field((string) $donnees['prenom']),
            'email' => sanitize_email((string) $donnees['email']),
            'telephone' => sanitize_text_field((string) $donnees['telephone']),
            'message' => sanitize_textarea_field((string) $donnees['message']),
            'ref' => $ref,
        ];

        foreach ($metas as $champ => $valeur) {
            update_post_meta((int) $postId, Don::metaKey($champ), $valeur);
        }

        return $ref;
    }

    public function parRef(string $ref): ?array
    {
        $posts = get_posts([
            'post_type' => Don::SLUG,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [['key' => Don::metaKey('ref'), 'value' => $ref]],
        ]);

        if ($posts === []) {
            return null;
        }

        $postId = (int) $posts[0];
        $lire = static fn (string $champ): string => (string) get_post_meta($postId, Don::metaKey($champ), true);

        return [
            'post_id' => $postId,
            'ref' => $ref,
            'motif_id' => (int) $lire('motif'),
            'motif_titre' => $lire('motif_titre'),
            'montant' => (int) $lire('montant'),
            'devise' => $lire('devise'),
            'nom' => $lire('nom'),
            'prenom' => $lire('prenom'),
            'email' => $lire('email'),
            'telephone' => $lire('telephone'),
            'message' => $lire('message'),
        ];
    }
}
