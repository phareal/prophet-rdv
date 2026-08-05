<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\Don\DonPayable;
use ProphetCore\Don\DonRepository;
use ProphetCore\Paiement\InitHandler;
use ProphetCore\Paiement\PaiementRepository;
use ProphetCore\Paiement\RetourHandler;
use ProphetCore\Paiement\Statut;
use ProphetCore\PostTypes\Don;
use Roots\Acorn\View\Composer;

class DonMerci extends Composer
{
    protected static $views = ['template-don-merci'];

    public function with(): array
    {
        $ref = isset($_GET['ref']) ? sanitize_text_field(wp_unslash($_GET['ref'])) : '';

        $don = $ref === '' ? null : (new DonRepository())->parRef($ref);

        if ($don === null) {
            return [
                'don' => null,
                'paiement' => null,
                'introuvable' => true,
                'actionPaiement' => InitHandler::ACTION,
                'erreurPaiement' => false,
            ];
        }

        // Le paramètre paymentStatus de l'URL de retour n'est jamais lu : la
        // présence de paymentId déclenche une vérification serveur, seule source
        // de vérité — voir App\View\Composers\Confirmation, même piège.
        if (isset($_GET['paymentId'])) {
            (new RetourHandler())->verifier(
                new DonPayable($don),
                sanitize_text_field(wp_unslash($_GET['paymentId'])),
            );
        }

        $paiement = (new PaiementRepository(Don::META_PREFIX, Don::SLUG))->donnees($don['post_id']);
        $paiement['libelle'] = Statut::libelle($paiement['statut']);

        return [
            'don' => $don,
            'paiement' => $paiement,
            'introuvable' => false,
            'actionPaiement' => InitHandler::ACTION,
            // Posé par DonSubmitHandler quand l'initialisation Moneroo échoue :
            // le don reste enregistré, seul le lancement du paiement a échoué.
            'erreurPaiement' => (($_GET['paiement'] ?? '') === 'erreur'),
        ];
    }
}
