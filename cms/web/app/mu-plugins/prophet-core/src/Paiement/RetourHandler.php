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
    public function verifier(SujetPaiement $sujet, string $paiementId): string
    {
        $paiements = new PaiementRepository($sujet->metaKey(''), $sujet->typeDePublication());

        // La transaction enregistrée par InitHandler à l'initialisation fait
        // foi : un identifiant différent fourni dans l'URL de retour
        // appartient à un autre paiement, réel ou non, mais jamais à ce
        // sujet-ci.
        $paiementIdEnregistre = $paiements->donnees($sujet->postId())['id'];

        if ($paiementIdEnregistre !== '' && $paiementIdEnregistre !== $paiementId) {
            $this->refuser($sujet, $paiementId);

            return $paiements->statut($sujet->postId());
        }

        try {
            $transaction = (new Moneroo(Options::env('MONEROO_SECRET_KEY')))
                ->recuperer($paiementId);
        } catch (Throwable $e) {
            error_log('[Paiement] vérification impossible (transaction ' . $paiementId . ') : ' . $e->getMessage());

            return $paiements->statut($sujet->postId());
        }

        // Filet de sécurité si aucune transaction n'était encore enregistrée
        // pour ce sujet : la référence portée en métadonnée par Moneroo (voir
        // InitHandler::demarrer()) doit elle aussi correspondre.
        $referenceTransaction = (string) ($transaction['metadata']['ref'] ?? '');

        if ($referenceTransaction !== '' && $referenceTransaction !== $sujet->reference()) {
            $this->refuser($sujet, $paiementId);

            return $paiements->statut($sujet->postId());
        }

        $statut = Statut::depuisMoneroo((string) ($transaction['status'] ?? ''));

        $paiements->appliquerStatut(
            $sujet->postId(),
            $statut,
            (string) ($transaction['payment_method'] ?? ''),
        );

        return $paiements->statut($sujet->postId());
    }

    /**
     * Jamais de donnée personnelle dans les journaux : seules la référence du
     * sujet et l'identifiant de transaction sont loggués.
     */
    private function refuser(SujetPaiement $sujet, string $paiementId): void
    {
        error_log(sprintf(
            '[Paiement] transaction refusée : ne correspond pas au sujet (réf. %s, transaction %s)',
            $sujet->reference(),
            $paiementId
        ));
    }
}
