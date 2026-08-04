<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\Options;
use ProphetCore\PostTypes\Service;
use ProphetCore\Rdv\Validator;

final class Montant
{
    /**
     * Le montant est toujours lu depuis le service côté serveur. Un montant qui
     * transiterait par le formulaire serait modifiable par le visiteur.
     *
     * @return array{montant: int, devise: string}|null null quand rien n'est dû
     */
    public static function pour(string $typeConsultation): ?array
    {
        if (! Options::paiementActif()) {
            return null;
        }

        // Une inscription à un événement ne correspond à aucun service : ses
        // modalités sont propres à l'événement.
        if (str_starts_with($typeConsultation, Validator::PREFIXE_EVENEMENT)) {
            return null;
        }

        $services = get_posts([
            'post_type' => Service::SLUG,
            'post_status' => 'publish',
            'title' => $typeConsultation,
            'posts_per_page' => 1,
            'no_found_rows' => true,
        ]);

        if ($services === []) {
            return null;
        }

        $prix = (int) carbon_get_post_meta($services[0]->ID, 'service_prix');

        if ($prix <= 0) {
            return null;
        }

        return ['montant' => $prix, 'devise' => Options::devise()];
    }
}
