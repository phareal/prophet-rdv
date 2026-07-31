<?php

declare(strict_types=1);

namespace ProphetCore\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

final class RdvOptions
{
    public static function register(): void
    {
        add_action('carbon_fields_register_fields', static function (): void {
            Container::make('theme_options', 'Réglages RDV')
                ->set_icon('dashicons-calendar-alt')
                ->add_fields([
                    Field::make('text', 'rdv_phone_1', 'WhatsApp principal'),
                    Field::make('text', 'rdv_phone_2', 'WhatsApp secondaire'),
                    Field::make('text', 'rdv_email', 'Email de notification'),
                    Field::make('complex', 'rdv_heures', 'Créneaux horaires')
                        ->add_fields([Field::make('text', 'valeur', 'Heure')->set_help_text('Ex. 08h00')]),
                    Field::make('complex', 'rdv_modes_paiement', 'Modes de paiement')
                        ->add_fields([
                            Field::make('text', 'valeur', 'Valeur technique')->set_help_text('Ex. mobile_money'),
                            Field::make('text', 'libelle', 'Libellé affiché')->set_help_text('Ex. Mobile Money'),
                        ]),
                    Field::make('textarea', 'rdv_aide', 'Texte d\'aide du formulaire'),
                ]);
        });
    }
}
