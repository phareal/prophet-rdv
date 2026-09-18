# Paiement Moneroo — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permettre le règlement en ligne d'une consultation via Moneroo, sans jamais faire dépendre l'enregistrement d'un rendez-vous du succès du paiement.

**Architecture:** Un espace de noms `ProphetCore\Paiement` dans le mu-plugin, composé de petites classes à responsabilité unique : correspondance des statuts, résolution du montant, client HTTP, accès aux métadonnées, puis trois points d'entrée (initialisation, retour navigateur, webhook) et une tâche planifiée d'abandon. Le thème ne reçoit qu'un bouton et trois états d'affichage.

**Tech Stack:** PHP 8.2, WordPress 6.x, API HTTP de WordPress (`wp_remote_post`), Transients, WP-Cron, REST API, Carbon Fields, PHPUnit 10 + Brain Monkey.

**Spec:** `docs/superpowers/specs/2026-08-03-paiement-moneroo-design.md`

## Global Constraints

- Tout le métier vit dans `cms/web/app/mu-plugins/prophet-core/src/`, namespace `ProphetCore\`. Le thème reste présentation seule.
- **Le rendez-vous est enregistré avant toute redirection vers Moneroo, sans exception.**
- **Le montant vient du serveur**, lu depuis le prix du service ; jamais du formulaire ni d'un paramètre d'URL.
- **Le `paymentStatus` de l'URL de retour n'est jamais cru** : seule une réponse de `GET /v1/payments/{id}` met à jour un statut.
- Les clés d'API ne vivent que dans `.env` (`MONEROO_SECRET_KEY`, `MONEROO_WEBHOOK_SECRET`), jamais en base, jamais commitées, jamais journalisées.
- **Aucune donnée personnelle dans les journaux** : on journalise par référence de rendez-vous et identifiant de transaction. Règle déjà en vigueur dans `Services/Mailer.php`.
- Toute méta de rendez-vous passe par `RendezVous::metaKey()` — Carbon Fields préfixe d'un `_` et une clé littérale crée un second jeu de données invisible.
- Montants en **unités majeures** de la devise : pour XOF, des francs entiers (`decimals: 0`).
- Chaînes visibles en français ; messages de commit en français.
- `phpunit.xml` a `failOnWarning="true"` et `beStrictAboutOutputDuringTests="true"` : sortie de test impeccable.

## Environnement

- Tests : `docker compose -f docker-compose.cms.dev.yml run --rm app sh -c "cd /srv && vendor/bin/phpunit"`. Point de départ : **85 tests / 238 assertions vertes**.
- WP-CLI : `docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp …` — **`exec`, jamais `run`** (l'entrypoint du service est `tail -f /dev/null`, `run` échoue en silence). Lent : prévoir des délais généreux.
- Carbon Fields ne se peuple pas avec `wp post meta update` : passer par `wp eval` et `carbon_set_post_meta()` / `carbon_set_theme_option()`.
- Thème : `cd cms/web/app/themes/prophet && npm run build` sur l'hôte. Site sur `http://localhost:8080`.
- Ne jamais perturber les conteneurs `atlas_blue-*` ni `prophet_rdv_*`. Ne pas reconstruire d'images, ne pas démarrer la pile de production.
- `cms/patchwork.json` autorise déjà Brain Monkey à remplacer `error_log`.

## Structure des fichiers

```
cms/web/app/mu-plugins/prophet-core/src/
├── Paiement/
│   ├── Statut.php              # constantes + correspondance Moneroo → nôtres
│   ├── Montant.php             # résolution du prix depuis le CPT service
│   ├── Moneroo.php             # client HTTP : initialize, retrieve
│   ├── MonerooException.php
│   ├── PaiementRepository.php  # lecture/écriture des métas, idempotence
│   ├── InitHandler.php         # admin-post prophet_paiement_init
│   ├── RetourHandler.php       # vérification serveur au retour navigateur
│   ├── Webhook.php             # route REST, signature, idempotence
│   └── Abandon.php             # cron d'annulation des paiements abandonnés
├── Fields/PaiementOptions.php  # page d'options « Paiement »
cms/tests/Unit/Paiement/…       # un fichier de test par classe testable

Modifiés :
├── Fields/ServiceFields.php        # + champ service_prix
├── Options.php                     # + accesseurs de paiement
├── PostTypes/RendezVous.php        # + colonne et libellés de paiement
├── Rdv/Repository.php              # + statut de paiement initial
├── Rdv/SubmitHandler.php           # + mode exigé
├── prophet-core.php                # enregistrements
cms/web/app/themes/prophet/
├── app/View/Composers/Confirmation.php
└── resources/views/template-confirmation.blade.php
```

---

### Task 1: Statuts de paiement et correspondance Moneroo

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Paiement/Statut.php`
- Test: `cms/tests/Unit/Paiement/StatutTest.php`

**Interfaces:**
- Consumes: rien
- Produces:
  - `Statut::NON_REQUIS = 'non_requis'`, `EN_ATTENTE = 'en_attente'`, `PAYE = 'paye'`, `ECHOUE = 'echoue'`, `ANNULE = 'annule'`
  - `Statut::depuisMoneroo(string $statutMoneroo): string`
  - `Statut::estFinal(string $statut): bool`
  - `Statut::libelle(string $statut): string`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Paiement/StatutTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use ProphetCore\Paiement\Statut;
use ProphetCore\Tests\TestCase;

final class StatutTest extends TestCase
{
    public function test_les_cinq_statuts_moneroo_sont_traduits(): void
    {
        $this->assertSame(Statut::EN_ATTENTE, Statut::depuisMoneroo('initiated'));
        $this->assertSame(Statut::EN_ATTENTE, Statut::depuisMoneroo('pending'));
        $this->assertSame(Statut::PAYE, Statut::depuisMoneroo('success'));
        $this->assertSame(Statut::ECHOUE, Statut::depuisMoneroo('failed'));
        $this->assertSame(Statut::ANNULE, Statut::depuisMoneroo('cancelled'));
    }

    public function test_un_statut_moneroo_inconnu_reste_en_attente(): void
    {
        // Prudence délibérée : un statut inconnu ne doit jamais être pris pour
        // un paiement reçu ni pour un échec définitif.
        $this->assertSame(Statut::EN_ATTENTE, Statut::depuisMoneroo('quelque_chose_de_neuf'));
    }

    public function test_seuls_paye_echoue_et_annule_sont_finaux(): void
    {
        $this->assertTrue(Statut::estFinal(Statut::PAYE));
        $this->assertTrue(Statut::estFinal(Statut::ECHOUE));
        $this->assertTrue(Statut::estFinal(Statut::ANNULE));
        $this->assertFalse(Statut::estFinal(Statut::EN_ATTENTE));
        $this->assertFalse(Statut::estFinal(Statut::NON_REQUIS));
    }

    public function test_les_libelles_sont_en_francais(): void
    {
        $this->assertSame('Non requis', Statut::libelle(Statut::NON_REQUIS));
        $this->assertSame('En attente', Statut::libelle(Statut::EN_ATTENTE));
        $this->assertSame('Réglé', Statut::libelle(Statut::PAYE));
        $this->assertSame('Échoué', Statut::libelle(Statut::ECHOUE));
        $this->assertSame('Annulé', Statut::libelle(Statut::ANNULE));
    }

    public function test_un_statut_interne_inconnu_renvoie_sa_propre_valeur(): void
    {
        $this->assertSame('inattendu', Statut::libelle('inattendu'));
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter StatutTest"
```

Attendu : ÉCHEC — `Class "ProphetCore\Paiement\Statut" not found`.

- [ ] **Step 3: Implémenter**

`cms/web/app/mu-plugins/prophet-core/src/Paiement/Statut.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

final class Statut
{
    public const NON_REQUIS = 'non_requis';
    public const EN_ATTENTE = 'en_attente';
    public const PAYE = 'paye';
    public const ECHOUE = 'echoue';
    public const ANNULE = 'annule';

    private const DEPUIS_MONEROO = [
        'initiated' => self::EN_ATTENTE,
        'pending' => self::EN_ATTENTE,
        'success' => self::PAYE,
        'failed' => self::ECHOUE,
        'cancelled' => self::ANNULE,
    ];

    private const LIBELLES = [
        self::NON_REQUIS => 'Non requis',
        self::EN_ATTENTE => 'En attente',
        self::PAYE => 'Réglé',
        self::ECHOUE => 'Échoué',
        self::ANNULE => 'Annulé',
    ];

    /**
     * Un statut inconnu retombe sur « en attente » : jamais sur « réglé », qui
     * créditerait à tort, ni sur un échec définitif, qui fermerait la porte.
     */
    public static function depuisMoneroo(string $statutMoneroo): string
    {
        return self::DEPUIS_MONEROO[$statutMoneroo] ?? self::EN_ATTENTE;
    }

    public static function estFinal(string $statut): bool
    {
        return in_array($statut, [self::PAYE, self::ECHOUE, self::ANNULE], true);
    }

    public static function libelle(string $statut): string
    {
        return self::LIBELLES[$statut] ?? $statut;
    }
}
```

- [ ] **Step 4: Lancer le test et vérifier qu'il passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter StatutTest"
```

Attendu : PASS, 5 tests.

- [ ] **Step 5: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core/src/Paiement cms/tests/Unit/Paiement
git commit -m "feat(paiement): statuts de paiement et correspondance avec Moneroo"
```

---

### Task 2: Champ prix, page d'options et accesseurs

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Fields/PaiementOptions.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/src/Fields/ServiceFields.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/src/Options.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/prophet-core.php`
- Test: `cms/tests/Unit/OptionsPaiementTest.php`

**Interfaces:**
- Consumes: `Statut` (tâche 1)
- Produces:
  - `Options::paiementActif(): bool`
  - `Options::devise(): string` — défaut `XOF`
  - `Options::momentPaiement(): string` — `facultatif` | `exige`
  - `Options::texteBoutonPaiement(): string`
  - `Options::delaiAbandon(): int` — minutes, défaut 30
  - Champ Carbon Fields `service_prix` sur le CPT `service`
  - `PaiementOptions::register(): void`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/OptionsPaiementTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit;

use Brain\Monkey\Functions;
use ProphetCore\Options;
use ProphetCore\Tests\TestCase;

final class OptionsPaiementTest extends TestCase
{
    private function options(array $valeurs): void
    {
        Functions\when('carbon_get_theme_option')->alias(
            static fn (string $cle) => $valeurs[$cle] ?? ''
        );
    }

    public function test_le_paiement_est_inactif_par_defaut(): void
    {
        $this->options([]);

        $this->assertFalse(Options::paiementActif());
    }

    public function test_le_paiement_s_active_par_la_case_a_cocher(): void
    {
        $this->options(['moneroo_actif' => true]);

        $this->assertTrue(Options::paiementActif());
    }

    public function test_la_devise_par_defaut_est_le_franc_cfa(): void
    {
        $this->options([]);

        $this->assertSame('XOF', Options::devise());
    }

    public function test_le_moment_par_defaut_est_facultatif(): void
    {
        // Le défaut sûr : un paiement exigé ferait perdre les demandes de tous
        // les visiteurs dont le règlement échoue.
        $this->options([]);

        $this->assertSame('facultatif', Options::momentPaiement());
    }

    public function test_le_moment_exige_est_lu_quand_il_est_configure(): void
    {
        $this->options(['moneroo_moment' => 'exige']);

        $this->assertSame('exige', Options::momentPaiement());
    }

    public function test_un_moment_inconnu_retombe_sur_facultatif(): void
    {
        $this->options(['moneroo_moment' => 'n_importe_quoi']);

        $this->assertSame('facultatif', Options::momentPaiement());
    }

    public function test_le_delai_d_abandon_vaut_trente_minutes_par_defaut(): void
    {
        $this->options([]);

        $this->assertSame(30, Options::delaiAbandon());
    }

    public function test_un_delai_d_abandon_absurde_retombe_sur_le_defaut(): void
    {
        $this->options(['moneroo_delai_abandon' => '0']);

        $this->assertSame(30, Options::delaiAbandon());
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter OptionsPaiementTest"
```

Attendu : ÉCHEC — `Call to undefined method ProphetCore\Options::paiementActif()`.

- [ ] **Step 3: Ajouter les accesseurs à `Options`**

Ajouter dans `cms/web/app/mu-plugins/prophet-core/src/Options.php`, avant la méthode privée `option()` :

```php
    public static function paiementActif(): bool
    {
        return (bool) carbon_get_theme_option('moneroo_actif');
    }

    public static function devise(): string
    {
        return self::option('moneroo_devise') ?: 'XOF';
    }

    /**
     * `facultatif` (défaut) ou `exige`. Toute autre valeur retombe sur le défaut :
     * un réglage corrompu ne doit pas rendre le paiement obligatoire à l'insu du
     * prophète.
     */
    public static function momentPaiement(): string
    {
        $moment = self::option('moneroo_moment');

        return $moment === 'exige' ? 'exige' : 'facultatif';
    }

    public static function texteBoutonPaiement(): string
    {
        return self::option('moneroo_texte_bouton') ?: 'Régler ma consultation';
    }

    public static function delaiAbandon(): int
    {
        $minutes = (int) self::option('moneroo_delai_abandon');

        return $minutes > 0 ? $minutes : 30;
    }
```

- [ ] **Step 4: Lancer le test et vérifier qu'il passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter OptionsPaiementTest"
```

Attendu : PASS, 8 tests.

- [ ] **Step 5: Ajouter le champ prix au CPT service**

Dans `cms/web/app/mu-plugins/prophet-core/src/Fields/ServiceFields.php`, ajouter au tableau `add_fields([...])` :

```php
                    Field::make('text', 'service_prix', 'Prix')
                        ->set_attribute('type', 'number')
                        ->set_help_text(
                            'Montant en unités entières de la devise configurée '
                            . '(pour le franc CFA, des francs sans décimale). '
                            . 'Laisser vide pour ne pas proposer de paiement en '
                            . 'ligne sur ce service.'
                        ),
```

- [ ] **Step 6: Créer la page d'options « Paiement »**

`cms/web/app/mu-plugins/prophet-core/src/Fields/PaiementOptions.php` :

```php
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
```

- [ ] **Step 7: Brancher dans le mu-plugin**

Dans `cms/web/app/mu-plugins/prophet-core/prophet-core.php`, ajouter l'import et l'appel auprès des autres :

```php
use ProphetCore\Fields\PaiementOptions;

PaiementOptions::register();
```

- [ ] **Step 8: Vérifier en administration**

```bash
cd cms/web/app/themes/prophet && npm run build && cd -
```

Ouvrir `http://localhost:8080/wp/wp-admin/` : le menu « Paiement » existe, ses cinq champs s'affichent, et l'écran d'édition d'un service montre le champ « Prix ». Poser une valeur, recharger, vérifier qu'elle survit :

```bash
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp eval '
  $id = get_posts(["post_type" => "service", "posts_per_page" => 1, "fields" => "ids"])[0];
  carbon_set_post_meta($id, "service_prix", "25000");
  echo carbon_get_post_meta($id, "service_prix"), PHP_EOL;'
```

Attendu : `25000`.

- [ ] **Step 9: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/OptionsPaiementTest.php
git commit -m "feat(paiement): prix par service, page d'options et accesseurs"
```

---

### Task 3: Résolution du montant depuis le service

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Paiement/Montant.php`
- Test: `cms/tests/Unit/Paiement/MontantTest.php`

**Interfaces:**
- Consumes: `Options::devise()`, `Options::paiementActif()` (tâche 2), `Rdv\Validator::PREFIXE_EVENEMENT`
- Produces: `Montant::pour(string $typeConsultation): ?array` — `['montant' => int, 'devise' => string]`, ou `null` quand aucun paiement n'est dû

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Paiement/MontantTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\Montant;
use ProphetCore\Tests\TestCase;

final class MontantTest extends TestCase
{
    private function service(?string $titre, string $prix = ''): void
    {
        Functions\when('carbon_get_theme_option')->alias(
            static fn (string $cle) => $cle === 'moneroo_actif' ? true : ''
        );
        Functions\when('get_posts')->justReturn(
            $titre === null ? [] : [(object) ['ID' => 7, 'post_title' => $titre]]
        );
        Functions\when('carbon_get_post_meta')->justReturn($prix);
    }

    public function test_le_montant_vient_du_prix_du_service(): void
    {
        $this->service('Mariage', '25000');

        $this->assertSame(
            ['montant' => 25000, 'devise' => 'XOF'],
            Montant::pour('Mariage')
        );
    }

    public function test_un_service_sans_prix_ne_donne_aucun_montant(): void
    {
        $this->service('Mariage', '');

        $this->assertNull(Montant::pour('Mariage'));
    }

    public function test_un_prix_a_zero_ne_donne_aucun_montant(): void
    {
        $this->service('Mariage', '0');

        $this->assertNull(Montant::pour('Mariage'));
    }

    public function test_un_service_introuvable_ne_donne_aucun_montant(): void
    {
        $this->service(null);

        $this->assertNull(Montant::pour('Service supprimé'));
    }

    public function test_une_inscription_a_un_evenement_ne_donne_aucun_montant(): void
    {
        // Les événements ont leurs propres modalités : aucun service ne leur
        // correspond, et on ne va pas interroger la base pour le découvrir.
        Functions\expect('get_posts')->never();
        Functions\when('carbon_get_theme_option')->justReturn(true);

        $this->assertNull(Montant::pour('Inscription — Croisade de Lomé'));
    }

    public function test_aucun_montant_quand_le_paiement_est_desactive(): void
    {
        Functions\when('carbon_get_theme_option')->justReturn('');
        Functions\expect('get_posts')->never();

        $this->assertNull(Montant::pour('Mariage'));
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter MontantTest"
```

Attendu : ÉCHEC — classe `Montant` introuvable.

- [ ] **Step 3: Implémenter**

`cms/web/app/mu-plugins/prophet-core/src/Paiement/Montant.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\Options;
use ProphetCore\PostTypes\Service;
use ProphetCore\Rdv\Validator;

final class Montant
{
    /**
     * Le montant est toujours lu depuis le service côté serveur. Un montant qui
     * transiterait par le formulaire serait modifiable par le visiteur.
     *
     * @return array{montant: int, devise: string}|null null quand rien n'est dû
     */
    public static function pour(string $typeConsultation): ?array
    {
        if (! Options::paiementActif()) {
            return null;
        }

        // Une inscription à un événement ne correspond à aucun service : ses
        // modalités sont propres à l'événement.
        if (str_starts_with($typeConsultation, Validator::PREFIXE_EVENEMENT)) {
            return null;
        }

        $services = get_posts([
            'post_type' => Service::SLUG,
            'post_status' => 'publish',
            'title' => $typeConsultation,
            'posts_per_page' => 1,
            'no_found_rows' => true,
        ]);

        if ($services === []) {
            return null;
        }

        $prix = (int) carbon_get_post_meta($services[0]->ID, 'service_prix');

        if ($prix <= 0) {
            return null;
        }

        return ['montant' => $prix, 'devise' => Options::devise()];
    }
}
```

- [ ] **Step 4: Lancer le test et vérifier qu'il passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter MontantTest"
```

Attendu : PASS, 6 tests.

- [ ] **Step 5: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core/src/Paiement/Montant.php cms/tests/Unit/Paiement/MontantTest.php
git commit -m "feat(paiement): résolution du montant depuis le prix du service"
```

---

### Task 4: Client HTTP Moneroo

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Paiement/Moneroo.php`
- Create: `cms/web/app/mu-plugins/prophet-core/src/Paiement/MonerooException.php`
- Test: `cms/tests/Unit/Paiement/MonerooTest.php`

**Interfaces:**
- Consumes: rien des tâches précédentes
- Produces:
  - `new Moneroo(string $cleSecrete)`
  - `->initialiser(array $charge): array` — renvoie `['id' => string, 'checkout_url' => string]`, lève `MonerooException`
  - `->recuperer(string $paiementId): array` — renvoie les données de la transaction, lève `MonerooException`
  - `Moneroo::API = 'https://api.moneroo.io/v1'`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Paiement/MonerooTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\Moneroo;
use ProphetCore\Paiement\MonerooException;
use ProphetCore\Tests\TestCase;

final class MonerooTest extends TestCase
{
    private array $appel = [];

    private function reponse(array $corps, int $code = 200): void
    {
        Functions\when('wp_remote_post')->alias(function ($url, $args) use ($corps, $code) {
            $this->appel = ['url' => $url, 'args' => $args];

            return ['body' => json_encode($corps), 'response' => ['code' => $code]];
        });
        Functions\when('wp_remote_get')->alias(function ($url, $args) use ($corps, $code) {
            $this->appel = ['url' => $url, 'args' => $args];

            return ['body' => json_encode($corps), 'response' => ['code' => $code]];
        });
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_body')->alias(
            static fn ($r) => $r['body']
        );
        Functions\when('wp_remote_retrieve_response_code')->alias(
            static fn ($r) => $r['response']['code']
        );
    }

    public function test_l_initialisation_renvoie_l_identifiant_et_l_url_de_paiement(): void
    {
        $this->reponse([
            'message' => 'Transaction initialized successfully',
            'data' => ['id' => '5f7b1b2c', 'checkout_url' => 'https://checkout.moneroo.io/5f7b1b2c'],
        ]);

        $resultat = (new Moneroo('cle_test'))->initialiser(['amount' => 25000]);

        $this->assertSame('5f7b1b2c', $resultat['id']);
        $this->assertSame('https://checkout.moneroo.io/5f7b1b2c', $resultat['checkout_url']);
    }

    public function test_la_cle_secrete_part_en_bearer_et_le_corps_en_json(): void
    {
        $this->reponse(['data' => ['id' => 'x', 'checkout_url' => 'https://c/x']]);

        (new Moneroo('cle_test'))->initialiser(['amount' => 25000, 'currency' => 'XOF']);

        $this->assertSame('https://api.moneroo.io/v1/payments/initialize', $this->appel['url']);
        $this->assertSame('Bearer cle_test', $this->appel['args']['headers']['Authorization']);
        $this->assertSame('application/json', $this->appel['args']['headers']['Content-Type']);
        $this->assertSame(
            ['amount' => 25000, 'currency' => 'XOF'],
            json_decode($this->appel['args']['body'], true)
        );
    }

    public function test_une_erreur_reseau_leve_une_exception(): void
    {
        Functions\when('wp_remote_post')->justReturn('erreur');
        Functions\when('is_wp_error')->justReturn(true);
        Functions\when('wp_remote_retrieve_body')->justReturn('');

        $this->expectException(MonerooException::class);

        (new Moneroo('cle_test'))->initialiser(['amount' => 1]);
    }

    public function test_un_code_http_d_erreur_leve_une_exception(): void
    {
        $this->reponse(['message' => 'Invalid amount'], 422);

        $this->expectException(MonerooException::class);

        (new Moneroo('cle_test'))->initialiser(['amount' => -1]);
    }

    public function test_une_reponse_sans_url_de_paiement_leve_une_exception(): void
    {
        $this->reponse(['data' => ['id' => 'x']]);

        $this->expectException(MonerooException::class);

        (new Moneroo('cle_test'))->initialiser(['amount' => 1]);
    }

    public function test_la_recuperation_interroge_la_transaction_par_son_identifiant(): void
    {
        $this->reponse(['data' => ['id' => 'abc', 'status' => 'success', 'amount' => 25000]]);

        $donnees = (new Moneroo('cle_test'))->recuperer('abc');

        $this->assertSame('https://api.moneroo.io/v1/payments/abc', $this->appel['url']);
        $this->assertSame('success', $donnees['status']);
    }

    public function test_une_cle_secrete_vide_leve_une_exception_sans_appel_reseau(): void
    {
        Functions\expect('wp_remote_post')->never();

        $this->expectException(MonerooException::class);

        (new Moneroo(''))->initialiser(['amount' => 1]);
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter MonerooTest"
```

Attendu : ÉCHEC — classe `Moneroo` introuvable.

- [ ] **Step 3: Implémenter l'exception**

`cms/web/app/mu-plugins/prophet-core/src/Paiement/MonerooException.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use RuntimeException;

final class MonerooException extends RuntimeException
{
}
```

- [ ] **Step 4: Implémenter le client**

`cms/web/app/mu-plugins/prophet-core/src/Paiement/Moneroo.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

final class Moneroo
{
    public const API = 'https://api.moneroo.io/v1';
    private const TIMEOUT = 15;

    public function __construct(private readonly string $cleSecrete)
    {
    }

    /**
     * @param array<string, mixed> $charge
     * @return array{id: string, checkout_url: string}
     */
    public function initialiser(array $charge): array
    {
        $donnees = $this->appeler(
            'POST',
            self::API . '/payments/initialize',
            $charge
        );

        if (! isset($donnees['id'], $donnees['checkout_url'])) {
            throw new MonerooException('Réponse d\'initialisation sans identifiant ni URL de paiement');
        }

        return [
            'id' => (string) $donnees['id'],
            'checkout_url' => (string) $donnees['checkout_url'],
        ];
    }

    /** @return array<string, mixed> */
    public function recuperer(string $paiementId): array
    {
        return $this->appeler('GET', self::API . '/payments/' . rawurlencode($paiementId));
    }

    /**
     * @param array<string, mixed>|null $charge
     * @return array<string, mixed>
     */
    private function appeler(string $methode, string $url, ?array $charge = null): array
    {
        if ($this->cleSecrete === '') {
            throw new MonerooException('MONEROO_SECRET_KEY absente de la configuration');
        }

        $args = [
            'timeout' => self::TIMEOUT,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->cleSecrete,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        if ($charge !== null) {
            $args['body'] = (string) wp_json_encode($charge);
        }

        $reponse = $methode === 'POST'
            ? wp_remote_post($url, $args)
            : wp_remote_get($url, $args);

        if (is_wp_error($reponse)) {
            throw new MonerooException('Appel Moneroo injoignable');
        }

        $code = (int) wp_remote_retrieve_response_code($reponse);
        $corps = json_decode((string) wp_remote_retrieve_body($reponse), true);

        if ($code < 200 || $code >= 300) {
            // Le message de Moneroo est technique et sans donnée personnelle.
            throw new MonerooException(sprintf(
                'Moneroo a répondu %d : %s',
                $code,
                is_array($corps) ? (string) ($corps['message'] ?? '') : ''
            ));
        }

        if (! is_array($corps) || ! isset($corps['data']) || ! is_array($corps['data'])) {
            throw new MonerooException('Réponse Moneroo inattendue');
        }

        return $corps['data'];
    }
}
```

- [ ] **Step 5: Lancer le test et vérifier qu'il passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter MonerooTest"
```

Attendu : PASS, 7 tests.

- [ ] **Step 6: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core/src/Paiement cms/tests/Unit/Paiement/MonerooTest.php
git commit -m "feat(paiement): client HTTP Moneroo pour l'initialisation et la vérification"
```

---

### Task 5: Métadonnées de paiement du rendez-vous

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Paiement/PaiementRepository.php`
- Test: `cms/tests/Unit/Paiement/PaiementRepositoryTest.php`

**Interfaces:**
- Consumes: `Statut` (tâche 1), `RendezVous::metaKey()`, `RendezVous::SLUG`
- Produces:
  - `PaiementRepository::statut(int $postId): string`
  - `->enregistrerInitialisation(int $postId, string $paiementId, int $montant, string $devise): void`
  - `->appliquerStatut(int $postId, string $statut, string $methode = ''): bool` — `false` si l'état était déjà final et inchangé
  - `->trouverParPaiementId(string $paiementId): ?int`
  - `->donnees(int $postId): array`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Paiement/PaiementRepositoryTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\PaiementRepository;
use ProphetCore\Paiement\Statut;
use ProphetCore\PostTypes\RendezVous;
use ProphetCore\Tests\TestCase;

final class PaiementRepositoryTest extends TestCase
{
    private array $metas = [];

    private function base(array $metasInitiales = []): void
    {
        $this->metas = $metasInitiales;

        Functions\when('get_post_meta')->alias(
            fn ($id, $cle, $single) => $this->metas[$cle] ?? ''
        );
        Functions\when('update_post_meta')->alias(
            function ($id, $cle, $valeur) {
                $this->metas[$cle] = $valeur;

                return true;
            }
        );
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('current_time')->justReturn('2026-08-03 10:00:00');
    }

    public function test_l_initialisation_fige_l_identifiant_le_montant_et_la_devise(): void
    {
        $this->base();

        (new PaiementRepository())->enregistrerInitialisation(7, 'tx_1', 25000, 'XOF');

        $this->assertSame('tx_1', $this->metas[RendezVous::metaKey('paiement_id')]);
        $this->assertSame(25000, $this->metas[RendezVous::metaKey('paiement_montant')]);
        $this->assertSame('XOF', $this->metas[RendezVous::metaKey('paiement_devise')]);
        $this->assertSame(Statut::EN_ATTENTE, $this->metas[RendezVous::metaKey('paiement_statut')]);
    }

    public function test_le_statut_par_defaut_est_non_requis(): void
    {
        $this->base();

        $this->assertSame(Statut::NON_REQUIS, (new PaiementRepository())->statut(7));
    }

    public function test_appliquer_un_statut_le_stocke_avec_le_moyen_et_l_horodatage(): void
    {
        $this->base([RendezVous::metaKey('paiement_statut') => Statut::EN_ATTENTE]);

        $applique = (new PaiementRepository())->appliquerStatut(7, Statut::PAYE, 'mtn_bj');

        $this->assertTrue($applique);
        $this->assertSame(Statut::PAYE, $this->metas[RendezVous::metaKey('paiement_statut')]);
        $this->assertSame('mtn_bj', $this->metas[RendezVous::metaKey('paiement_methode')]);
        $this->assertSame('2026-08-03 10:00:00', $this->metas[RendezVous::metaKey('paiement_verifie_le')]);
    }

    public function test_reappliquer_le_meme_statut_final_ne_fait_rien(): void
    {
        // Le webhook et le retour navigateur portent la même information : le
        // second arrivé ne doit produire aucun effet.
        $this->base([RendezVous::metaKey('paiement_statut') => Statut::PAYE]);

        $applique = (new PaiementRepository())->appliquerStatut(7, Statut::PAYE, 'mtn_bj');

        $this->assertFalse($applique);
    }

    public function test_un_statut_final_ne_peut_pas_etre_ecrase(): void
    {
        // Un paiement reçu ne redevient jamais « en attente » sur un webhook tardif.
        $this->base([RendezVous::metaKey('paiement_statut') => Statut::PAYE]);

        $applique = (new PaiementRepository())->appliquerStatut(7, Statut::EN_ATTENTE);

        $this->assertFalse($applique);
        $this->assertSame(Statut::PAYE, $this->metas[RendezVous::metaKey('paiement_statut')]);
    }

    public function test_la_recherche_par_identifiant_de_transaction(): void
    {
        $capture = [];
        Functions\when('get_posts')->alias(function ($args) use (&$capture) {
            $capture = $args;

            return [42];
        });

        $id = (new PaiementRepository())->trouverParPaiementId('tx_1');

        $this->assertSame(42, $id);
        $this->assertSame(RendezVous::SLUG, $capture['post_type']);
        $this->assertSame(
            RendezVous::metaKey('paiement_id'),
            $capture['meta_query'][0]['key']
        );
    }

    public function test_un_identifiant_de_transaction_inconnu_renvoie_null(): void
    {
        Functions\when('get_posts')->justReturn([]);

        $this->assertNull((new PaiementRepository())->trouverParPaiementId('inconnu'));
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter PaiementRepositoryTest"
```

Attendu : ÉCHEC — classe `PaiementRepository` introuvable.

- [ ] **Step 3: Implémenter**

`cms/web/app/mu-plugins/prophet-core/src/Paiement/PaiementRepository.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\PostTypes\RendezVous;

final class PaiementRepository
{
    public function statut(int $postId): string
    {
        $statut = (string) get_post_meta($postId, RendezVous::metaKey('paiement_statut'), true);

        return $statut !== '' ? $statut : Statut::NON_REQUIS;
    }

    /**
     * Le montant et la devise sont figés ici : un changement de tarif ne doit pas
     * réécrire l'historique d'une transaction déjà engagée.
     */
    public function enregistrerInitialisation(
        int $postId,
        string $paiementId,
        int $montant,
        string $devise,
    ): void {
        update_post_meta($postId, RendezVous::metaKey('paiement_id'), sanitize_text_field($paiementId));
        update_post_meta($postId, RendezVous::metaKey('paiement_montant'), $montant);
        update_post_meta($postId, RendezVous::metaKey('paiement_devise'), sanitize_text_field($devise));
        update_post_meta($postId, RendezVous::metaKey('paiement_statut'), Statut::EN_ATTENTE);
    }

    /**
     * Idempotent et monotone : un statut déjà final n'est jamais écrasé, que ce
     * soit par le même événement reçu deux fois ou par un webhook tardif.
     *
     * @return bool true si l'état a réellement changé
     */
    public function appliquerStatut(int $postId, string $statut, string $methode = ''): bool
    {
        if (Statut::estFinal($this->statut($postId))) {
            return false;
        }

        update_post_meta($postId, RendezVous::metaKey('paiement_statut'), $statut);

        if ($methode !== '') {
            update_post_meta($postId, RendezVous::metaKey('paiement_methode'), sanitize_text_field($methode));
        }

        update_post_meta($postId, RendezVous::metaKey('paiement_verifie_le'), current_time('mysql'));

        return true;
    }

    public function trouverParPaiementId(string $paiementId): ?int
    {
        $posts = get_posts([
            'post_type' => RendezVous::SLUG,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [
                ['key' => RendezVous::metaKey('paiement_id'), 'value' => $paiementId],
            ],
        ]);

        return $posts === [] ? null : (int) $posts[0];
    }

    /** @return array{statut: string, id: string, montant: int, devise: string, methode: string} */
    public function donnees(int $postId): array
    {
        $lire = static fn (string $champ): string => (string) get_post_meta(
            $postId,
            RendezVous::metaKey($champ),
            true
        );

        return [
            'statut' => $this->statut($postId),
            'id' => $lire('paiement_id'),
            'montant' => (int) $lire('paiement_montant'),
            'devise' => $lire('paiement_devise'),
            'methode' => $lire('paiement_methode'),
        ];
    }
}
```

- [ ] **Step 4: Lancer le test et vérifier qu'il passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter PaiementRepositoryTest"
```

Attendu : PASS, 7 tests.

- [ ] **Step 5: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core/src/Paiement/PaiementRepository.php \
        cms/tests/Unit/Paiement/PaiementRepositoryTest.php
git commit -m "feat(paiement): métadonnées de paiement du rendez-vous, idempotentes"
```

---

### Task 6: Initialisation du paiement

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Paiement/InitHandler.php`
- Test: `cms/tests/Unit/Paiement/InitHandlerTest.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/prophet-core.php`

**Interfaces:**
- Consumes: `Moneroo`, `Montant`, `PaiementRepository`, `Statut`, `Rdv\Repository::findByRef()`, `Options::env()`
- Produces:
  - `InitHandler::ACTION = 'prophet_paiement_init'`
  - `InitHandler::register(): void`
  - `->demarrer(string $ref): string` — renvoie l'URL de paiement, lève `MonerooException`
  - `protected function terminer(): void` — surchargeable en test, comme `Rdv\SubmitHandler`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Paiement/InitHandlerTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use ProphetCore\Paiement\InitHandler;
use ProphetCore\Paiement\MonerooException;
use ProphetCore\Tests\TestCase;

final class InitHandlerTest extends TestCase
{
    private array $charge = [];

    private function contexte(string $statutExistant = 'non_requis', string $prix = '25000'): void
    {
        Functions\when('carbon_get_theme_option')->alias(
            static fn (string $cle) => match ($cle) {
                'moneroo_actif' => true,
                'moneroo_devise' => 'XOF',
                default => '',
            }
        );
        Functions\when('carbon_get_post_meta')->justReturn($prix);
        Functions\when('get_posts')->justReturn([(object) ['ID' => 7, 'post_title' => 'Mariage']]);
        Functions\when('get_post_meta')->alias(
            static fn ($id, $cle, $single) => str_ends_with($cle, 'paiement_statut')
                ? $statutExistant
                : ''
        );
        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('current_time')->justReturn('2026-08-03 10:00:00');
        Functions\when('home_url')->alias(static fn ($c = '/') => 'https://exemple.test' . $c);
        Functions\when('add_query_arg')->alias(
            static fn ($args, $url) => $url . '?' . http_build_query($args)
        );
        Functions\when('wp_json_encode')->alias(static fn ($v) => json_encode($v));
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn(json_encode([
            'data' => ['id' => 'tx_1', 'checkout_url' => 'https://checkout.moneroo.io/tx_1'],
        ]));
        Functions\when('wp_remote_post')->alias(function ($url, $args) {
            $this->charge = json_decode($args['body'], true);

            return [];
        });
        $_ENV['MONEROO_SECRET_KEY'] = 'cle_test';
    }

    private function rdvFactice(): array
    {
        return [
            'nom' => 'Doe', 'prenom' => 'Jane', 'email' => 'jane@example.test',
            'telephone' => '+22890000000', 'pays' => 'Togo',
            'date' => new DateTimeImmutable('2026-08-11'), 'heure' => '09h00',
            'type_consultation' => 'Mariage', 'mode_paiement' => 'mobile_money',
            'message' => '', 'ref' => 'REF123', 'post_id' => 7,
        ];
    }

    public function test_le_montant_envoye_vient_du_service_et_non_de_l_appelant(): void
    {
        $this->contexte();
        $handler = $this->handler($this->rdvFactice());

        $handler->demarrer('REF123');

        $this->assertSame(25000, $this->charge['amount']);
        $this->assertSame('XOF', $this->charge['currency']);
    }

    public function test_l_url_de_retour_porte_la_reference_et_non_l_identifiant_de_publication(): void
    {
        $this->contexte();

        $this->handler($this->rdvFactice())->demarrer('REF123');

        $this->assertStringContainsString('/confirmation/', $this->charge['return_url']);
        $this->assertStringContainsString('REF123', $this->charge['return_url']);
        $this->assertStringNotContainsString('post_id', $this->charge['return_url']);
    }

    public function test_la_reference_voyage_en_metadonnee(): void
    {
        $this->contexte();

        $this->handler($this->rdvFactice())->demarrer('REF123');

        $this->assertSame('REF123', $this->charge['metadata']['ref']);
    }

    public function test_un_rendez_vous_deja_regle_refuse_une_seconde_initialisation(): void
    {
        $this->contexte(statutExistant: 'paye');

        $this->expectException(MonerooException::class);

        $this->handler($this->rdvFactice())->demarrer('REF123');
    }

    public function test_une_reference_inconnue_leve_une_exception(): void
    {
        $this->contexte();

        $this->expectException(MonerooException::class);

        $this->handler(null)->demarrer('INEXISTANTE');
    }

    public function test_un_service_sans_prix_leve_une_exception(): void
    {
        $this->contexte(prix: '');

        $this->expectException(MonerooException::class);

        $this->handler($this->rdvFactice())->demarrer('REF123');
    }

    private function handler(?array $rdv): InitHandler
    {
        return new class ($rdv) extends InitHandler {
            public function __construct(private readonly ?array $rdv)
            {
            }

            protected function chargerRendezVous(string $ref): ?array
            {
                return $this->rdv;
            }

            protected function terminer(): void
            {
            }
        };
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter InitHandlerTest"
```

Attendu : ÉCHEC — classe `InitHandler` introuvable.

- [ ] **Step 3: Implémenter**

`cms/web/app/mu-plugins/prophet-core/src/Paiement/InitHandler.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\Options;
use ProphetCore\Rdv\Repository;

class InitHandler
{
    public const ACTION = 'prophet_paiement_init';

    public static function register(): void
    {
        $handler = new self();

        add_action('admin_post_' . self::ACTION, [$handler, 'handle']);
        add_action('admin_post_nopriv_' . self::ACTION, [$handler, 'handle']);
    }

    public function handle(): void
    {
        $ref = isset($_GET['ref']) ? sanitize_text_field(wp_unslash($_GET['ref'])) : '';

        try {
            $url = $this->demarrer($ref);
        } catch (MonerooException $e) {
            error_log('[Paiement] initialisation impossible (réf. ' . $ref . ') : ' . $e->getMessage());
            wp_safe_redirect(add_query_arg(
                ['ref' => $ref, 'paiement' => 'erreur'],
                home_url('/confirmation/')
            ));
            $this->terminer();

            return;
        }

        wp_redirect($url);
        $this->terminer();
    }

    /**
     * @return string l'URL de paiement Moneroo
     * @throws MonerooException
     */
    public function demarrer(string $ref): string
    {
        $rdv = $this->chargerRendezVous($ref);

        if ($rdv === null) {
            throw new MonerooException('Référence de rendez-vous inconnue');
        }

        $postId = (int) $rdv['post_id'];
        $paiements = new PaiementRepository();

        if ($paiements->statut($postId) === Statut::PAYE) {
            throw new MonerooException('Rendez-vous déjà réglé');
        }

        $montant = Montant::pour((string) $rdv['type_consultation']);

        if ($montant === null) {
            throw new MonerooException('Aucun montant à régler pour ce rendez-vous');
        }

        $client = new Moneroo(Options::env('MONEROO_SECRET_KEY'));

        $transaction = $client->initialiser([
            'amount' => $montant['montant'],
            'currency' => $montant['devise'],
            'description' => 'Consultation — ' . $rdv['type_consultation'],
            'customer' => [
                'email' => $rdv['email'],
                'first_name' => $rdv['prenom'],
                'last_name' => $rdv['nom'],
                'phone' => $rdv['telephone'],
            ],
            // L'URL de retour ne porte que la référence : un identifiant de
            // publication y serait énumérable.
            'return_url' => add_query_arg(['ref' => $ref], home_url('/confirmation/')),
            'metadata' => ['ref' => $ref],
        ]);

        $paiements->enregistrerInitialisation(
            $postId,
            $transaction['id'],
            $montant['montant'],
            $montant['devise'],
        );

        return $transaction['checkout_url'];
    }

    /** @return array<string, mixed>|null */
    protected function chargerRendezVous(string $ref): ?array
    {
        return (new Repository())->findByRef($ref);
    }

    protected function terminer(): void
    {
        exit;
    }
}
```

- [ ] **Step 4: Lancer le test et vérifier qu'il passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter InitHandlerTest"
```

Attendu : PASS, 6 tests.

Si le test échoue sur `post_id` absent, c'est que `Repository::findByRef()` ne le renvoie pas encore : la tâche 10 l'ajoute. Vérifier alors ce point et y revenir.

- [ ] **Step 5: Brancher dans le mu-plugin**

Dans `prophet-core.php`, dans le `add_action('init', …)` existant :

```php
use ProphetCore\Paiement\InitHandler;

    InitHandler::register();
```

- [ ] **Step 6: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/Paiement/InitHandlerTest.php
git commit -m "feat(paiement): initialisation d'une transaction depuis la référence du rendez-vous"
```

---

### Task 7: Vérification au retour du visiteur

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Paiement/RetourHandler.php`
- Test: `cms/tests/Unit/Paiement/RetourHandlerTest.php`

**Interfaces:**
- Consumes: `Moneroo::recuperer()`, `PaiementRepository`, `Statut`
- Produces: `RetourHandler::verifier(int $postId, string $paiementId): string` — renvoie le statut interne appliqué

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Paiement/RetourHandlerTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\RetourHandler;
use ProphetCore\Paiement\Statut;
use ProphetCore\Tests\TestCase;

final class RetourHandlerTest extends TestCase
{
    private array $metas = [];

    private function transaction(string $statutMoneroo, string $methode = 'mtn_bj'): void
    {
        $this->metas = [];

        Functions\when('get_post_meta')->alias(fn ($id, $cle, $s) => $this->metas[$cle] ?? '');
        Functions\when('update_post_meta')->alias(function ($id, $cle, $v) {
            $this->metas[$cle] = $v;

            return true;
        });
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('current_time')->justReturn('2026-08-03 10:00:00');
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn(json_encode([
            'data' => [
                'id' => 'tx_1',
                'status' => $statutMoneroo,
                'payment_method' => $methode,
            ],
        ]));
        Functions\when('wp_remote_get')->justReturn([]);
        Functions\when('error_log')->justReturn(true);
        $_ENV['MONEROO_SECRET_KEY'] = 'cle_test';
    }

    public function test_un_paiement_reussi_est_enregistre_comme_regle(): void
    {
        $this->transaction('success');

        $statut = (new RetourHandler())->verifier(7, 'tx_1');

        $this->assertSame(Statut::PAYE, $statut);
    }

    public function test_un_paiement_echoue_est_enregistre_comme_tel(): void
    {
        $this->transaction('failed');

        $this->assertSame(Statut::ECHOUE, (new RetourHandler())->verifier(7, 'tx_1'));
    }

    public function test_le_moyen_de_paiement_renvoye_est_conserve(): void
    {
        $this->transaction('success', 'orange_money_sn');

        (new RetourHandler())->verifier(7, 'tx_1');

        $this->assertSame(
            'orange_money_sn',
            $this->metas[\ProphetCore\PostTypes\RendezVous::metaKey('paiement_methode')]
        );
    }

    public function test_un_appel_moneroo_en_echec_laisse_le_statut_inchange(): void
    {
        $this->transaction('success');
        Functions\when('is_wp_error')->justReturn(true);
        $this->metas[\ProphetCore\PostTypes\RendezVous::metaKey('paiement_statut')] = Statut::EN_ATTENTE;

        $statut = (new RetourHandler())->verifier(7, 'tx_1');

        $this->assertSame(Statut::EN_ATTENTE, $statut);
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter RetourHandlerTest"
```

Attendu : ÉCHEC — classe `RetourHandler` introuvable.

- [ ] **Step 3: Implémenter**

`cms/web/app/mu-plugins/prophet-core/src/Paiement/RetourHandler.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\Options;
use Throwable;

final class RetourHandler
{
    /**
     * Le paramètre `paymentStatus` de l'URL de retour n'est jamais lu : un
     * visiteur peut le réécrire. Seule la réponse de l'API fait foi.
     *
     * @return string le statut interne après vérification
     */
    public function verifier(int $postId, string $paiementId): string
    {
        $paiements = new PaiementRepository();

        try {
            $transaction = (new Moneroo(Options::env('MONEROO_SECRET_KEY')))
                ->recuperer($paiementId);
        } catch (Throwable $e) {
            error_log('[Paiement] vérification impossible (transaction ' . $paiementId . ') : ' . $e->getMessage());

            return $paiements->statut($postId);
        }

        $statut = Statut::depuisMoneroo((string) ($transaction['status'] ?? ''));

        $paiements->appliquerStatut(
            $postId,
            $statut,
            (string) ($transaction['payment_method'] ?? ''),
        );

        return $paiements->statut($postId);
    }
}
```

- [ ] **Step 4: Lancer le test et vérifier qu'il passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter RetourHandlerTest"
```

Attendu : PASS, 4 tests.

- [ ] **Step 5: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core/src/Paiement/RetourHandler.php \
        cms/tests/Unit/Paiement/RetourHandlerTest.php
git commit -m "feat(paiement): vérification serveur au retour du visiteur"
```

---

### Task 8: Webhook signé

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Paiement/Webhook.php`
- Test: `cms/tests/Unit/Paiement/WebhookTest.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/prophet-core.php`

**Interfaces:**
- Consumes: `PaiementRepository`, `Statut`, `Options::env()`
- Produces:
  - `Webhook::register(): void` — route `POST /wp-json/prophet/v1/moneroo/webhook`
  - `Webhook::signatureValide(string $corpsBrut, string $signature, string $secret): bool`
  - `->traiter(string $corpsBrut, string $signature): int` — code HTTP à renvoyer

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Paiement/WebhookTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\Statut;
use ProphetCore\Paiement\Webhook;
use ProphetCore\PostTypes\RendezVous;
use ProphetCore\Tests\TestCase;

final class WebhookTest extends TestCase
{
    private const SECRET = 'secret_webhook';

    private array $metas = [];

    private function contexte(?int $postId = 7): void
    {
        $this->metas = [];

        Functions\when('get_post_meta')->alias(fn ($id, $cle, $s) => $this->metas[$cle] ?? '');
        Functions\when('update_post_meta')->alias(function ($id, $cle, $v) {
            $this->metas[$cle] = $v;

            return true;
        });
        Functions\when('get_posts')->justReturn($postId === null ? [] : [$postId]);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('current_time')->justReturn('2026-08-03 10:00:00');
        Functions\when('error_log')->justReturn(true);
        $_ENV['MONEROO_WEBHOOK_SECRET'] = self::SECRET;
    }

    private function corps(string $evenement = 'payment.success', string $statut = 'success'): string
    {
        return (string) json_encode([
            'event' => $evenement,
            'data' => ['id' => 'tx_1', 'status' => $statut, 'payment_method' => 'mtn_bj'],
        ]);
    }

    private function signer(string $corps): string
    {
        return hash_hmac('sha256', $corps, self::SECRET);
    }

    public function test_une_signature_valide_est_acceptee(): void
    {
        $corps = $this->corps();

        $this->assertTrue(Webhook::signatureValide($corps, $this->signer($corps), self::SECRET));
    }

    public function test_une_signature_invalide_est_rejetee(): void
    {
        $corps = $this->corps();

        $this->assertFalse(Webhook::signatureValide($corps, 'mauvaise', self::SECRET));
    }

    public function test_un_corps_modifie_invalide_la_signature(): void
    {
        $signature = $this->signer($this->corps());
        $corpsFalsifie = $this->corps(statut: 'success') . ' ';

        $this->assertFalse(Webhook::signatureValide($corpsFalsifie, $signature, self::SECRET));
    }

    public function test_une_signature_absente_est_rejetee(): void
    {
        $this->assertFalse(Webhook::signatureValide($this->corps(), '', self::SECRET));
    }

    public function test_un_secret_absent_rejette_tout(): void
    {
        $corps = $this->corps();

        $this->assertFalse(Webhook::signatureValide($corps, $this->signer($corps), ''));
    }

    public function test_une_signature_invalide_renvoie_403_sans_rien_ecrire(): void
    {
        $this->contexte();
        Functions\expect('update_post_meta')->never();

        $code = (new Webhook())->traiter($this->corps(), 'mauvaise');

        $this->assertSame(403, $code);
    }

    public function test_un_paiement_reussi_met_le_rendez_vous_a_jour(): void
    {
        $this->contexte();
        $corps = $this->corps();

        $code = (new Webhook())->traiter($corps, $this->signer($corps));

        $this->assertSame(200, $code);
        $this->assertSame(Statut::PAYE, $this->metas[RendezVous::metaKey('paiement_statut')]);
    }

    public function test_le_meme_evenement_recu_deux_fois_ne_produit_qu_un_effet(): void
    {
        $this->contexte();
        $corps = $this->corps();
        $signature = $this->signer($corps);
        $webhook = new Webhook();

        $this->assertSame(200, $webhook->traiter($corps, $signature));
        $this->assertSame(200, $webhook->traiter($corps, $signature));
        $this->assertSame(Statut::PAYE, $this->metas[RendezVous::metaKey('paiement_statut')]);
    }

    public function test_une_transaction_inconnue_est_acquittee_sans_erreur(): void
    {
        // Acquitter évite que Moneroo réessaie indéfiniment un événement qui ne
        // nous concerne pas.
        $this->contexte(postId: null);
        $corps = $this->corps();

        $this->assertSame(200, (new Webhook())->traiter($corps, $this->signer($corps)));
    }

    public function test_un_corps_illisible_renvoie_400(): void
    {
        $this->contexte();
        $corps = 'pas du json';

        $this->assertSame(400, (new Webhook())->traiter($corps, $this->signer($corps)));
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter WebhookTest"
```

Attendu : ÉCHEC — classe `Webhook` introuvable.

- [ ] **Step 3: Implémenter**

`cms/web/app/mu-plugins/prophet-core/src/Paiement/Webhook.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\Options;
use WP_REST_Request;
use WP_REST_Response;

final class Webhook
{
    public const ESPACE = 'prophet/v1';
    public const ROUTE = '/moneroo/webhook';
    private const ENTETE_SIGNATURE = 'x-moneroo-signature';

    public static function register(): void
    {
        add_action('rest_api_init', static function (): void {
            register_rest_route(self::ESPACE, self::ROUTE, [
                'methods' => 'POST',
                // La route est publique par nature : Moneroo n'a pas de compte
                // WordPress. L'authentification, c'est la signature.
                'permission_callback' => '__return_true',
                'callback' => static function (WP_REST_Request $requete): WP_REST_Response {
                    $code = (new self())->traiter(
                        $requete->get_body(),
                        (string) $requete->get_header(self::ENTETE_SIGNATURE),
                    );

                    return new WP_REST_Response(null, $code);
                },
            ]);
        });
    }

    /**
     * Comparaison à temps constant : une comparaison naïve laisse fuir la
     * signature attendue par la mesure du temps de réponse.
     */
    public static function signatureValide(string $corpsBrut, string $signature, string $secret): bool
    {
        if ($secret === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $corpsBrut, $secret), $signature);
    }

    /** @return int le code HTTP à renvoyer */
    public function traiter(string $corpsBrut, string $signature): int
    {
        if (! self::signatureValide($corpsBrut, $signature, Options::env('MONEROO_WEBHOOK_SECRET'))) {
            error_log('[Paiement] webhook à signature invalide rejeté');

            return 403;
        }

        $charge = json_decode($corpsBrut, true);

        if (! is_array($charge) || ! isset($charge['data']['id'])) {
            error_log('[Paiement] webhook au corps illisible');

            return 400;
        }

        $paiementId = (string) $charge['data']['id'];
        $paiements = new PaiementRepository();
        $postId = $paiements->trouverParPaiementId($paiementId);

        if ($postId === null) {
            // Acquitté sans traitement : sans quoi Moneroo réessaierait trois
            // fois un événement qui ne nous concerne pas.
            error_log('[Paiement] webhook pour une transaction inconnue : ' . $paiementId);

            return 200;
        }

        $paiements->appliquerStatut(
            $postId,
            Statut::depuisMoneroo((string) ($charge['data']['status'] ?? '')),
            (string) ($charge['data']['payment_method'] ?? ''),
        );

        return 200;
    }
}
```

- [ ] **Step 4: Lancer le test et vérifier qu'il passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter WebhookTest"
```

Attendu : PASS, 10 tests.

- [ ] **Step 5: Brancher et vérifier la route**

Dans `prophet-core.php` :

```php
use ProphetCore\Paiement\Webhook;

Webhook::register();
```

Puis vérifier que la route existe et rejette une signature absente :

```bash
curl -sS -o /dev/null -w '%{http_code}\n' -X POST \
  -H 'Content-Type: application/json' -d '{"event":"payment.success","data":{"id":"x"}}' \
  http://localhost:8080/wp-json/prophet/v1/moneroo/webhook
```

Attendu : `403`.

- [ ] **Step 6: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/Paiement/WebhookTest.php
git commit -m "feat(paiement): webhook Moneroo signé et idempotent"
```

---

### Task 9: Abandon des paiements en souffrance

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Paiement/Abandon.php`
- Test: `cms/tests/Unit/Paiement/AbandonTest.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/prophet-core.php`

**Interfaces:**
- Consumes: `Statut`, `Options::momentPaiement()`, `Options::delaiAbandon()`, `RendezVous`
- Produces:
  - `Abandon::HOOK = 'prophet_paiement_abandon'`
  - `Abandon::register(): void` — planifie l'événement horaire
  - `->passer(): int` — renvoie le nombre de rendez-vous annulés

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Paiement/AbandonTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use Brain\Monkey\Functions;
use ProphetCore\Paiement\Abandon;
use ProphetCore\Paiement\Statut;
use ProphetCore\PostTypes\RendezVous;
use ProphetCore\Tests\TestCase;

final class AbandonTest extends TestCase
{
    private array $misAJour = [];

    private function contexte(string $moment, array $candidats): void
    {
        $this->misAJour = [];

        Functions\when('carbon_get_theme_option')->alias(
            static fn (string $cle) => match ($cle) {
                'moneroo_moment' => $moment,
                'moneroo_delai_abandon' => '30',
                default => '',
            }
        );
        Functions\when('get_posts')->justReturn($candidats);
        Functions\when('get_post_meta')->justReturn(Statut::EN_ATTENTE);
        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('current_time')->justReturn('2026-08-03 10:00:00');
        Functions\when('wp_update_post')->alias(function ($args) {
            $this->misAJour[] = $args;

            return $args['ID'];
        });
        Functions\when('error_log')->justReturn(true);
    }

    public function test_en_mode_facultatif_aucun_rendez_vous_n_est_annule(): void
    {
        // Le paiement n'y conditionne pas la demande : l'annuler serait absurde.
        $this->contexte('facultatif', [7, 8]);
        Functions\expect('wp_update_post')->never();

        $this->assertSame(0, (new Abandon())->passer());
    }

    public function test_en_mode_exige_les_rendez_vous_en_souffrance_sont_annules(): void
    {
        $this->contexte('exige', [7, 8]);

        $annules = (new Abandon())->passer();

        $this->assertSame(2, $annules);
        $this->assertSame(RendezVous::STATUT_ANNULE, $this->misAJour[0]['post_status']);
        $this->assertSame(7, $this->misAJour[0]['ID']);
    }

    public function test_l_annulation_marque_aussi_le_paiement_comme_annule(): void
    {
        $this->contexte('exige', [7]);
        $ecrites = [];
        Functions\when('update_post_meta')->alias(function ($id, $cle, $v) use (&$ecrites) {
            $ecrites[$cle] = $v;

            return true;
        });

        (new Abandon())->passer();

        $this->assertSame(Statut::ANNULE, $ecrites[RendezVous::metaKey('paiement_statut')]);
    }

    public function test_la_requete_ne_retient_que_les_paiements_en_attente(): void
    {
        $capture = [];
        $this->contexte('exige', []);
        Functions\when('get_posts')->alias(function ($args) use (&$capture) {
            $capture = $args;

            return [];
        });

        (new Abandon())->passer();

        $this->assertSame(RendezVous::SLUG, $capture['post_type']);
        $this->assertSame([RendezVous::STATUT_EN_ATTENTE], $capture['post_status']);
        $this->assertSame(
            RendezVous::metaKey('paiement_statut'),
            $capture['meta_query'][0]['key']
        );
        $this->assertSame(Statut::EN_ATTENTE, $capture['meta_query'][0]['value']);
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter AbandonTest"
```

Attendu : ÉCHEC — classe `Abandon` introuvable.

- [ ] **Step 3: Implémenter**

`cms/web/app/mu-plugins/prophet-core/src/Paiement/Abandon.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

use ProphetCore\Options;
use ProphetCore\PostTypes\RendezVous;

final class Abandon
{
    public const HOOK = 'prophet_paiement_abandon';

    public static function register(): void
    {
        add_action(self::HOOK, static function (): void {
            (new self())->passer();
        });

        add_action('init', static function (): void {
            if (! wp_next_scheduled(self::HOOK)) {
                wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::HOOK);
            }
        });
    }

    /**
     * En mode exigé, un rendez-vous laissé sans règlement bloquerait son créneau
     * indéfiniment. L'annuler le libère — `Repository::slotTaken()` ne compte pas
     * les rendez-vous annulés.
     *
     * @return int nombre de rendez-vous annulés
     */
    public function passer(): int
    {
        if (Options::momentPaiement() !== 'exige') {
            return 0;
        }

        $limite = gmdate('Y-m-d H:i:s', time() - Options::delaiAbandon() * MINUTE_IN_SECONDS);

        $candidats = get_posts([
            'post_type' => RendezVous::SLUG,
            'post_status' => [RendezVous::STATUT_EN_ATTENTE],
            'fields' => 'ids',
            'posts_per_page' => 50,
            'no_found_rows' => true,
            'date_query' => [['before' => $limite, 'column' => 'post_date_gmt']],
            'meta_query' => [
                ['key' => RendezVous::metaKey('paiement_statut'), 'value' => Statut::EN_ATTENTE],
            ],
        ]);

        $paiements = new PaiementRepository();
        $annules = 0;

        foreach ($candidats as $postId) {
            wp_update_post([
                'ID' => (int) $postId,
                'post_status' => RendezVous::STATUT_ANNULE,
            ]);

            $paiements->appliquerStatut((int) $postId, Statut::ANNULE);
            $annules++;
        }

        if ($annules > 0) {
            error_log('[Paiement] ' . $annules . ' rendez-vous annulés faute de règlement');
        }

        return $annules;
    }
}
```

- [ ] **Step 4: Lancer le test et vérifier qu'il passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter AbandonTest"
```

Attendu : PASS, 4 tests.

- [ ] **Step 5: Brancher dans le mu-plugin**

```php
use ProphetCore\Paiement\Abandon;

Abandon::register();
```

- [ ] **Step 6: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/Paiement/AbandonTest.php
git commit -m "feat(paiement): annulation des rendez-vous non réglés et libération du créneau"
```

---

### Task 10: Intégration au parcours de réservation

**Files:**
- Modify: `cms/web/app/mu-plugins/prophet-core/src/Rdv/Repository.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/src/Rdv/SubmitHandler.php`
- Test: `cms/tests/Unit/Rdv/RepositoryTest.php`, `cms/tests/Unit/Rdv/SubmitHandlerTest.php`

**Interfaces:**
- Consumes: `Montant`, `Statut`, `InitHandler::demarrer()`
- Produces:
  - `Repository::create()` pose `paiement_statut` initial (`en_attente` si un montant est dû, sinon `non_requis`)
  - `Repository::findByRef()` renvoie en plus `post_id` et `ref`
  - En mode `exige`, `SubmitHandler` redirige vers Moneroo au lieu de la confirmation

- [ ] **Step 1: Écrire les tests qui échouent**

Ajouter à `cms/tests/Unit/Rdv/RepositoryTest.php` :

```php
    public function test_la_creation_pose_un_statut_de_paiement_non_requis_sans_prix(): void
    {
        $metas = [];

        Functions\when('wp_generate_password')->justReturn('REF0123456789');
        Functions\when('wp_insert_post')->justReturn(7);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('carbon_get_theme_option')->justReturn('');
        Functions\when('update_post_meta')->alias(function ($id, $cle, $valeur) use (&$metas) {
            $metas[$cle] = $valeur;

            return true;
        });

        (new Repository())->create([
            'nom' => 'Doe', 'prenom' => 'Jane', 'email' => 'jane@example.test',
            'telephone' => '+22890000000', 'pays' => 'Togo',
            'date' => new DateTimeImmutable('2026-08-05', new DateTimeZone('UTC')),
            'heure' => '09h00', 'type_consultation' => 'Mariage',
            'mode_paiement' => 'mobile_money', 'message' => '',
        ]);

        $this->assertSame(
            'non_requis',
            $metas[RendezVous::metaKey('paiement_statut')]
        );
    }

    public function test_la_recherche_par_reference_renvoie_l_identifiant_de_publication(): void
    {
        Functions\when('get_posts')->justReturn([42]);
        Functions\when('get_post_meta')->justReturn('');

        $rdv = (new Repository())->findByRef('REF123');

        $this->assertSame(42, $rdv['post_id']);
        $this->assertSame('REF123', $rdv['ref']);
    }
```

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter RepositoryTest"
```

Attendu : ÉCHEC — clé `paiement_statut` absente, puis `post_id` absent.

- [ ] **Step 3: Poser le statut initial dans `Repository::create()`**

Dans `cms/web/app/mu-plugins/prophet-core/src/Rdv/Repository.php`, ajouter à la fin du tableau `$metas`, avant la boucle d'écriture :

```php
        // Le statut de paiement naît avec le rendez-vous : « en attente » s'il y
        // a un montant dû, « non requis » sinon. Aucun appel réseau ici — le
        // rendez-vous doit être enregistré même si Moneroo est injoignable.
        $metas['paiement_statut'] = Montant::pour((string) $data['type_consultation']) === null
            ? Statut::NON_REQUIS
            : Statut::EN_ATTENTE;
```

Et les imports en tête du fichier :

```php
use ProphetCore\Paiement\Montant;
use ProphetCore\Paiement\Statut;
```

- [ ] **Step 4: Exposer `post_id` et `ref` dans `findByRef()`**

Dans la même classe, ajouter au tableau renvoyé par `findByRef()` :

```php
            'post_id' => $postId,
            'ref' => $ref,
```

- [ ] **Step 5: Lancer les tests et vérifier qu'ils passent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter RepositoryTest"
```

Attendu : PASS.

- [ ] **Step 6: Rediriger vers Moneroo en mode exigé**

Dans `cms/web/app/mu-plugins/prophet-core/src/Rdv/SubmitHandler.php`, remplacer la redirection finale (`wp_safe_redirect(add_query_arg(['ref' => $ref], home_url('/confirmation/')));`) par :

```php
        // En mode exigé, le visiteur part payer immédiatement. Le rendez-vous est
        // déjà enregistré et ses emails déjà programmés : un échec de paiement ou
        // un abandon laisse une demande exploitable, jamais un trou.
        if (Options::momentPaiement() === 'exige') {
            try {
                wp_redirect((new InitHandler())->demarrer($ref));
                $this->terminer();

                return;
            } catch (MonerooException $e) {
                error_log('[RDV] paiement non initialisable (réf. ' . $ref . ') : ' . $e->getMessage());
            }
        }

        wp_safe_redirect(add_query_arg(['ref' => $ref], home_url('/confirmation/')));
```

Et les imports :

```php
use ProphetCore\Paiement\InitHandler;
use ProphetCore\Paiement\MonerooException;
```

- [ ] **Step 7: Lancer la suite complète**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit"
```

Attendu : suite verte. Une initialisation impossible ne doit jamais empêcher la redirection vers la confirmation.

- [ ] **Step 8: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core/src/Rdv cms/tests/Unit/Rdv
git commit -m "feat(paiement): statut initial du rendez-vous et redirection en mode exigé"
```

---

### Task 11: Administration des paiements

**Files:**
- Modify: `cms/web/app/mu-plugins/prophet-core/src/PostTypes/RendezVous.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/src/Fields/RendezVousFields.php`
- Test: `cms/tests/Unit/PostTypes/RendezVousTest.php`

**Interfaces:**
- Consumes: `Statut::libelle()`, `PaiementRepository::donnees()`
- Produces: colonne `rdv_paiement` dans la liste, champs de paiement en lecture seule dans le panneau « Demande », filtre par statut de paiement

- [ ] **Step 1: Écrire le test qui échoue**

Ajouter à `cms/tests/Unit/PostTypes/RendezVousTest.php` :

```php
    public function test_la_colonne_paiement_figure_dans_la_liste(): void
    {
        $colonnes = RendezVous::adminColumns([]);

        $this->assertSame(
            ['cb', 'title', 'rdv_date', 'rdv_type', 'rdv_statut', 'rdv_paiement'],
            array_keys($colonnes)
        );
        $this->assertSame('Paiement', $colonnes['rdv_paiement']);
    }
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter RendezVousTest"
```

Attendu : ÉCHEC — la colonne manque.

- [ ] **Step 3: Ajouter la colonne**

Dans `adminColumns()`, ajouter en fin de tableau :

```php
            'rdv_paiement' => 'Paiement',
```

Et dans `renderColumn()`, une branche :

```php
            case 'rdv_paiement':
                $paiement = (new PaiementRepository())->donnees($postId);
                echo esc_html(
                    $paiement['montant'] > 0
                        ? Statut::libelle($paiement['statut'])
                            . ' — ' . $paiement['montant'] . ' ' . $paiement['devise']
                        : Statut::libelle($paiement['statut'])
                );
                break;
```

Avec les imports :

```php
use ProphetCore\Paiement\PaiementRepository;
use ProphetCore\Paiement\Statut;
```

- [ ] **Step 4: Ajouter les champs au panneau « Demande »**

Dans `cms/web/app/mu-plugins/prophet-core/src/Fields/RendezVousFields.php`, ajouter au tableau `add_fields([...])` :

```php
                    Field::make('select', 'paiement_statut', 'Statut du paiement')
                        ->set_options([
                            'non_requis' => 'Non requis',
                            'en_attente' => 'En attente',
                            'paye' => 'Réglé',
                            'echoue' => 'Échoué',
                            'annule' => 'Annulé',
                        ])
                        ->set_help_text(
                            'Modifiable à la main pour enregistrer un règlement '
                            . 'reçu hors ligne — le mobile money de la main à la '
                            . 'main reste courant.'
                        ),
                    Field::make('text', 'paiement_montant', 'Montant')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'paiement_devise', 'Devise')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'paiement_methode', 'Moyen de paiement')
                        ->set_attribute('readOnly', true),
                    Field::make('text', 'paiement_id', 'Transaction Moneroo')
                        ->set_attribute('readOnly', true),
```

- [ ] **Step 5: Ajouter le filtre par statut de paiement**

Dans `RendezVous::register()`, après les `add_filter` existants :

```php
        add_action('restrict_manage_posts', static function (string $postType): void {
            if ($postType !== self::SLUG) {
                return;
            }

            $courant = isset($_GET['paiement']) ? sanitize_text_field(wp_unslash($_GET['paiement'])) : '';

            echo '<select name="paiement"><option value="">Tous les paiements</option>';

            foreach ([Statut::NON_REQUIS, Statut::EN_ATTENTE, Statut::PAYE, Statut::ECHOUE, Statut::ANNULE] as $statut) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($statut),
                    selected($courant, $statut, false),
                    esc_html(Statut::libelle($statut))
                );
            }

            echo '</select>';
        });

        add_action('pre_get_posts', static function ($query): void {
            if (! is_admin() || ! $query->is_main_query()) {
                return;
            }

            if ($query->get('post_type') !== self::SLUG || empty($_GET['paiement'])) {
                return;
            }

            $query->set('meta_query', [[
                'key' => self::metaKey('paiement_statut'),
                'value' => sanitize_text_field(wp_unslash($_GET['paiement'])),
            ]]);
        });
```

- [ ] **Step 6: Lancer la suite et vérifier en administration**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit"
```

Puis ouvrir `http://localhost:8080/wp/wp-admin/edit.php?post_type=rendez_vous` : la colonne « Paiement » est présente, le filtre s'affiche, et l'ouverture d'un rendez-vous montre les cinq champs de paiement.

- [ ] **Step 7: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/PostTypes
git commit -m "feat(paiement): colonne, filtre et panneau de paiement en administration"
```

---

### Task 12: Page de confirmation et bouton de règlement

**Files:**
- Modify: `cms/web/app/themes/prophet/app/View/Composers/Confirmation.php`
- Modify: `cms/web/app/themes/prophet/resources/views/template-confirmation.blade.php`
- Modify: `cms/web/app/themes/prophet/resources/css/sections/_confirmation.css`

**Interfaces:**
- Consumes: `PaiementRepository::donnees()`, `RetourHandler::verifier()`, `Options::texteBoutonPaiement()`, `InitHandler::ACTION`
- Produces: `$paiement` exposé à la vue — `['statut' => string, 'montant' => int, 'devise' => string, 'libelle' => string]` — et `$erreurPaiement` (bool)

- [ ] **Step 1: Étendre le composer**

Dans `cms/web/app/themes/prophet/app/View/Composers/Confirmation.php`, après avoir chargé `$rdv`, ajouter :

```php
        // Le paramètre paymentStatus de l'URL n'est jamais lu : la présence de
        // paymentId déclenche une vérification serveur, seule source de vérité.
        if ($rdv !== null && isset($_GET['paymentId'])) {
            (new RetourHandler())->verifier(
                (int) $rdv['post_id'],
                sanitize_text_field(wp_unslash($_GET['paymentId'])),
            );
        }

        $paiement = $rdv === null
            ? ['statut' => Statut::NON_REQUIS, 'montant' => 0, 'devise' => '', 'libelle' => '']
            : (new PaiementRepository())->donnees((int) $rdv['post_id']);

        $paiement['libelle'] = Statut::libelle($paiement['statut']);
```

Puis l'ajouter au tableau renvoyé, avec :

```php
            'paiement' => $paiement,
            'texteBoutonPaiement' => Options::texteBoutonPaiement(),
            'actionPaiement' => InitHandler::ACTION,
            'erreurPaiement' => (($_GET['paiement'] ?? '') === 'erreur'),
```

Imports nécessaires :

```php
use ProphetCore\Options;
use ProphetCore\Paiement\InitHandler;
use ProphetCore\Paiement\PaiementRepository;
use ProphetCore\Paiement\RetourHandler;
use ProphetCore\Paiement\Statut;
```

- [ ] **Step 2: Ajouter les trois états à la vue**

Dans `template-confirmation.blade.php`, à l'intérieur du bloc qui affiche le récapitulatif, ajouter :

```blade
      @if ($erreurPaiement)
        <p class="conf__paiement-erreur" role="alert">
          Le paiement n'a pas pu être lancé. Votre demande est bien enregistrée —
          vous pouvez réessayer ou régler directement avec le prophète.
        </p>
      @endif

      @if ($paiement['statut'] === 'paye')
        <p class="conf__paiement conf__paiement--regle">
          <x-icon name="check-circle-2" /> Paiement reçu —
          {{ $paiement['montant'] }} {{ $paiement['devise'] }}
        </p>
      @elseif ($paiement['statut'] === 'en_attente' && $paiement['montant'] > 0)
        <form method="get" action="{{ esc_url(admin_url('admin-post.php')) }}" class="conf__paiement">
          <input type="hidden" name="action" value="{{ $actionPaiement }}">
          <input type="hidden" name="ref" value="{{ $rdv['ref'] ?? '' }}">
          <p class="conf__paiement-montant">
            Montant à régler : <strong>{{ $paiement['montant'] }} {{ $paiement['devise'] }}</strong>
          </p>
          <button type="submit" class="conf__paiement-btn">{{ $texteBoutonPaiement }}</button>
        </form>
      @elseif (in_array($paiement['statut'], ['echoue', 'annule'], true))
        <form method="get" action="{{ esc_url(admin_url('admin-post.php')) }}" class="conf__paiement">
          <input type="hidden" name="action" value="{{ $actionPaiement }}">
          <input type="hidden" name="ref" value="{{ $rdv['ref'] ?? '' }}">
          <p class="conf__paiement-erreur">
            Le règlement n'a pas abouti. Votre demande reste enregistrée.
          </p>
          <button type="submit" class="conf__paiement-btn">Réessayer le paiement</button>
        </form>
      @endif
```

- [ ] **Step 3: Styler les trois états**

Ajouter à `resources/css/sections/_confirmation.css`, **à l'intérieur** de l'enveloppe `.sec-confirmation` existante, en reprenant les variables de `tokens.css` :

```css
    .conf__paiement {
        margin: var(--s5) 0 0;
        padding: var(--s4);
        border: 1px solid var(--gold-pale);
        border-radius: var(--r4);
        text-align: center;
    }

    .conf__paiement--regle {
        border-color: var(--emerald);
        color: var(--emerald);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--s1);
    }

    .conf__paiement-montant {
        margin-bottom: var(--s3);
        font-family: var(--f-serif);
    }

    .conf__paiement-btn {
        padding: var(--s2) var(--s5);
        border-radius: var(--rpill);
        background: var(--gold);
        color: var(--ivory);
        font-family: var(--f-display);
        letter-spacing: 0.05em;
        transition: background var(--t);
    }

    .conf__paiement-btn:hover {
        background: var(--gold-light);
    }

    .conf__paiement-erreur {
        color: var(--destructive, #D32F2F);
        margin-bottom: var(--s3);
    }
```

- [ ] **Step 4: Construire et vérifier les états**

```bash
cd cms/web/app/themes/prophet && npm run build && cd -
```

Sans paiement configuré, `/confirmation/?ref=<ref>` ne doit afficher aucun bloc de paiement. Activer le paiement et poser un prix, puis refaire une demande : le bouton apparaît avec le montant.

- [ ] **Step 5: Commit**

```bash
git add cms/web/app/themes/prophet
git commit -m "feat(paiement): bouton de règlement et états de paiement sur la confirmation"
```

---

### Task 13: Configuration, documentation et recette

**Files:**
- Modify: `cms/.env.example`
- Modify: `README.md`
- Create: `docs/superpowers/notes/2026-08-03-recette-paiement.md`

**Interfaces:**
- Consumes: tout ce qui précède
- Produces: la configuration documentée et une recette tracée

- [ ] **Step 1: Documenter les clés**

Ajouter à `cms/.env.example` :

```
# ─── Paiement Moneroo ───────────────────────────────────────
# Clés obtenues sur https://app.moneroo.io — jamais en base, jamais commitées.
MONEROO_SECRET_KEY=
MONEROO_WEBHOOK_SECRET=
```

- [ ] **Step 2: Documenter dans le README**

Ajouter au tableau de configuration `cms/.env` les deux clés, et une section courte après « Administration du contenu » :

```markdown
## Paiement en ligne

Le paiement est **désactivé par défaut**. Pour l'activer :

1. Renseigner `MONEROO_SECRET_KEY` et `MONEROO_WEBHOOK_SECRET` dans `cms/.env`.
2. Dans `wp-admin` → **Paiement** : cocher l'activation, choisir la devise et le
   moment du paiement.
3. Dans **Services**, renseigner le prix de chaque consultation. Un service sans
   prix ne propose pas de paiement.
4. Déclarer l'URL de webhook sur le tableau de bord Moneroo :
   `https://votre-domaine.com/wp-json/prophet/v1/moneroo/webhook`

Le rendez-vous est enregistré avant toute redirection vers Moneroo : un paiement
abandonné ou échoué laisse une demande exploitable, visible en administration.

En mode **exigé**, une demande non réglée est annulée après le délai configuré et
son créneau est libéré.
```

- [ ] **Step 3: Dérouler la recette**

Créer `docs/superpowers/notes/2026-08-03-recette-paiement.md` avec un verdict par ligne :

| # | Vérification | Attendu |
|---|---|---|
| 1 | Paiement désactivé | aucun bloc de paiement sur la confirmation |
| 2 | Activé, service sans prix | aucun bloc de paiement |
| 3 | Activé, prix posé, mode facultatif | bouton avec le bon montant |
| 4 | Clic sur le bouton | redirection vers `checkout.moneroo.io` |
| 5 | Retour avec `paymentId` | statut vérifié côté serveur, affichage cohérent |
| 6 | Retour avec `paymentStatus=success` falsifié | **aucun** changement de statut |
| 7 | Webhook sans signature | `403` |
| 8 | Webhook signé, `payment.success` | rendez-vous marqué réglé |
| 9 | Même webhook rejoué | aucun second effet |
| 10 | Webhook pour une transaction inconnue | `200`, rien écrit |
| 11 | Rendez-vous déjà réglé, nouvelle initialisation | refusée |
| 12 | Mode exigé, demande valide | redirection directe vers Moneroo |
| 13 | Mode exigé, Moneroo injoignable | rendez-vous enregistré, redirection vers la confirmation |
| 14 | Cron d'abandon en mode exigé | rendez-vous annulé, créneau libéré |
| 15 | Cron d'abandon en mode facultatif | aucun rendez-vous touché |
| 16 | Journaux | aucune donnée personnelle, aucune clé |
| 17 | Suite PHPUnit | verte |

Le point 6 se vérifie en appelant la page de confirmation avec un `paymentStatus`
falsifié et un `paymentId` d'une transaction restée en attente : le statut ne doit
pas bouger.

Le point 14 se déclenche à la main :

```bash
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp eval \
  'echo (new ProphetCore\Paiement\Abandon())->passer(), PHP_EOL;'
```

- [ ] **Step 4: Commit**

```bash
git add cms/.env.example README.md docs/superpowers/notes
git commit -m "docs(paiement): configuration, mode d'emploi et recette"
```

---

## Notes de relecture

1. **L'ordre des tâches suit les dépendances** : statuts, montant et client HTTP n'ont besoin de rien ; le dépôt de métadonnées les suit ; les trois points d'entrée viennent ensuite ; l'intégration au parcours de réservation n'arrive qu'en tâche 10, quand tout ce qu'elle appelle existe.
2. **`Repository::findByRef()` doit renvoyer `post_id`** — la tâche 6 en dépend alors que la tâche 10 l'ajoute. La tâche 6 le signale explicitement dans son étape 4 ; si son test échoue là-dessus, faire la tâche 10 d'abord.
3. **Aucune tâche ne fait dépendre l'enregistrement d'un rendez-vous d'un appel réseau.** `Repository::create()` calcule le statut initial sans joindre Moneroo, et la tâche 10 rattrape une initialisation impossible par une redirection normale vers la confirmation.
4. **Les tests de la tâche 8 sont le cœur de la sécurité** : signature valide, invalide, absente, corps modifié, secret absent, idempotence. Ne pas les alléger.
5. **La devise `XOF` reste à confirmer** auprès de Moneroo, ainsi que la liste réelle de la tâche 2. C'est un risque identifié dans la spec.
