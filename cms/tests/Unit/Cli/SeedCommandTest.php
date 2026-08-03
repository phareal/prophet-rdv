<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Cli;

use Brain\Monkey\Functions;
use ProphetCore\Cli\SeedCommand;
use ProphetCore\Tests\TestCase;

final class SeedCommandTest extends TestCase
{
    public function test_les_douze_services_du_site_sont_presents(): void
    {
        $services = SeedCommand::services();

        $this->assertCount(12, $services);
        $this->assertSame('Mariage', $services[0]['titre']);
        $this->assertSame('heart', $services[0]['icone']);
        $this->assertSame('#c2185b', $services[0]['couleur']);
        $this->assertSame('Année 2026', $services[11]['titre']);
    }

    public function test_les_six_temoignages_du_site_sont_presents(): void
    {
        $temoignages = SeedCommand::temoignages();

        $this->assertCount(6, $temoignages);
        $this->assertSame('Marie-Claire D.', $temoignages[0]['nom']);
        $this->assertSame('MC', $temoignages[0]['initiales']);
        $this->assertSame("Côte d'Ivoire", $temoignages[0]['pays']);
    }

    public function test_les_quatre_chiffres_cles_du_site_sont_presents(): void
    {
        $stats = SeedCommand::stats();

        $this->assertSame(
            [
                ['valeur' => '15+', 'libelle' => 'Ans de ministère'],
                ['valeur' => '40+', 'libelle' => 'Nations touchées'],
                ['valeur' => '10K+', 'libelle' => 'Consultations'],
                ['valeur' => '100%', 'libelle' => 'Parole accomplie'],
            ],
            $stats
        );
    }

    public function test_chaque_service_a_une_icone_et_une_description(): void
    {
        foreach (SeedCommand::services() as $service) {
            $this->assertNotSame('', $service['icone'], $service['titre']);
            $this->assertNotSame('', $service['description'], $service['titre']);
        }
    }

    public function test_les_quatorze_textes_de_la_page_d_accueil_sont_presents(): void
    {
        $contenu = SeedCommand::contenu();

        $this->assertCount(14, $contenu);
        $this->assertSame('Prophète', $contenu['hero_surtitre']);
        $this->assertSame('Jeremiah', $contenu['hero_titre_ligne_1']);
        $this->assertSame('Nahoum', $contenu['hero_titre_ligne_2']);
        $this->assertSame('Le Conseiller des Rois', $contenu['hero_sous_titre']);
        $this->assertSame('Prendre Rendez-vous', $contenu['hero_cta_principal']);
        $this->assertSame('Voir le ministère', $contenu['hero_cta_secondaire']);
        $this->assertSame('Un prophète', $contenu['about_titre_ligne_1']);
        $this->assertSame('au service des nations', $contenu['about_titre_emphase']);
        $this->assertStringContainsString('Le Conseiller des Rois', $contenu['about_texte']);
        $this->assertStringContainsString("l'Éternel", $contenu['about_texte']);
        $this->assertSame('Votre rendez-vous', $contenu['cta_titre_ligne_1']);
        $this->assertSame('avec Dieu vous attend', $contenu['cta_titre_emphase']);
        $this->assertStringContainsString('précise.', $contenu['cta_texte_ligne_1']);
        $this->assertStringContainsString('aujourd\'hui', $contenu['cta_texte_ligne_2']);
        $this->assertSame('Réserver une consultation', $contenu['cta_bouton']);
    }

    public function test_le_contenu_deja_saisi_par_un_administrateur_n_est_pas_ecrase(): void
    {
        $ecrits = [];

        Functions\when('carbon_get_theme_option')->alias(
            fn (string $cle) => $cle === 'hero_titre_ligne_1' ? 'Texte personnalisé par l\'admin' : ''
        );
        Functions\when('carbon_set_theme_option')->alias(function (string $cle, $valeur) use (&$ecrits): void {
            $ecrits[$cle] = $valeur;
        });

        SeedCommand::seedContenu();

        $this->assertArrayNotHasKey('hero_titre_ligne_1', $ecrits);
        $this->assertSame('Prophète', $ecrits['hero_surtitre']);
        $this->assertCount(13, $ecrits);
    }

    public function test_le_contenu_vide_est_ecrit_lors_d_une_premiere_execution(): void
    {
        $ecrits = [];

        Functions\when('carbon_get_theme_option')->justReturn('');
        Functions\when('carbon_set_theme_option')->alias(function (string $cle, $valeur) use (&$ecrits): void {
            $ecrits[$cle] = $valeur;
        });

        SeedCommand::seedContenu();

        $this->assertCount(14, $ecrits);
    }

    public function test_une_image_deja_presente_dans_la_mediatheque_n_est_pas_reimportee(): void
    {
        Functions\when('get_posts')->justReturn([42]);
        Functions\expect('media_handle_sideload')->never();

        $this->assertSame(42, SeedCommand::sideloadImage('prophet-main.jpg'));
    }

    public function test_une_image_absente_de_la_mediatheque_est_importee_et_son_id_renvoye(): void
    {
        $capture = [];
        $copie = sys_get_temp_dir() . '/seed-test-' . uniqid('', true);

        Functions\when('get_posts')->justReturn([]);
        Functions\when('wp_tempnam')->justReturn($copie);
        Functions\when('media_handle_sideload')->alias(function ($fileArray) use (&$capture) {
            $capture = $fileArray;

            return 99;
        });
        Functions\when('is_wp_error')->justReturn(false);

        try {
            $this->assertSame(99, SeedCommand::sideloadImage('prophet-main-3.jpeg'));
            $this->assertSame('prophet-main-3.jpeg', $capture['name']);
            $this->assertSame($copie, $capture['tmp_name']);
            // Le fichier du thème doit être copié, jamais déplacé : wp_handle_sideload()
            // supprime son 'tmp_name' une fois la copie faite en médiathèque, et pointer
            // directement sur le fichier source le ferait disparaître de resources/images/.
            $this->assertFileExists($copie);
        } finally {
            @unlink($copie);
        }
    }

    public function test_un_import_en_echec_renvoie_zero(): void
    {
        $copie = sys_get_temp_dir() . '/seed-test-' . uniqid('', true);
        $erreur = new class {
            public function get_error_message(): string
            {
                return 'fichier introuvable';
            }
        };

        Functions\when('get_posts')->justReturn([]);
        Functions\when('wp_tempnam')->justReturn($copie);
        Functions\when('media_handle_sideload')->justReturn($erreur);
        Functions\when('is_wp_error')->justReturn(true);

        $this->assertSame(0, SeedCommand::sideloadImage('prophet-main.jpg'));
    }

    public function test_un_fichier_source_introuvable_n_est_pas_importe(): void
    {
        Functions\when('get_posts')->justReturn([]);
        Functions\expect('media_handle_sideload')->never();

        $this->assertSame(0, SeedCommand::sideloadImage('fichier-inexistant.jpg'));
    }
}
