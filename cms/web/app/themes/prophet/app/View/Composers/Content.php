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
                'hero_titre' => Options::content('hero_titre'),
                'hero_sous_titre' => Options::content('hero_sous_titre'),
                'hero_cta_principal' => Options::content('hero_cta_principal'),
                'hero_cta_secondaire' => Options::content('hero_cta_secondaire'),
                'about_titre' => Options::content('about_titre'),
                'about_texte' => Options::content('about_texte'),
                'cta_titre' => Options::content('cta_titre'),
                'cta_texte' => Options::content('cta_texte'),
                'cta_bouton' => Options::content('cta_bouton'),
                'footer_description' => Options::content(
                    'footer_description',
                    'Ministère prophétique international dédié à guider les nations, les dirigeants '
                    .'et les familles selon la volonté de Dieu depuis plus de 15 ans.'
                ),
                'footer_youtube' => Options::content('footer_youtube', 'https://www.youtube.com/@ProphetJeremiahNahoum'),
                'footer_facebook' => Options::content('footer_facebook', 'https://www.facebook.com/ProphetJeremiahNahoum'),
                'footer_mentions' => Options::content('footer_mentions', 'Tous droits réservés'),
            ],
            'phone1' => Options::phone1(),
            'phone2' => Options::phone2(),
        ];
    }
}
