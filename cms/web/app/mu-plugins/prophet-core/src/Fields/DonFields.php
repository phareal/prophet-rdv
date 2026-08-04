<?php

declare(strict_types=1);

namespace ProphetCore\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use ProphetCore\PostTypes\Don;

final class DonFields
{
    public static function register(): void
    {
        add_action('carbon_fields_register_fields', static function (): void {
            Container::make('post_meta', 'Don')
                ->where('post_type', '=', Don::SLUG)
                ->add_fields([
                    Field::make('text', 'don_motif_titre', 'Motif')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'don_montant', 'Montant')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'don_devise', 'Devise')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'don_prenom', 'Prénom')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'don_nom', 'Nom')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'don_email', 'Email')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'don_telephone', 'Téléphone')
                        ->set_attribute('readOnly', true),
                    Field::make('textarea', 'don_message', 'Message')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'don_ref', 'Référence')
                        ->set_attribute('readOnly', true),
                    // Noms de champ préfixés « don_ », comme tous les champs ci-dessus :
                    // Carbon Fields n'ajoute qu'un unique `_`, alors que
                    // Don::metaKey() exige `_don_`. Un champ nommé littéralement
                    // « paiement_statut » écrirait dans `_paiement_statut`, une clé que
                    // PaiementRepository ne lit jamais — le réglage à la main resterait
                    // invisible du système de paiement.
                    Field::make('select', 'don_paiement_statut', 'Statut du paiement')
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
                    Field::make('text', 'don_paiement_montant', 'Montant réglé')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'don_paiement_devise', 'Devise réglée')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'don_paiement_methode', 'Moyen de paiement')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'don_paiement_id', 'Transaction Moneroo')
                        ->set_attribute('readOnly', true),
                ]);
        });
    }
}
