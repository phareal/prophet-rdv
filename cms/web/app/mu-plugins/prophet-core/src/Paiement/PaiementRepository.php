<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\PostTypes\RendezVous;

final class PaiementRepository
{
    public function __construct(
        private readonly string $prefixe = RendezVous::META_PREFIX,
        private readonly string $typeDePublication = RendezVous::SLUG,
    ) {
    }

    private function metaKey(string $champ): string
    {
        return $this->prefixe . $champ;
    }

    public function statut(int $postId): string
    {
        $statut = (string) get_post_meta($postId, $this->metaKey('paiement_statut'), true);

        return $statut !== '' ? $statut : Statut::NON_REQUIS;
    }

    /**
     * Le montant et la devise sont figés ici : un changement de tarif ne doit pas
     * réécrire l'historique d'une transaction déjà engagée.
     */
    public function enregistrerInitialisation(
        int $postId,
        string $paiementId,
        int $montant,
        string $devise,
    ): void {
        update_post_meta($postId, $this->metaKey('paiement_id'), sanitize_text_field($paiementId));
        update_post_meta($postId, $this->metaKey('paiement_montant'), $montant);
        update_post_meta($postId, $this->metaKey('paiement_devise'), sanitize_text_field($devise));
        update_post_meta($postId, $this->metaKey('paiement_statut'), Statut::EN_ATTENTE);
    }

    /**
     * Idempotent et monotone : un statut déjà final n'est jamais écrasé, que ce
     * soit par le même événement reçu deux fois ou par un webhook tardif.
     *
     * @return bool true si l'état a réellement changé
     */
    public function appliquerStatut(int $postId, string $statut, string $methode = ''): bool
    {
        if (Statut::estFinal($this->statut($postId))) {
            return false;
        }

        update_post_meta($postId, $this->metaKey('paiement_statut'), $statut);

        if ($methode !== '') {
            update_post_meta($postId, $this->metaKey('paiement_methode'), sanitize_text_field($methode));
        }

        update_post_meta($postId, $this->metaKey('paiement_verifie_le'), current_time('mysql'));

        return true;
    }

    public function trouverParPaiementId(string $paiementId): ?int
    {
        $posts = get_posts([
            'post_type' => $this->typeDePublication,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [
                ['key' => $this->metaKey('paiement_id'), 'value' => $paiementId],
            ],
        ]);

        return $posts === [] ? null : (int) $posts[0];
    }

    /** @return array{statut: string, id: string, montant: int, devise: string, methode: string} */
    public function donnees(int $postId): array
    {
        $lire = fn (string $champ): string => (string) get_post_meta(
            $postId,
            $this->metaKey($champ),
            true
        );

        return [
            'statut' => $this->statut($postId),
            'id' => $lire('paiement_id'),
            'montant' => (int) $lire('paiement_montant'),
            'devise' => $lire('paiement_devise'),
            'methode' => $lire('paiement_methode'),
        ];
    }
}
