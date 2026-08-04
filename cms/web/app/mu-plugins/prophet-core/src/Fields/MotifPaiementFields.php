<?php

declare(strict_types=1);

namespace ProphetCore\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use ProphetCore\PostTypes\MotifPaiement;

final class MotifPaiementFields
{
    public static function register(): void
    {
        add_action('carbon_fields_register_fields', static function (): void {
            Container::make('post_meta', 'Configuration du motif')
                ->where('post_type', '=', MotifPaiement::SLUG)
                ->add_fields([
                    Field::make('text', 'motif_icone', 'Icône')
                        ->set_help_text('Nom lucide en minuscules : hand-coins, gift, heart-handshake…'),
                    Field::make('color', 'motif_couleur', 'Couleur')->set_default_value('#B07A14'),
                    Field::make('select', 'motif_regime', 'Régime de montant')
                        ->set_options([
                            MotifPaiement::REGIME_LIBRE => 'Libre — le donateur saisit ce qu\'il veut',
                            MotifPaiement::REGIME_FIXE => 'Fixe — montant imposé',
                            MotifPaiement::REGIME_SUGGERE => 'Suggéré — paliers proposés, saisie libre possible',
                        ])
                        ->set_default_value(MotifPaiement::REGIME_LIBRE),
                    Field::make('text', 'motif_montant_fixe', 'Montant fixe')
                        ->set_attribute('type', 'number')
                        ->set_conditional_logic([
                            ['field' => 'motif_regime', 'value' => MotifPaiement::REGIME_FIXE],
                        ]),
                    Field::make('complex', 'motif_montants_suggeres', 'Montants suggérés')
                        ->add_fields([Field::make('text', 'valeur', 'Montant')->set_attribute('type', 'number')])
                        ->set_conditional_logic([
                            ['field' => 'motif_regime', 'value' => MotifPaiement::REGIME_SUGGERE],
                        ]),
                    Field::make('text', 'motif_montant_min', 'Montant minimum')
                        ->set_attribute('type', 'number')
                        ->set_default_value('500')
                        ->set_help_text('Garde-fou serveur : refuse les versements dérisoires.'),
                    Field::make('text', 'motif_montant_max', 'Montant maximum')
                        ->set_attribute('type', 'number')
                        ->set_default_value('5000000'),
                    Field::make('checkbox', 'motif_actif', 'Motif actif')
                        ->set_default_value(true)
                        ->set_help_text('Décoché, le motif disparaît du formulaire mais son lien reste consultable.'),
                ]);
        });
    }
}
