<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\Options;
use Throwable;

final class RetourHandler
{
    /**
     * Le paramètre `paymentStatus` de l'URL de retour n'est jamais lu : un
     * visiteur peut le réécrire. Seule la réponse de l'API fait foi.
     *
     * @return string le statut interne après vérification
     */
    public function verifier(int $postId, string $paiementId): string
    {
        $paiements = new PaiementRepository();

        try {
            $transaction = (new Moneroo(Options::env('MONEROO_SECRET_KEY')))
                ->recuperer($paiementId);
        } catch (Throwable $e) {
            error_log('[Paiement] vérification impossible (transaction ' . $paiementId . ') : ' . $e->getMessage());

            return $paiements->statut($postId);
        }

        $statut = Statut::depuisMoneroo((string) ($transaction['status'] ?? ''));

        $paiements->appliquerStatut(
            $postId,
            $statut,
            (string) ($transaction['payment_method'] ?? ''),
        );

        return $paiements->statut($postId);
    }
}
