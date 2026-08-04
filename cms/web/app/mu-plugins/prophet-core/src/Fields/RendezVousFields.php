<?php

declare(strict_types=1);

namespace ProphetCore\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use ProphetCore\PostTypes\RendezVous;

final class RendezVousFields
{
    public static function register(): void
    {
        add_action('carbon_fields_register_fields', static function (): void {
            Container::make('post_meta', 'Demande')
                ->where('post_type', '=', RendezVous::SLUG)
                ->add_fields([
                    Field::make('text', 'rdv_prenom', 'Prénom'),
                    Field::make('text', 'rdv_nom', 'Nom'),
                    Field::make('text', 'rdv_email', 'Email'),
                    Field::make('text', 'rdv_telephone', 'Téléphone'),
                    Field::make('text', 'rdv_pays', 'Pays'),
                    Field::make('date', 'rdv_date', 'Date')->set_storage_format('Y-m-d'),
                    Field::make('text', 'rdv_heure', 'Heure'),
                    Field::make('text', 'rdv_type_consultation', 'Type de consultation'),
                    Field::make('text', 'rdv_mode_paiement', 'Mode de paiement'),
                    Field::make('textarea', 'rdv_message', 'Message'),
                    Field::make('text', 'rdv_ref', 'Référence')
                        ->set_attribute('readOnly', true),
                    // Noms de champ préfixés « rdv_ », comme tous les champs ci-dessus :
                    // Carbon Fields n'ajoute qu'un unique `_`, alors que
                    // RendezVous::metaKey() exige `_rdv_`. Un champ nommé littéralement
                    // « paiement_statut » écrirait dans `_paiement_statut`, une clé que
                    // PaiementRepository ne lit jamais — le réglage à la main resterait
                    // invisible du système de paiement.
                    Field::make('select', 'rdv_paiement_statut', 'Statut du paiement')
                        ->set_options([
                            'non_requis' => 'Non requis',
                            'en_attente' => 'En attente',
                            'paye' => 'Réglé',
                            'echoue' => 'Échoué',
                            'annule' => 'Annulé',
                        ])
                        ->set_help_text(
                            'Modifiable à la main pour enregistrer un règlement '
                            . 'reçu hors ligne — le mobile money de la main à la '
                            . 'main reste courant.'
                        ),
                    Field::make('text', 'rdv_paiement_montant', 'Montant')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'rdv_paiement_devise', 'Devise')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'rdv_paiement_methode', 'Moyen de paiement')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'rdv_paiement_id', 'Transaction Moneroo')
                        ->set_attribute('readOnly', true),
                ]);
        });
    }
}
