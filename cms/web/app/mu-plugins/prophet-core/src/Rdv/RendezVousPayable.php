<?php

declare(strict_types=1);

namespace ProphetCore\Rdv;

use ProphetCore\Paiement\Montant;
use ProphetCore\Paiement\SujetPaiement;
use ProphetCore\PostTypes\RendezVous;

final class RendezVousPayable implements SujetPaiement
{
    /** @param array<string, mixed> $rdv */
    public function __construct(private readonly array $rdv)
    {
    }

    public static function parReference(string $ref): ?self
    {
        $rdv = (new Repository())->findByRef($ref);

        return $rdv === null ? null : new self($rdv);
    }

    public static function parPaiementId(string $paiementId): ?self
    {
        $posts = get_posts([
            'post_type' => RendezVous::SLUG,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [
                ['key' => RendezVous::metaKey('paiement_id'), 'value' => $paiementId],
            ],
        ]);

        if ($posts === []) {
            return null;
        }

        $ref = (string) get_post_meta((int) $posts[0], RendezVous::metaKey('ref'), true);

        return self::parReference($ref);
    }

    public function postId(): int
    {
        return (int) $this->rdv['post_id'];
    }

    public function reference(): string
    {
        return (string) $this->rdv['ref'];
    }

    public function description(): string
    {
        return 'Consultation — ' . $this->rdv['type_consultation'];
    }

    public function montant(): ?array
    {
        return Montant::pour((string) $this->rdv['type_consultation']);
    }

    public function client(): array
    {
        return [
            'email' => (string) $this->rdv['email'],
            'prenom' => (string) $this->rdv['prenom'],
            'nom' => (string) $this->rdv['nom'],
            'telephone' => (string) $this->rdv['telephone'],
        ];
    }

    public function urlRetour(): string
    {
        return add_query_arg(['ref' => $this->reference()], home_url('/confirmation/'));
    }

    public function metaKey(string $champ): string
    {
        return RendezVous::metaKey($champ);
    }

    public function typeDePublication(): string
    {
        return RendezVous::SLUG;
    }
}
