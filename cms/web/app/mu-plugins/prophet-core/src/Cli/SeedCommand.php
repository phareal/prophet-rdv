<?php

declare(strict_types=1);

namespace ProphetCore\Cli;

use ProphetCore\PostTypes\Photo;
use ProphetCore\PostTypes\Service;
use ProphetCore\PostTypes\Temoignage;
use WP_CLI;

final class SeedCommand
{
    /** Reprise de components/ServicesSection.vue:6-19. */
    public static function services(): array
    {
        return [
            ['titre' => 'Mariage', 'icone' => 'heart', 'couleur' => '#c2185b',
             'description' => 'Guidance prophétique pour trouver votre partenaire de vie selon la volonté de Dieu.'],
            ['titre' => 'Anges', 'icone' => 'sparkles', 'couleur' => '#B07A14',
             'description' => 'Révélation sur les anges assignés à votre vie et votre destinée divine.'],
            ['titre' => 'Appel', 'icone' => 'mic-2', 'couleur' => '#7B5EA7',
             'description' => 'Confirmation et activation de votre appel prophétique ou ministériel.'],
            ['titre' => 'Politique', 'icone' => 'landmark', 'couleur' => '#1565C0',
             'description' => 'Stratégie divine pour les élections, les nominations et la gouvernance.'],
            ['titre' => 'Affaires', 'icone' => 'briefcase', 'couleur' => '#0A9060',
             'description' => 'Direction spirituelle pour vos investissements, partenariats et décisions clés.'],
            ['titre' => 'Autels', 'icone' => 'flame', 'couleur' => '#E64A19',
             'description' => 'Identification et bris des autels familiaux qui bloquent votre progression.'],
            ['titre' => 'Étoiles', 'icone' => 'star', 'couleur' => '#B07A14',
             'description' => 'Lecture prophétique de votre étoile et de votre glorieux destin céleste.'],
            ['titre' => 'Santé', 'icone' => 'heart-pulse', 'couleur' => '#D32F2F',
             'description' => 'Prière prophétique et révélation pour les guérisons physiques et mentales.'],
            ['titre' => 'Voyage', 'icone' => 'plane', 'couleur' => '#0277BD',
             'description' => 'Confirmation divine pour vos déplacements, immigrations et nouvelles résidences.'],
            ['titre' => 'Entreprise', 'icone' => 'building-2', 'couleur' => '#00897B',
             'description' => 'Vision prophétique pour le lancement et la croissance de votre entreprise.'],
            ['titre' => 'Commerce', 'icone' => 'shopping-bag', 'couleur' => '#6D4C41',
             'description' => 'Stratégies divines pour la prospérité dans le négoce et le commerce international.'],
            ['titre' => 'Année 2026', 'icone' => 'calendar-days', 'couleur' => '#B07A14',
             'description' => 'Prophétie personnelle sur ce que Dieu prépare pour vous cette année.'],
        ];
    }

    /** Reprise de components/TestimonialsSection.vue:4-17. */
    public static function temoignages(): array
    {
        return [
            ['nom' => 'Marie-Claire D.', 'initiales' => 'MC', 'pays' => "Côte d'Ivoire",
             'consultation' => 'Mariage', 'couleur' => '#c2185b', 'etoiles' => 5,
             'texte' => "Trois mois après la prophétie, j'ai rencontré mon époux. Chaque mot s'est réalisé avec une précision qui m'a laissée sans voix. Seul Dieu pouvait connaître ces détails."],
            ['nom' => 'Pastor Emmanuel K.', 'initiales' => 'EK', 'pays' => 'Nigeria',
             'consultation' => 'Appel', 'couleur' => '#3949ab', 'etoiles' => 5,
             'texte' => "Il a confirmé mon appel au ministère avec des détails que seul Dieu pouvait connaître — le nom de ma ville d'origine, l'année de ma conversion, et la nation vers laquelle Dieu m'envoyait."],
            ['nom' => 'Fatou S.', 'initiales' => 'FS', 'pays' => 'Sénégal',
             'consultation' => 'Affaires', 'couleur' => '#B07A14', 'etoiles' => 5,
             'texte' => "Au bord de la faillite, sa stratégie divine a multiplié mon chiffre d'affaires par 10 en 8 mois. Ce n'est pas de la magie — c'est la direction de l'Esprit Saint."],
            ['nom' => 'Jean-Baptiste M.', 'initiales' => 'JB', 'pays' => 'France',
             'consultation' => 'Politique', 'couleur' => '#0A9060', 'etoiles' => 5,
             'texte' => "Élu avec 67% des voix après sa prière et sa prophétie sur ma candidature. Il m'avait annoncé la date exacte de ma victoire deux mois à l'avance."],
            ['nom' => 'Amara B.', 'initiales' => 'AB', 'pays' => 'Guinée',
             'consultation' => 'Santé', 'couleur' => '#D32F2F', 'etoiles' => 5,
             'texte' => "Les médecins avaient abandonné tout espoir. Après la consultation et la prière prophétique, les examens de contrôle ont montré une guérison totale. Je rends gloire à Dieu."],
            ['nom' => 'Grace N.', 'initiales' => 'GN', 'pays' => 'Ghana',
             'consultation' => 'Voyage', 'couleur' => '#0277BD', 'etoiles' => 5,
             'texte' => "Mon dossier de visa était bloqué depuis 3 ans. Il a prophétisé une porte ouverte dans 40 jours. Le 38ème jour, j'avais mon visa en main. Dieu est fidèle."],
        ];
    }

    /** Reprise de components/GallerySection.vue:2-9. */
    public static function photos(): array
    {
        return [
            ['fichier' => 'prophet-main.jpg', 'legende' => 'Prophète Jeremiah Nahoum', 'format' => 'normal'],
            ['fichier' => 'prophet-main-2.jpeg', 'legende' => 'Consultation prophétique', 'format' => 'normal'],
            ['fichier' => 'prophet-main-3.jpeg', 'legende' => 'Le Conseiller des Rois', 'format' => 'normal'],
            ['fichier' => 'prophet-main.jpg', 'legende' => 'Ministère international', 'format' => 'wide'],
            ['fichier' => 'prophet-main-2.jpeg', 'legende' => 'Prière et intercession', 'format' => 'normal'],
            ['fichier' => 'prophet-main-3.jpeg', 'legende' => 'Parole prophétique aux nations', 'format' => 'normal'],
        ];
    }

    /** Reprise de components/StatsBar.vue:2-7. */
    public static function stats(): array
    {
        return [
            ['valeur' => '15+', 'libelle' => 'Ans de ministère'],
            ['valeur' => '40+', 'libelle' => 'Nations touchées'],
            ['valeur' => '10K+', 'libelle' => 'Consultations'],
            ['valeur' => '100%', 'libelle' => 'Parole accomplie'],
        ];
    }

    /** Reprise de lib/validations.ts:18-27. */
    public static function heures(): array
    {
        return array_map(
            static fn (string $h): array => ['valeur' => $h],
            ['08h00', '09h00', '10h00', '11h00', '14h00', '15h00', '16h00', '17h00']
        );
    }

    /** Reprise de lib/validations.ts:29-34. */
    public static function modesPaiement(): array
    {
        return [
            ['valeur' => 'mobile_money', 'libelle' => 'Mobile Money'],
            ['valeur' => 'virement', 'libelle' => 'Virement bancaire'],
            ['valeur' => 'paypal', 'libelle' => 'PayPal'],
            ['valeur' => 'crypto', 'libelle' => 'Crypto USDT'],
        ];
    }

    /**
     * Installe le contenu du site. Rejouable sans créer de doublon :
     * l'existence est testée sur le titre du post.
     *
     * ## EXAMPLES
     *
     *     wp prophet seed
     */
    public function __invoke(): void
    {
        $servicesParTitre = [];

        foreach (self::services() as $ordre => $service) {
            $id = $this->upsert(Service::SLUG, $service['titre'], [
                'post_content' => $service['description'],
                'menu_order' => $ordre + 1,
            ]);
            carbon_set_post_meta($id, 'service_icone', $service['icone']);
            carbon_set_post_meta($id, 'service_couleur', $service['couleur']);
            $servicesParTitre[$service['titre']] = $id;
        }

        foreach (self::temoignages() as $ordre => $t) {
            $id = $this->upsert(Temoignage::SLUG, $t['nom'], ['menu_order' => $ordre + 1]);
            carbon_set_post_meta($id, 'temoignage_initiales', $t['initiales']);
            carbon_set_post_meta($id, 'temoignage_pays', $t['pays']);
            carbon_set_post_meta($id, 'temoignage_couleur', $t['couleur']);
            carbon_set_post_meta($id, 'temoignage_etoiles', $t['etoiles']);
            carbon_set_post_meta($id, 'temoignage_texte', $t['texte']);

            if (isset($servicesParTitre[$t['consultation']])) {
                carbon_set_post_meta($id, 'temoignage_service', [
                    'post:' . Service::SLUG . ':' . $servicesParTitre[$t['consultation']],
                ]);
            }
        }

        foreach (self::photos() as $ordre => $photo) {
            $id = $this->upsert(Photo::SLUG, $photo['legende'], ['menu_order' => $ordre + 1]);
            carbon_set_post_meta($id, 'photo_legende', $photo['legende']);
            carbon_set_post_meta($id, 'photo_format', $photo['format']);
            WP_CLI::log(sprintf(
                'Photo « %s » créée — importer l\'image %s depuis la médiathèque.',
                $photo['legende'],
                $photo['fichier']
            ));
        }

        carbon_set_theme_option('stats', self::stats());
        carbon_set_theme_option('rdv_heures', self::heures());
        carbon_set_theme_option('rdv_modes_paiement', self::modesPaiement());

        WP_CLI::success('Contenu installé.');
    }

    private function upsert(string $postType, string $titre, array $args): int
    {
        $existants = get_posts([
            'post_type' => $postType,
            'post_status' => 'any',
            'title' => $titre,
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        $base = [
            'post_type' => $postType,
            'post_status' => 'publish',
            'post_title' => $titre,
        ];

        if ($existants !== []) {
            $base['ID'] = (int) $existants[0];
        }

        return (int) wp_insert_post(array_merge($base, $args));
    }
}
