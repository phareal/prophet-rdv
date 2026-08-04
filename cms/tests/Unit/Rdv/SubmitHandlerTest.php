<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Rdv;

use Brain\Monkey\Functions;
use ProphetCore\Rdv\SubmitHandler;
use ProphetCore\Tests\TestCase;

final class SubmitHandlerTest extends TestCase
{
    public function test_la_cle_de_limitation_depend_de_l_adresse_ip(): void
    {
        $this->assertSame(
            'prophet_rdv_ip_' . md5('203.0.113.7'),
            SubmitHandler::rateLimitKey('203.0.113.7'),
        );
    }

    public function test_un_pot_de_miel_rempli_est_traite_comme_un_robot(): void
    {
        $this->assertTrue(SubmitHandler::isBot(['prophet_website' => 'http://spam.test']));
        $this->assertFalse(SubmitHandler::isBot(['prophet_website' => '']));
        $this->assertFalse(SubmitHandler::isBot([]));
    }

    public function test_un_nonce_invalide_renvoie_vers_le_formulaire_sans_rien_enregistrer(): void
    {
        $redirection = null;

        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\when('wp_unslash')->returnArg();
        Functions\when('home_url')->alias(fn($chemin = '/') => 'https://exemple.test' . $chemin);
        Functions\when('add_query_arg')->alias(
            fn($args, $url) => $url . '?' . http_build_query($args),
        );
        Functions\when('wp_generate_password')->justReturn('CLE');
        Functions\when('set_transient')->justReturn(true);
        Functions\when('wp_safe_redirect')->alias(function ($url) use (&$redirection) {
            $redirection = $url;
        });
        Functions\expect('wp_insert_post')->never();

        $handler = new class extends SubmitHandler {
            protected function terminer(): void {}
        };
        $handler->process(['prophet_nonce' => 'faux'], '203.0.113.7');

        $this->assertStringContainsString('/rdv/?', (string) $redirection);
        $this->assertStringContainsString('e=CLE', (string) $redirection);
    }

    public function test_un_pot_de_miel_pose_la_limite_de_debit_pour_l_ip(): void
    {
        $appels = [];

        Functions\when('wp_generate_password')->justReturn('CLE');
        Functions\when('home_url')->alias(fn($chemin = '/') => 'https://exemple.test' . $chemin);
        Functions\when('add_query_arg')->alias(
            fn($args, $url) => $url . '?' . http_build_query($args),
        );
        Functions\when('set_transient')->alias(function ($cle, $valeur, $ttl) use (&$appels) {
            $appels[] = compact('cle', 'valeur', 'ttl');

            return true;
        });
        Functions\when('wp_safe_redirect')->justReturn(null);

        $handler = new class extends SubmitHandler {
            protected function terminer(): void {}
        };
        $handler->process(['prophet_website' => 'http://spam.test'], '203.0.113.9');

        $cleAttendue = SubmitHandler::rateLimitKey('203.0.113.9');
        $correspondants = array_values(array_filter($appels, fn ($a) => $a['cle'] === $cleAttendue));

        $this->assertNotEmpty($correspondants, 'la limite de débit doit être posée sur le pot de miel');
        $this->assertSame(60, $correspondants[0]['ttl']);
    }

    public function test_un_nonce_invalide_pose_aussi_la_limite_de_debit_pour_l_ip(): void
    {
        $appels = [];

        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_generate_password')->justReturn('CLE');
        Functions\when('home_url')->alias(fn($chemin = '/') => 'https://exemple.test' . $chemin);
        Functions\when('add_query_arg')->alias(
            fn($args, $url) => $url . '?' . http_build_query($args),
        );
        Functions\when('set_transient')->alias(function ($cle, $valeur, $ttl) use (&$appels) {
            $appels[] = compact('cle', 'valeur', 'ttl');

            return true;
        });
        Functions\when('wp_safe_redirect')->justReturn(null);

        $handler = new class extends SubmitHandler {
            protected function terminer(): void {}
        };
        $handler->process(['prophet_nonce' => 'faux'], '203.0.113.10');

        $cleAttendue = SubmitHandler::rateLimitKey('203.0.113.10');
        $correspondants = array_values(array_filter($appels, fn ($a) => $a['cle'] === $cleAttendue));

        $this->assertNotEmpty($correspondants, 'la limite de débit doit être posée sur le nonce invalide');
        $this->assertSame(60, $correspondants[0]['ttl']);
    }

    public function test_un_pot_de_miel_sans_ip_ne_pose_aucune_limite_de_debit(): void
    {
        $appels = [];

        Functions\when('wp_generate_password')->justReturn('CLE');
        Functions\when('home_url')->alias(fn($chemin = '/') => 'https://exemple.test' . $chemin);
        Functions\when('add_query_arg')->alias(
            fn($args, $url) => $url . '?' . http_build_query($args),
        );
        Functions\when('wp_safe_redirect')->justReturn(null);
        // set_transient est bien appelé par FlashStore::put() (message d'erreur) ;
        // ce test vérifie seulement qu'aucune limite de débit (préfixe
        // prophet_rdv_ip_) n'est posée quand l'IP est vide.
        Functions\when('set_transient')->alias(function ($cle, $valeur, $ttl) use (&$appels) {
            $appels[] = compact('cle', 'valeur', 'ttl');

            return true;
        });

        $handler = new class extends SubmitHandler {
            protected function terminer(): void {}
        };
        $handler->process(['prophet_website' => 'http://spam.test'], '');

        $correspondants = array_filter($appels, fn ($a) => str_starts_with($a['cle'], 'prophet_rdv_ip_'));

        $this->assertEmpty($correspondants);
    }

    /** @return array<string, string> */
    private function donneesValides(): array
    {
        return [
            'prophet_nonce' => 'nonce-valide',
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane@example.test',
            'telephone' => '+22890000000',
            'pays' => 'Togo',
            'date' => '2030-06-10',
            'heure' => '09h00',
            'type_consultation' => 'Mariage',
            'mode_paiement' => 'mobile_money',
            'message' => '',
        ];
    }

    public function test_une_soumission_valide_enregistre_le_rdv_pose_la_limite_et_redirige(): void
    {
        $transients = [];
        $redirection = null;
        $shutdownEnregistre = false;

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('wp_unslash')->returnArg();
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_posts')->alias(function (array $args) {
            if (($args['post_type'] ?? '') === \ProphetCore\PostTypes\RendezVous::SLUG) {
                return []; // aucun rendez-vous existant : le créneau est libre
            }

            return [(object) ['post_title' => 'Mariage']];
        });
        Functions\when('carbon_get_theme_option')->alias(function (string $cle) {
            if ($cle === 'rdv_heures') {
                return [['valeur' => '09h00']];
            }
            if ($cle === 'rdv_modes_paiement') {
                return [['valeur' => 'mobile_money', 'libelle' => 'Mobile Money']];
            }

            return '';
        });
        Functions\when('wp_generate_password')->justReturn('REF0123456789');
        Functions\when('wp_insert_post')->justReturn(7);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('set_transient')->alias(function ($cle, $valeur, $ttl) use (&$transients) {
            $transients[] = compact('cle', 'valeur', 'ttl');

            return true;
        });
        Functions\when('add_action')->alias(function ($hook, $callback) use (&$shutdownEnregistre) {
            if ($hook === 'shutdown') {
                $shutdownEnregistre = true;
            }
        });
        Functions\when('home_url')->alias(fn($chemin = '/') => 'https://exemple.test' . $chemin);
        Functions\when('add_query_arg')->alias(
            fn($args, $url) => $url . '?' . http_build_query($args),
        );
        Functions\when('wp_safe_redirect')->alias(function ($url) use (&$redirection) {
            $redirection = $url;
        });

        $handler = new class extends SubmitHandler {
            protected function terminer(): void {}
        };
        $handler->process($this->donneesValides(), '203.0.113.20');

        $this->assertStringContainsString('/confirmation/?', (string) $redirection);
        $this->assertStringContainsString('ref=REF0123456789', (string) $redirection);
        $this->assertTrue($shutdownEnregistre, 'les emails doivent être envoyés au shutdown, pas avant la redirection');

        $cleAttendue = SubmitHandler::rateLimitKey('203.0.113.20');
        $correspondants = array_values(array_filter($transients, fn ($a) => $a['cle'] === $cleAttendue));
        $this->assertNotEmpty($correspondants, 'la limite de débit doit être posée après un enregistrement réussi');
        $this->assertSame(60, $correspondants[0]['ttl']);
    }

    public function test_un_creneau_deja_pris_renvoie_vers_le_formulaire_sans_poser_de_limite(): void
    {
        $transients = [];
        $redirection = null;

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('wp_unslash')->returnArg();
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_posts')->justReturn([
            (object) ['post_title' => 'Mariage'],
        ]);
        Functions\when('carbon_get_theme_option')->alias(function (string $cle) {
            if ($cle === 'rdv_heures') {
                return [['valeur' => '09h00']];
            }
            if ($cle === 'rdv_modes_paiement') {
                return [['valeur' => 'mobile_money', 'libelle' => 'Mobile Money']];
            }

            return '';
        });
        Functions\expect('wp_insert_post')->never();
        Functions\when('wp_generate_password')->justReturn('CLE');
        Functions\when('set_transient')->alias(function ($cle, $valeur, $ttl) use (&$transients) {
            $transients[] = compact('cle', 'valeur', 'ttl');

            return true;
        });
        Functions\when('home_url')->alias(fn($chemin = '/') => 'https://exemple.test' . $chemin);
        Functions\when('add_query_arg')->alias(
            fn($args, $url) => $url . '?' . http_build_query($args),
        );
        Functions\when('wp_safe_redirect')->alias(function ($url) use (&$redirection) {
            $redirection = $url;
        });

        $handler = new class extends SubmitHandler {
            protected function terminer(): void {}
        };

        // Repository::slotTaken() interroge get_posts ; un post existant suffit
        // à simuler un créneau déjà pris, sans avoir besoin de surcharger la classe.
        Functions\when('get_posts')->alias(function (array $args) {
            if (($args['post_type'] ?? '') === \ProphetCore\PostTypes\RendezVous::SLUG) {
                return [42];
            }

            return [(object) ['post_title' => 'Mariage']];
        });

        $handler->process($this->donneesValides(), '203.0.113.21');

        $this->assertStringContainsString('/rdv/?', (string) $redirection);
        $cleAttendue = SubmitHandler::rateLimitKey('203.0.113.21');
        $correspondants = array_filter($transients, fn ($a) => $a['cle'] === $cleAttendue);
        $this->assertEmpty($correspondants, 'un créneau déjà pris ne doit pas poser de limite de débit');
    }

    /**
     * `get_posts` sert plusieurs recherches (créneau, services, référence) : on
     * distingue la recherche par référence des autres via sa clé de meta_query,
     * pour simuler un rendez-vous retrouvable après sa création.
     */
    private function getPostsExigeAlias(bool $rdvRetrouvableParRef): callable
    {
        return function (array $args) use ($rdvRetrouvableParRef) {
            if (($args['post_type'] ?? '') === \ProphetCore\PostTypes\RendezVous::SLUG) {
                $clesMeta = array_column($args['meta_query'] ?? [], 'key');
                $chercheParRef = in_array(
                    \ProphetCore\PostTypes\RendezVous::metaKey('ref'),
                    $clesMeta,
                    true
                );

                if ($chercheParRef) {
                    return $rdvRetrouvableParRef ? [7] : [];
                }

                return []; // aucun créneau déjà pris
            }

            return [(object) ['ID' => 7, 'post_title' => 'Mariage']];
        };
    }

    public function test_en_mode_exige_le_paiement_redirige_vers_moneroo(): void
    {
        $redirectionMoneroo = null;
        $redirectionSure = null;

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('wp_unslash')->returnArg();
        Functions\when('get_transient')->justReturn(false);
        Functions\when('carbon_get_theme_option')->alias(fn (string $cle) => match ($cle) {
            'rdv_heures' => [['valeur' => '09h00']],
            'rdv_modes_paiement' => [['valeur' => 'mobile_money', 'libelle' => 'Mobile Money']],
            'moneroo_moment' => 'exige',
            'moneroo_actif' => true,
            'moneroo_devise' => 'XOF',
            default => '',
        });
        Functions\when('carbon_get_post_meta')->justReturn('25000');
        Functions\when('get_posts')->alias($this->getPostsExigeAlias(rdvRetrouvableParRef: true));
        Functions\when('get_post_meta')->alias(fn ($id, $cle, $single) => [
            \ProphetCore\PostTypes\RendezVous::metaKey('nom') => 'Doe',
            \ProphetCore\PostTypes\RendezVous::metaKey('prenom') => 'Jane',
            \ProphetCore\PostTypes\RendezVous::metaKey('email') => 'jane@example.test',
            \ProphetCore\PostTypes\RendezVous::metaKey('telephone') => '+22890000000',
            \ProphetCore\PostTypes\RendezVous::metaKey('pays') => 'Togo',
            \ProphetCore\PostTypes\RendezVous::metaKey('date') => '2030-06-10',
            \ProphetCore\PostTypes\RendezVous::metaKey('heure') => '09h00',
            \ProphetCore\PostTypes\RendezVous::metaKey('type_consultation') => 'Mariage',
            \ProphetCore\PostTypes\RendezVous::metaKey('mode_paiement') => 'mobile_money',
            \ProphetCore\PostTypes\RendezVous::metaKey('message') => '',
            \ProphetCore\PostTypes\RendezVous::metaKey('paiement_statut') => 'en_attente',
        ][$cle] ?? '');
        Functions\when('wp_generate_password')->justReturn('REF0123456789');
        Functions\when('wp_insert_post')->justReturn(7);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('set_transient')->justReturn(true);
        Functions\when('add_action')->justReturn(null);
        Functions\when('home_url')->alias(fn($chemin = '/') => 'https://exemple.test' . $chemin);
        Functions\when('add_query_arg')->alias(
            fn($args, $url) => $url . '?' . http_build_query($args),
        );
        Functions\when('wp_json_encode')->alias(fn ($v) => json_encode($v));
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn(json_encode([
            'data' => ['id' => 'tx_1', 'checkout_url' => 'https://checkout.moneroo.io/tx_1'],
        ]));
        Functions\when('wp_remote_post')->justReturn([]);
        Functions\when('wp_redirect')->alias(function ($url) use (&$redirectionMoneroo) {
            $redirectionMoneroo = $url;
        });
        Functions\when('wp_safe_redirect')->alias(function ($url) use (&$redirectionSure) {
            $redirectionSure = $url;
        });
        $_ENV['MONEROO_SECRET_KEY'] = 'cle_test';

        $handler = new class extends SubmitHandler {
            protected function terminer(): void {}
        };
        $handler->process($this->donneesValides(), '203.0.113.25');

        $this->assertSame('https://checkout.moneroo.io/tx_1', $redirectionMoneroo);
        $this->assertNull($redirectionSure, 'aucune redirection vers la confirmation ne doit avoir lieu');
    }

    public function test_en_mode_exige_un_echec_d_initialisation_retombe_sur_la_confirmation(): void
    {
        $redirection = null;

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('wp_unslash')->returnArg();
        Functions\when('get_transient')->justReturn(false);
        Functions\when('carbon_get_theme_option')->alias(fn (string $cle) => match ($cle) {
            'rdv_heures' => [['valeur' => '09h00']],
            'rdv_modes_paiement' => [['valeur' => 'mobile_money', 'libelle' => 'Mobile Money']],
            'moneroo_moment' => 'exige',
            'moneroo_actif' => true,
            'moneroo_devise' => 'XOF',
            default => '',
        });
        Functions\when('carbon_get_post_meta')->justReturn('25000');
        // La référence reste introuvable après la création : simule une
        // initialisation impossible (rendez-vous déjà réglé, appel Moneroo en
        // échec, etc. — la cause exacte importe peu, seule la retombée compte).
        Functions\when('get_posts')->alias($this->getPostsExigeAlias(rdvRetrouvableParRef: false));
        Functions\when('wp_generate_password')->justReturn('REF0123456789');
        Functions\when('wp_insert_post')->justReturn(7);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('set_transient')->justReturn(true);
        Functions\when('add_action')->justReturn(null);
        Functions\when('home_url')->alias(fn($chemin = '/') => 'https://exemple.test' . $chemin);
        Functions\when('add_query_arg')->alias(
            fn($args, $url) => $url . '?' . http_build_query($args),
        );
        Functions\when('wp_redirect')->justReturn(null);
        Functions\when('wp_safe_redirect')->alias(function ($url) use (&$redirection) {
            $redirection = $url;
        });
        Functions\when('error_log')->justReturn(true);
        $_ENV['MONEROO_SECRET_KEY'] = 'cle_test';

        $handler = new class extends SubmitHandler {
            protected function terminer(): void {}
        };
        $handler->process($this->donneesValides(), '203.0.113.26');

        $this->assertStringContainsString('/confirmation/?', (string) $redirection);
        $this->assertStringContainsString('ref=REF0123456789', (string) $redirection);
    }
}
