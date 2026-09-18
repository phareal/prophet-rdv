<?php

declare(strict_types=1);

namespace ProphetCore\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use ProphetCore\Don\QrCode;
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
                    // Défaut D2 de la recette du 2026-08-04 : le QR n'était
                    // visible que dans la colonne de la liste, jamais sur
                    // l'écran d'édition individuel — l'endroit où l'on
                    // regarde avant de partager un motif. Champ `html` : pas
                    // de valeur à stocker, juste le rendu partagé avec la
                    // colonne (MotifPaiement::lienEtQrHtml()). Carbon Fields
                    // n'expose pas l'ID du post au callback ; à l'écran
                    // d'édition, WordPress le porte dans `$_GET['post']`
                    // (c'est d'ailleurs ainsi que Post_Meta_Container résout
                    // lui-même son propre object_id).
                    Field::make('html', 'motif_qr', 'Lien et QR code')
                        ->set_html(static function (): string {
                            $postId = isset($_GET['post']) ? (int) $_GET['post'] : 0;

                            return $postId > 0
                                ? MotifPaiement::lienEtQrHtml($postId)
                                : '<p>Enregistrez le motif pour générer son lien et son QR.</p>';
                        }),
                ]);
        });

        // Un changement d'identifiant change l'URL du motif : le QR en cache
        // pointerait alors vers une page disparue.
        add_action('post_updated', static function (int $postId, $apres, $avant): void {
            if ($apres->post_type !== MotifPaiement::SLUG) {
                return;
            }

            if ($apres->post_name !== $avant->post_name) {
                QrCode::invalider($postId);
            }
        }, 10, 3);
    }
}
