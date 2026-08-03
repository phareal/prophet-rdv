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
                    // Titre en deux lignes : la source (HeroSection.vue:26) empile
                    // "Jeremiah" / "Nahoum" via un <br> décoratif dans .hero__title-name
                    // (clamp(3.4rem, 6vw, 5.5rem), line-height 0.9). Un champ text unique
                    // échapperait le <br> ; deux champs texte, joints par un <br> côté Blade,
                    // gardent l'échappement tout en conservant la mise en page à deux lignes.
                    Field::make('text', 'hero_titre_ligne_1', 'Titre — ligne 1'),
                    Field::make('text', 'hero_titre_ligne_2', 'Titre — ligne 2'),
                    Field::make('textarea', 'hero_sous_titre', 'Sous-titre'),
                    Field::make('text', 'hero_cta_principal', 'Libellé du bouton principal'),
                    Field::make('text', 'hero_cta_secondaire', 'Libellé du bouton secondaire'),
                    Field::make('image', 'hero_image', 'Image')->set_value_type('id'),
                ])
                ->add_tab('À propos', [
                    // Même logique que le titre du hero : AboutSection.vue:62-65 est
                    // "Un prophète<br><em>au service des nations</em>". Deux champs texte
                    // évitent d'ouvrir la balise <em> à l'échappement.
                    Field::make('text', 'about_titre_ligne_1', 'Titre — ligne 1'),
                    Field::make('text', 'about_titre_emphase', 'Titre — ligne 2 (en emphase)'),
                    Field::make('rich_text', 'about_texte', 'Texte'),
                    Field::make('image', 'about_image', 'Image')->set_value_type('id'),
                    // Finding 8 (revue) : "Domaines de ministère" était codé en dur dans
                    // about.blade.php alors que le cahier des charges (section 2, "points
                    // clés") le prévoit comme champ éditable de cette page d'options.
                    Field::make('complex', 'points_cles', 'Domaines de ministère')
                        ->add_fields([
                            Field::make('text', 'texte', 'Point clé'),
                        ]),
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
                    // CtaSection.vue:29-31 : "Votre rendez-vous<br><em>avec Dieu vous
                    // attend</em>" — même traitement en deux champs que hero/about.
                    Field::make('text', 'cta_titre_ligne_1', 'Titre — ligne 1'),
                    Field::make('text', 'cta_titre_emphase', 'Titre — ligne 2 (en emphase)'),
                    // CtaSection.vue:33-36 : le <br> entre les deux phrases du sous-texte
                    // est lui aussi structurel (pas de <em> ici, juste le saut de ligne).
                    Field::make('text', 'cta_texte_ligne_1', 'Texte — ligne 1'),
                    Field::make('text', 'cta_texte_ligne_2', 'Texte — ligne 2'),
                    Field::make('text', 'cta_bouton', 'Libellé du bouton'),
                ])
                ->add_tab('Pied de page', [
                    Field::make('textarea', 'footer_description', 'Description'),
                    Field::make('text', 'footer_youtube', 'Chaîne YouTube'),
                    Field::make('text', 'footer_facebook', 'Page Facebook'),
                    Field::make('text', 'footer_instagram', 'Compte Instagram'),
                    Field::make('text', 'footer_mentions', 'Mentions'),
                ]);
        });
    }
}
