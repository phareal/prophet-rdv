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
                ]);
        });
    }
}
