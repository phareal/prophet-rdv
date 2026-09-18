<?php

declare(strict_types=1);

namespace ProphetCore\Don;

use ProphetCore\Paiement\SujetPaiement;
use ProphetCore\PostTypes\Don;

final class DonPayable implements SujetPaiement
{
    /** @param array<string, mixed> $don */
    public function __construct(private readonly array $don)
    {
    }

    public static function parReference(string $ref): ?self
    {
        $don = (new DonRepository())->parRef($ref);

        return $don === null ? null : new self($don);
    }

    public static function parPaiementId(string $paiementId): ?self
    {
        $posts = get_posts([
            'post_type' => Don::SLUG,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [
                ['key' => Don::metaKey('paiement_id'), 'value' => $paiementId],
            ],
        ]);

        if ($posts === []) {
            return null;
        }

        $ref = (string) get_post_meta((int) $posts[0], Don::metaKey('ref'), true);

        return self::parReference($ref);
    }

    public function postId(): int
    {
        return (int) $this->don['post_id'];
    }

    public function reference(): string
    {
        return (string) $this->don['ref'];
    }

    public function description(): string
    {
        return 'Don — ' . $this->don['motif_titre'];
    }

    /**
     * Le montant est figé à l'enregistrement du don, contrairement au
     * rendez-vous où il se résout depuis le service : un changement de motif
     * après coup ne doit pas rouvrir un don déjà déposé.
     */
    public function montant(): ?array
    {
        return [
            'montant' => (int) $this->don['montant'],
            'devise' => (string) $this->don['devise'],
        ];
    }

    public function client(): array
    {
        return [
            'email' => (string) $this->don['email'],
            'prenom' => (string) $this->don['prenom'],
            'nom' => (string) $this->don['nom'],
            'telephone' => (string) $this->don['telephone'],
        ];
    }

    public function urlRetour(): string
    {
        return add_query_arg(['ref' => $this->reference()], home_url('/don/merci/'));
    }

    public function metaKey(string $champ): string
    {
        return Don::metaKey($champ);
    }

    public function typeDePublication(): string
    {
        return Don::SLUG;
    }
}
