<?php

declare(strict_types=1);

namespace ProphetCore\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

final class PaiementOptions
{
    public static function register(): void
    {
        add_action('carbon_fields_register_fields', static function (): void {
            Container::make('theme_options', 'Paiement')
                ->set_icon('dashicons-money-alt')
                ->add_fields([
                    Field::make('checkbox', 'moneroo_actif', 'Activer le paiement en ligne')
                        ->set_help_text(
                            'Décoché, le site fonctionne comme avant : le mode de '
                            . 'paiement reste déclaratif et rien n\'est encaissé.'
                        ),
                    Field::make('select', 'moneroo_devise', 'Devise')
                        ->set_options([
                            'XOF' => 'Franc CFA (XOF)',
                            'XAF' => 'Franc CFA (XAF)',
                            'NGN' => 'Naira (NGN)',
                            'GHS' => 'Cedi (GHS)',
                            'EUR' => 'Euro (EUR)',
                            'USD' => 'Dollar (USD)',
                        ])
                        ->set_default_value('XOF'),
                    Field::make('select', 'moneroo_moment', 'Moment du paiement')
                        ->set_options([
                            'facultatif' => 'Facultatif — proposé après la demande',
                            'exige' => 'Exigé — le visiteur paie pour valider',
                        ])
                        ->set_default_value('facultatif')
                        ->set_help_text(
                            'En mode exigé, une demande non réglée est annulée après '
                            . 'le délai ci-dessous et son créneau est libéré.'
                        ),
                    Field::make('text', 'moneroo_texte_bouton', 'Libellé du bouton')
                        ->set_default_value('Régler ma consultation'),
                    Field::make('text', 'moneroo_delai_abandon', 'Délai d\'abandon (minutes)')
                        ->set_attribute('type', 'number')
                        ->set_default_value('30'),
                ]);
        });
    }
}
