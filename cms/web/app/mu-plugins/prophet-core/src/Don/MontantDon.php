<?php

declare(strict_types=1);

namespace ProphetCore\Don;

use ProphetCore\Options;
use ProphetCore\PostTypes\MotifPaiement;

final class MontantDon
{
    /**
     * Le montant est ici saisi par le visiteur, contrairement au rendez-vous où
     * il vient du service. D'où une validation serveur stricte : les attributs
     * HTML min et max ne sont qu'un confort d'affichage.
     *
     * @param array<string, mixed> $motif
     * @return array{montant: int, devise: string}|array{erreur: string}
     */
    public static function resoudre(array $motif, string $montantSoumis): array
    {
        $devise = Options::devise();

        if ($motif['regime'] === MotifPaiement::REGIME_FIXE) {
            $montantFixe = (int) $motif['montant_fixe'];

            // Le champ n'a pas de défaut : un motif basculé en fixe sans
            // être rempli renverrait 0, que Moneroo refuserait sans qu'aucun
            // message n'explique au donateur pourquoi le paiement échoue.
            if ($montantFixe <= 0) {
                return ['erreur' => 'Ce motif n\'est pas correctement configuré, merci de réessayer plus tard'];
            }

            if ($montantFixe < (int) $motif['min']) {
                return ['erreur' => sprintf('Le montant minimum est de %d %s', $motif['min'], $devise)];
            }

            if ($montantFixe > (int) $motif['max']) {
                return ['erreur' => sprintf('Le montant maximum est de %d %s', $motif['max'], $devise)];
            }

            return ['montant' => $montantFixe, 'devise' => $devise];
        }

        // « 2 500 » est ce qu'un francophone tape ; les espaces fines et
        // insécables comptent aussi.
        $nettoye = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', trim($montantSoumis)) ?? '';

        if ($nettoye === '' || preg_match('/^\d+$/', $nettoye) !== 1) {
            return ['erreur' => 'Veuillez saisir un montant en chiffres entiers'];
        }

        $montant = (int) $nettoye;

        if ($montant < (int) $motif['min']) {
            return ['erreur' => sprintf('Le montant minimum est de %d %s', $motif['min'], $devise)];
        }

        if ($montant > (int) $motif['max']) {
            return ['erreur' => sprintf('Le montant maximum est de %d %s', $motif['max'], $devise)];
        }

        return ['montant' => $montant, 'devise' => $devise];
    }
}
