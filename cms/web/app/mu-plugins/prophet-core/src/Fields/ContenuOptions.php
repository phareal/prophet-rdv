<?php

declare(strict_types=1);

namespace ProphetCore\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

final class ContenuOptions
{
    public static function register(): void
    {
        add_action('carbon_fields_register_fields', static function (): void {
            Container::make('theme_options', 'Contenu du site')
                ->set_icon('dashicons-edit-large')
                ->add_tab('Hero', [
                    Field::make('text', 'hero_surtitre', 'Sur-titre'),
                    Field::make('text', 'hero_titre', 'Titre'),
                    Field::make('textarea', 'hero_sous_titre', 'Sous-titre'),
                    Field::make('text', 'hero_cta_principal', 'Libellé du bouton principal'),
                    Field::make('text', 'hero_cta_secondaire', 'Libellé du bouton secondaire'),
                    Field::make('image', 'hero_image', 'Image')->set_value_type('id'),
                ])
                ->add_tab('À propos', [
                    Field::make('text', 'about_titre', 'Titre'),
                    Field::make('rich_text', 'about_texte', 'Texte'),
                    Field::make('image', 'about_image', 'Image')->set_value_type('id'),
                ])
                ->add_tab('Chiffres', [
                    Field::make('complex', 'stats', 'Chiffres clés')
                        ->set_max(4)
                        ->add_fields([
                            Field::make('text', 'valeur', 'Valeur')->set_help_text('Ex. 15+'),
                            Field::make('text', 'libelle', 'Libellé')->set_help_text('Ex. Ans de ministère'),
                        ]),
                ])
                ->add_tab('Appel à l\'action', [
                    Field::make('text', 'cta_titre', 'Titre'),
                    Field::make('textarea', 'cta_texte', 'Texte'),
                    Field::make('text', 'cta_bouton', 'Libellé du bouton'),
                ])
                ->add_tab('Pied de page', [
                    Field::make('textarea', 'footer_description', 'Description'),
                    Field::make('text', 'footer_youtube', 'Chaîne YouTube'),
                    Field::make('text', 'footer_facebook', 'Page Facebook'),
                    Field::make('text', 'footer_mentions', 'Mentions'),
                ]);
        });
    }
}
