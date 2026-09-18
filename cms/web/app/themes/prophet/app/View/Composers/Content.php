<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\Options;
use Roots\Acorn\View\Composer;

class Content extends Composer
{
    protected static $views = ['*'];

    public function with(): array
    {
        return [
            'contenu' => [
                'hero_surtitre' => Options::content('hero_surtitre'),
                // Titre en deux champs (voir ContenuOptions::register) : la source
                // (HeroSection.vue:26) empile "Jeremiah" / "Nahoum" via un <br>.
                'hero_titre_ligne_1' => Options::content('hero_titre_ligne_1'),
                'hero_titre_ligne_2' => Options::content('hero_titre_ligne_2'),
                'hero_sous_titre' => Options::content('hero_sous_titre'),
                'hero_cta_principal' => Options::content('hero_cta_principal'),
                'hero_cta_secondaire' => Options::content('hero_cta_secondaire'),
                'about_titre_ligne_1' => Options::content('about_titre_ligne_1'),
                'about_titre_emphase' => Options::content('about_titre_emphase'),
                'about_texte' => Options::content('about_texte'),
                'points_cles' => self::pointsCles(),
                'cta_titre_ligne_1' => Options::content('cta_titre_ligne_1'),
                'cta_titre_emphase' => Options::content('cta_titre_emphase'),
                'cta_texte_ligne_1' => Options::content('cta_texte_ligne_1'),
                'cta_texte_ligne_2' => Options::content('cta_texte_ligne_2'),
                'cta_bouton' => Options::content('cta_bouton'),
                'footer_description' => Options::content(
                    'footer_description',
                    'Ministère prophétique international dédié à guider les nations, les dirigeants '
                    .'et les familles selon la volonté de Dieu depuis plus de 15 ans.'
                ),
                'footer_youtube' => Options::content('footer_youtube', 'https://www.youtube.com/@ProphetJeremiahNahoum'),
                'footer_facebook' => Options::content('footer_facebook', 'https://www.facebook.com/ProphetJeremiahNahoum'),
                'footer_instagram' => Options::content('footer_instagram', 'https://www.instagram.com/ProphetJeremiahNahoum'),
                'footer_mentions' => Options::content('footer_mentions', 'Tous droits réservés'),
            ],
            'phone1' => Options::phone1(),
            'phone2' => Options::phone2(),
        ];
    }

    /**
     * "Domaines de ministère" (AboutSection.vue: tableau `gifts` codé en dur dans le
     * <script setup>) — le cahier des charges (section 2, "points clés") le prévoit
     * comme répéteur de la page d'options ; on retombe sur les six libellés d'origine
     * tant que l'admin n'a rien saisi, exactement comme footer_description ci-dessus.
     *
     * @return array<int, string>
     */
    private static function pointsCles(): array
    {
        $valeur = carbon_get_theme_option('points_cles');

        if (is_array($valeur) && $valeur !== []) {
            return array_values(array_filter(array_map(
                static fn ($ligne): string => is_array($ligne) ? (string) ($ligne['texte'] ?? '') : '',
                $valeur
            )));
        }

        return [
            'Prophétie de précision — noms, dates, détails vérifiables',
            'Conseil stratégique pour dirigeants et entrepreneurs',
            'Intercession et délivrance spirituelle',
            'Activation des appels prophétiques',
            'Guidance dans les décisions de carrière et de mariage',
            'Révélation sur les années et les saisons spirituelles',
        ];
    }
}
