<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\Options;
use ProphetCore\Paiement\InitHandler;
use ProphetCore\Paiement\Montant;
use ProphetCore\Paiement\PaiementRepository;
use ProphetCore\Paiement\RetourHandler;
use ProphetCore\Paiement\Statut;
use ProphetCore\Rdv\Repository;
use ProphetCore\Rdv\RendezVousPayable;
use ProphetCore\Services\Whatsapp;
use ProphetCore\Support\Date;
use Roots\Acorn\View\Composer;

class Confirmation extends Composer
{
    protected static $views = ['template-confirmation'];

    public function with(): array
    {
        $ref = isset($_GET['ref'])
            ? sanitize_text_field(wp_unslash($_GET['ref']))
            : '';

        $rdv = $ref === '' ? null : (new Repository())->findByRef($ref);

        if ($rdv === null) {
            return ['rdv' => null, 'dateFr' => '', 'waUrl' => '', 'introuvable' => true];
        }

        // Le paramètre paymentStatus de l'URL n'est jamais lu : la présence de
        // paymentId déclenche une vérification serveur, seule source de vérité.
        if ($rdv !== null && isset($_GET['paymentId'])) {
            (new RetourHandler())->verifier(
                new RendezVousPayable($rdv),
                sanitize_text_field(wp_unslash($_GET['paymentId'])),
            );
        }

        $paiement = $rdv === null
            ? ['statut' => Statut::NON_REQUIS, 'montant' => 0, 'devise' => '', 'libelle' => '']
            : (new PaiementRepository())->donnees((int) $rdv['post_id']);

        // Le montant n'est figé en base qu'à l'initialisation Moneroo
        // (InitHandler::demarrer(), déclenché par le clic sur le bouton). Avant ce
        // clic, un rendez-vous « en attente » avec un prix dû n'a donc encore
        // aucun paiement_montant enregistré : sans ce recalcul en lecture seule,
        // le premier palier du parcours facultatif — voir le montant et pouvoir
        // payer dès la confirmation — resterait invisible. Rien n'est écrit ici ;
        // le montant réellement figé reste celui posé par l'initialisation.
        if ($paiement['statut'] === Statut::EN_ATTENTE && $paiement['montant'] === 0) {
            $devis = Montant::pour((string) $rdv['type_consultation']);

            if ($devis !== null) {
                $paiement['montant'] = $devis['montant'];
                $paiement['devise'] = $devis['devise'];
            }
        }

        $paiement['libelle'] = Statut::libelle($paiement['statut']);

        return [
            'rdv' => $rdv,
            'dateFr' => Date::formatFr($rdv['date']),
            'waUrl' => Whatsapp::confirmationUrl(Options::phone1(), $rdv),
            'introuvable' => false,
            'paiement' => $paiement,
            'texteBoutonPaiement' => Options::texteBoutonPaiement(),
            'actionPaiement' => InitHandler::ACTION,
            'erreurPaiement' => (($_GET['paiement'] ?? '') === 'erreur'),
        ];
    }
}
