<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

/**
 * Ce qu'un objet doit savoir dire de lui-même pour être payé. Le rendez-vous et
 * le don l'implémentent chacun : la couche paiement ne connaît plus ni l'un ni
 * l'autre, ce qui évite d'en dupliquer la mécanique — et d'avoir un jour un
 * correctif de sécurité appliqué à un seul des deux chemins.
 */
interface SujetPaiement
{
    public function postId(): int;

    public function reference(): string;

    /** Libellé que Moneroo affiche au payeur. */
    public function description(): string;

    /** @return array{montant: int, devise: string}|null null si rien n'est dû */
    public function montant(): ?array;

    /** @return array{email: string, prenom: string, nom: string, telephone: string} */
    public function client(): array;

    public function urlRetour(): string;

    public function metaKey(string $champ): string;

    public function typeDePublication(): string;
}
