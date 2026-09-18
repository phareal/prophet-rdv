# Motifs de paiement — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permettre au prophète de créer ses motifs de paiement — dîmes, offrandes, alliance — depuis l'administration, chacun avec son lien permanent et son QR code, et au visiteur de régler en ligne.

**Architecture:** Une refonte préalable extrait la couche paiement du rendez-vous derrière une interface `SujetPaiement`, puis un espace de noms `ProphetCore\Don` ajoute les deux types de contenu (motif, don), la validation du montant selon trois régimes, la génération de QR codes et le formulaire public. La plomberie Moneroo n'est pas dupliquée.

**Tech Stack:** PHP 8.2, WordPress 6.x, Carbon Fields, `endroid/qr-code`, Alpine.js, PHPUnit 10 + Brain Monkey.

**Spec:** `docs/superpowers/specs/2026-08-04-motifs-de-paiement-design.md`

## Global Constraints

- Tout le métier vit dans `cms/web/app/mu-plugins/prophet-core/src/`, namespace `ProphetCore\`. Le thème reste présentation seule.
- **La refonte de la tâche 1 ne doit changer aucun comportement observable.** Les tests de paiement existants doivent passer **sans qu'une seule de leurs assertions soit modifiée**. C'est le critère qui prouve qu'elle n'a rien cassé.
- **Le don est enregistré avant toute redirection vers Moneroo**, comme le rendez-vous.
- **Le montant est validé côté serveur** contre le régime et les bornes du motif. Les attributs HTML `min`/`max` ne sont qu'un confort d'affichage. En régime `fixe`, le montant soumis est **ignoré**.
- **Vocabulaire** : « mode » de paiement = le canal (champ existant du rendez-vous) ; « motif » = la raison (ce module). Ne jamais les confondre, ni en code ni en administration.
- Toute méta passe par la méthode `metaKey()` du type concerné — Carbon Fields préfixe d'un `_` et une clé littérale crée un jeu de données invisible.
- **Aucune donnée personnelle dans les journaux** : référence et identifiant de transaction seulement.
- Les clés d'API ne vivent que dans `.env`, jamais en base, jamais journalisées.
- Chaînes visibles en français ; messages de commit en français.
- `phpunit.xml` a `failOnWarning="true"` et `beStrictAboutOutputDuringTests="true"`.

## Environnement

- Tests : `docker compose -f docker-compose.cms.dev.yml run --rm app sh -c "cd /srv && vendor/bin/phpunit"`. Point de départ : **147 tests / 342 assertions vertes**.
- Composer : `docker compose -f docker-compose.cms.dev.yml run --rm app composer <args> -d /srv`. Pas de `php`/`composer` local.
- WP-CLI : `docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp …` — **`exec`, jamais `run`** (l'entrypoint du service est `tail -f /dev/null`). Lent : délais généreux.
- Carbon Fields ne se peuple pas avec `wp post meta update` : `wp eval` + `carbon_set_post_meta()` / `carbon_set_theme_option()`.
- Thème : `cd cms/web/app/themes/prophet && npm run build` sur l'hôte. Site `http://localhost:8080`.
- **WordPress redirige `/x?a=1` vers `/x/?a=1` en 301** : utiliser `curl -L` et afficher les codes HTTP, sinon on diagnostique à tort une fonctionnalité cassée.
- Ne jamais perturber les conteneurs `atlas_blue-*` ni `prophet_rdv_*`. Ne pas reconstruire d'images, ne pas démarrer la pile de production.
- Fichiers créés par conteneur parfois root : `docker run --rm -v "$PWD":/app alpine chown -R $(id -u):$(id -g) /app/cms`.

## Structure des fichiers

```
src/Paiement/                        # refondu en tâche 1
├── SujetPaiement.php                # interface
├── ResolveurDeSujet.php             # référence ou id de transaction → sujet
├── PaiementRepository.php           # paramétré par préfixe et type de publication
├── (Statut, Montant, Moneroo, MonerooException, InitHandler,
│    RetourHandler, Webhook, Abandon — existants, recâblés)
src/Rdv/
└── RendezVousPayable.php            # implémentation pour le rendez-vous
src/Don/
├── MotifRepository.php              # lecture des motifs actifs
├── MontantDon.php                   # validation selon régime et bornes
├── DonRepository.php                # création et lecture d'un don
├── DonPayable.php                   # implémentation SujetPaiement
├── DonSubmitHandler.php             # admin-post du formulaire de don
└── QrCode.php                       # génération et cache
src/PostTypes/{MotifPaiement,Don}.php
src/Fields/{MotifPaiementFields,DonFields}.php
cms/tests/Unit/{Paiement,Don}/…

Thème :
├── app/View/Composers/Don.php
├── resources/views/template-don.blade.php
├── resources/views/partials/don-form.blade.php
└── resources/css/sections/_don.css
```

---

### Task 1: Refonte — un sujet payable

**Files:**
- Create: `src/Paiement/SujetPaiement.php`, `src/Paiement/ResolveurDeSujet.php`, `src/Rdv/RendezVousPayable.php`
- Modify: `src/Paiement/PaiementRepository.php`, `src/Paiement/InitHandler.php`, `src/Paiement/RetourHandler.php`, `src/Paiement/Webhook.php`
- Test: `cms/tests/Unit/Paiement/ResolveurDeSujetTest.php`

**Interfaces:**
- Consumes: `Statut`, `Montant`, `RendezVous::metaKey()`, `Rdv\Repository::findByRef()`
- Produces:
  - `interface SujetPaiement` — `postId(): int`, `reference(): string`, `description(): string`, `montant(): ?array`, `client(): array`, `urlRetour(): string`, `metaKey(string $champ): string`, `typeDePublication(): string`
  - `ResolveurDeSujet::enregistrer(callable $fabrique): void`
  - `ResolveurDeSujet::parReference(string $ref): ?SujetPaiement`
  - `ResolveurDeSujet::parPaiementId(string $id): ?SujetPaiement`
  - `new PaiementRepository(string $prefixe = RendezVous::META_PREFIX, string $typeDePublication = RendezVous::SLUG)`

**Le critère de réussite de cette tâche est négatif :** aucune assertion des tests existants ne bouge. Si vous devez en modifier une, c'est que la refonte change un comportement — arrêtez et signalez-le.

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Paiement/ResolveurDeSujetTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Paiement;

use ProphetCore\Paiement\ResolveurDeSujet;
use ProphetCore\Paiement\SujetPaiement;
use ProphetCore\Tests\TestCase;

final class ResolveurDeSujetTest extends TestCase
{
    private function sujetFactice(string $ref, string $paiementId = ''): SujetPaiement
    {
        return new class ($ref, $paiementId) implements SujetPaiement {
            public function __construct(
                private readonly string $ref,
                private readonly string $paiementId,
            ) {
            }

            public function postId(): int
            {
                return 7;
            }

            public function reference(): string
            {
                return $this->ref;
            }

            public function description(): string
            {
                return 'Factice';
            }

            public function montant(): ?array
            {
                return ['montant' => 1000, 'devise' => 'XOF'];
            }

            public function client(): array
            {
                return ['email' => 'a@b.test', 'prenom' => 'A', 'nom' => 'B', 'telephone' => ''];
            }

            public function urlRetour(): string
            {
                return 'https://exemple.test/retour';
            }

            public function metaKey(string $champ): string
            {
                return '_factice_' . $champ;
            }

            public function typeDePublication(): string
            {
                return 'factice';
            }
        };
    }

    protected function setUp(): void
    {
        parent::setUp();
        ResolveurDeSujet::reinitialiser();
    }

    public function test_une_reference_connue_donne_son_sujet(): void
    {
        ResolveurDeSujet::enregistrer(
            fn (string $ref): ?SujetPaiement => $ref === 'REF1' ? $this->sujetFactice('REF1') : null,
            fn (string $id): ?SujetPaiement => null,
        );

        $sujet = ResolveurDeSujet::parReference('REF1');

        $this->assertNotNull($sujet);
        $this->assertSame('REF1', $sujet->reference());
    }

    public function test_une_reference_inconnue_donne_null(): void
    {
        ResolveurDeSujet::enregistrer(
            fn (string $ref): ?SujetPaiement => null,
            fn (string $id): ?SujetPaiement => null,
        );

        $this->assertNull(ResolveurDeSujet::parReference('INCONNUE'));
    }

    public function test_le_premier_type_qui_reconnait_la_reference_gagne(): void
    {
        ResolveurDeSujet::enregistrer(
            fn (string $ref): ?SujetPaiement => null,
            fn (string $id): ?SujetPaiement => null,
        );
        ResolveurDeSujet::enregistrer(
            fn (string $ref): ?SujetPaiement => $this->sujetFactice('DEUXIEME'),
            fn (string $id): ?SujetPaiement => null,
        );

        $this->assertSame('DEUXIEME', ResolveurDeSujet::parReference('peu importe')?->reference());
    }

    public function test_la_recherche_par_identifiant_de_transaction_interroge_chaque_type(): void
    {
        ResolveurDeSujet::enregistrer(
            fn (string $ref): ?SujetPaiement => null,
            fn (string $id): ?SujetPaiement => $id === 'tx_1' ? $this->sujetFactice('REF1', 'tx_1') : null,
        );

        $this->assertSame('REF1', ResolveurDeSujet::parPaiementId('tx_1')?->reference());
        $this->assertNull(ResolveurDeSujet::parPaiementId('tx_inconnue'));
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter ResolveurDeSujetTest"
```

Attendu : ÉCHEC — interface et résolveur introuvables.

- [ ] **Step 3: Écrire l'interface**

`src/Paiement/SujetPaiement.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

/**
 * Ce qu'un objet doit savoir dire de lui-même pour être payé. Le rendez-vous et
 * le don l'implémentent chacun : la couche paiement ne connaît plus ni l'un ni
 * l'autre, ce qui évite d'en dupliquer la mécanique — et d'avoir un jour un
 * correctif de sécurité appliqué à un seul des deux chemins.
 */
interface SujetPaiement
{
    public function postId(): int;

    public function reference(): string;

    /** Libellé que Moneroo affiche au payeur. */
    public function description(): string;

    /** @return array{montant: int, devise: string}|null null si rien n'est dû */
    public function montant(): ?array;

    /** @return array{email: string, prenom: string, nom: string, telephone: string} */
    public function client(): array;

    public function urlRetour(): string;

    public function metaKey(string $champ): string;

    public function typeDePublication(): string;
}
```

- [ ] **Step 4: Écrire le résolveur**

`src/Paiement/ResolveurDeSujet.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Paiement;

/**
 * Chaque type payable s'enregistre avec deux fabriques : l'une résout une
 * référence publique, l'autre un identifiant de transaction Moneroo. Les points
 * d'entrée (initialisation, retour, webhook) n'ont ainsi plus à connaître les
 * types concrets.
 */
final class ResolveurDeSujet
{
    /** @var array<int, array{0: callable, 1: callable}> */
    private static array $fabriques = [];

    public static function enregistrer(callable $parReference, callable $parPaiementId): void
    {
        self::$fabriques[] = [$parReference, $parPaiementId];
    }

    public static function reinitialiser(): void
    {
        self::$fabriques = [];
    }

    public static function parReference(string $ref): ?SujetPaiement
    {
        foreach (self::$fabriques as [$parReference, $_]) {
            $sujet = $parReference($ref);

            if ($sujet instanceof SujetPaiement) {
                return $sujet;
            }
        }

        return null;
    }

    public static function parPaiementId(string $paiementId): ?SujetPaiement
    {
        foreach (self::$fabriques as [$_, $parPaiementId]) {
            $sujet = $parPaiementId($paiementId);

            if ($sujet instanceof SujetPaiement) {
                return $sujet;
            }
        }

        return null;
    }
}
```

- [ ] **Step 5: Paramétrer le dépôt de paiement**

Dans `src/Paiement/PaiementRepository.php`, ajouter un constructeur et remplacer chaque `RendezVous::metaKey(...)` par `$this->metaKey(...)`, et `RendezVous::SLUG` par `$this->typeDePublication` :

```php
    public function __construct(
        private readonly string $prefixe = RendezVous::META_PREFIX,
        private readonly string $typeDePublication = RendezVous::SLUG,
    ) {
    }

    private function metaKey(string $champ): string
    {
        return $this->prefixe . $champ;
    }
```

Les valeurs par défaut préservent le comportement actuel : c'est ce qui garde les tests existants verts sans y toucher.

- [ ] **Step 6: Écrire l'implémentation pour le rendez-vous**

`src/Rdv/RendezVousPayable.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Rdv;

use ProphetCore\Paiement\Montant;
use ProphetCore\Paiement\SujetPaiement;
use ProphetCore\PostTypes\RendezVous;

final class RendezVousPayable implements SujetPaiement
{
    /** @param array<string, mixed> $rdv */
    public function __construct(private readonly array $rdv)
    {
    }

    public static function parReference(string $ref): ?self
    {
        $rdv = (new Repository())->findByRef($ref);

        return $rdv === null ? null : new self($rdv);
    }

    public static function parPaiementId(string $paiementId): ?self
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

        if ($posts === []) {
            return null;
        }

        $ref = (string) get_post_meta((int) $posts[0], RendezVous::metaKey('ref'), true);

        return self::parReference($ref);
    }

    public function postId(): int
    {
        return (int) $this->rdv['post_id'];
    }

    public function reference(): string
    {
        return (string) $this->rdv['ref'];
    }

    public function description(): string
    {
        return 'Consultation — ' . $this->rdv['type_consultation'];
    }

    public function montant(): ?array
    {
        return Montant::pour((string) $this->rdv['type_consultation']);
    }

    public function client(): array
    {
        return [
            'email' => (string) $this->rdv['email'],
            'prenom' => (string) $this->rdv['prenom'],
            'nom' => (string) $this->rdv['nom'],
            'telephone' => (string) $this->rdv['telephone'],
        ];
    }

    public function urlRetour(): string
    {
        return add_query_arg(['ref' => $this->reference()], home_url('/confirmation/'));
    }

    public function metaKey(string $champ): string
    {
        return RendezVous::metaKey($champ);
    }

    public function typeDePublication(): string
    {
        return RendezVous::SLUG;
    }
}
```

- [ ] **Step 7: Recâbler les trois points d'entrée**

Dans `InitHandler::demarrer()`, remplacer le chargement du rendez-vous par le résolveur, et construire la charge depuis le sujet :

```php
        $sujet = ResolveurDeSujet::parReference($ref);

        if ($sujet === null) {
            throw new MonerooException('Référence inconnue');
        }

        $paiements = new PaiementRepository($sujet->metaKey(''), $sujet->typeDePublication());

        if ($paiements->statut($sujet->postId()) === Statut::PAYE) {
            throw new MonerooException('Déjà réglé');
        }

        $montant = $sujet->montant();

        if ($montant === null) {
            throw new MonerooException('Aucun montant à régler');
        }

        $client = $sujet->client();

        $transaction = (new Moneroo(Options::env('MONEROO_SECRET_KEY')))->initialiser([
            'amount' => $montant['montant'],
            'currency' => $montant['devise'],
            'description' => $sujet->description(),
            'customer' => [
                'email' => $client['email'],
                'first_name' => $client['prenom'],
                'last_name' => $client['nom'],
                'phone' => $client['telephone'],
            ],
            'return_url' => $sujet->urlRetour(),
            'metadata' => ['ref' => $ref],
        ]);

        $paiements->enregistrerInitialisation(
            $sujet->postId(),
            $transaction['id'],
            $montant['montant'],
            $montant['devise'],
        );

        return $transaction['checkout_url'];
```

`RetourHandler::verifier()` prend désormais un `SujetPaiement` plutôt qu'un `postId`, et `Webhook::traiter()` passe par `ResolveurDeSujet::parPaiementId()`. Adapter leurs tests **uniquement** là où la signature change — pas leurs assertions de comportement.

- [ ] **Step 8: Enregistrer le rendez-vous comme type payable**

Dans `prophet-core.php` :

```php
use ProphetCore\Paiement\ResolveurDeSujet;
use ProphetCore\Rdv\RendezVousPayable;

add_action('init', static function (): void {
    ResolveurDeSujet::enregistrer(
        static fn (string $ref) => RendezVousPayable::parReference($ref),
        static fn (string $id) => RendezVousPayable::parPaiementId($id),
    );
}, 5);
```

- [ ] **Step 9: Lancer la suite complète**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit"
```

Attendu : verte. **Si une assertion de comportement a dû changer, la refonte a modifié un comportement — signalez-le au lieu de l'accepter.**

- [ ] **Step 10: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/Paiement
git commit -m "refactor(paiement): extraire la couche paiement derrière un sujet payable"
```

---

### Task 2: Type de contenu Motif de paiement

**Files:**
- Create: `src/PostTypes/MotifPaiement.php`, `src/Fields/MotifPaiementFields.php`, `src/Don/MotifRepository.php`
- Test: `cms/tests/Unit/Don/MotifRepositoryTest.php`
- Modify: `prophet-core.php`

**Interfaces:**
- Produces:
  - `MotifPaiement::SLUG = 'motif_paiement'`, `::META_PREFIX = '_motif_'`, `::metaKey(string): string`, `::register(): void`
  - Régimes : `MotifPaiement::REGIME_LIBRE = 'libre'`, `REGIME_FIXE = 'fixe'`, `REGIME_SUGGERE = 'suggere'`
  - `MotifRepository::actifs(): array` — liste de `['id','titre','description','icone','couleur','regime','montant_fixe','suggeres','min','max']`
  - `MotifRepository::parId(int $id): ?array`, `::parSlug(string $slug): ?array`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Don/MotifRepositoryTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use Brain\Monkey\Functions;
use ProphetCore\Don\MotifRepository;
use ProphetCore\PostTypes\MotifPaiement;
use ProphetCore\Tests\TestCase;

final class MotifRepositoryTest extends TestCase
{
    private function motif(array $metas): void
    {
        Functions\when('get_posts')->justReturn([
            (object) ['ID' => 3, 'post_title' => 'Dîmes', 'post_content' => 'Description'],
        ]);
        Functions\when('carbon_get_post_meta')->alias(
            static fn ($id, $cle) => $metas[$cle] ?? ''
        );
        Functions\when('get_post_field')->justReturn('dimes');
    }

    public function test_un_motif_actif_est_lu_avec_tous_ses_champs(): void
    {
        $this->motif([
            'motif_icone' => 'hand-coins',
            'motif_couleur' => '#B07A14',
            'motif_regime' => MotifPaiement::REGIME_LIBRE,
            'motif_montant_min' => '500',
            'motif_montant_max' => '5000000',
            'motif_actif' => true,
        ]);

        $motifs = MotifRepository::actifs();

        $this->assertCount(1, $motifs);
        $this->assertSame('Dîmes', $motifs[0]['titre']);
        $this->assertSame('hand-coins', $motifs[0]['icone']);
        $this->assertSame(MotifPaiement::REGIME_LIBRE, $motifs[0]['regime']);
        $this->assertSame(500, $motifs[0]['min']);
        $this->assertSame(5000000, $motifs[0]['max']);
    }

    public function test_les_bornes_absentes_prennent_leurs_valeurs_par_defaut(): void
    {
        $this->motif(['motif_regime' => MotifPaiement::REGIME_LIBRE, 'motif_actif' => true]);

        $motifs = MotifRepository::actifs();

        $this->assertSame(500, $motifs[0]['min']);
        $this->assertSame(5000000, $motifs[0]['max']);
    }

    public function test_les_montants_suggeres_sont_aplatis_en_entiers(): void
    {
        $this->motif([
            'motif_regime' => MotifPaiement::REGIME_SUGGERE,
            'motif_montants_suggeres' => [['valeur' => '1000'], ['valeur' => '5000']],
            'motif_actif' => true,
        ]);

        $this->assertSame([1000, 5000], MotifRepository::actifs()[0]['suggeres']);
    }

    public function test_la_requete_ne_demande_que_les_motifs_publies_dans_l_ordre_du_menu(): void
    {
        $capture = [];
        Functions\when('get_posts')->alias(function ($args) use (&$capture) {
            $capture = $args;

            return [];
        });

        MotifRepository::actifs();

        $this->assertSame(MotifPaiement::SLUG, $capture['post_type']);
        $this->assertSame('publish', $capture['post_status']);
        $this->assertSame('menu_order', $capture['orderby']);
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter MotifRepositoryTest"
```

Attendu : ÉCHEC — classes introuvables.

- [ ] **Step 3: Écrire le type de contenu**

`src/PostTypes/MotifPaiement.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\PostTypes;

final class MotifPaiement
{
    public const SLUG = 'motif_paiement';
    public const META_PREFIX = '_motif_';

    public const REGIME_LIBRE = 'libre';
    public const REGIME_FIXE = 'fixe';
    public const REGIME_SUGGERE = 'suggere';

    public static function metaKey(string $champ): string
    {
        return self::META_PREFIX . $champ;
    }

    public static function register(): void
    {
        add_action('init', static function (): void {
            register_post_type(self::SLUG, [
                'labels' => [
                    'name' => 'Motifs de paiement',
                    'singular_name' => 'Motif de paiement',
                    'add_new_item' => 'Ajouter un motif',
                    'edit_item' => 'Modifier le motif',
                ],
                // Public : c'est l'URL du motif qui sert de lien partageable et
                // que le QR code encode.
                'public' => true,
                'has_archive' => false,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-money-alt',
                'supports' => ['title', 'editor', 'page-attributes'],
                'rewrite' => ['slug' => 'don', 'with_front' => false],
            ]);
        });
    }
}
```

- [ ] **Step 4: Écrire les champs**

`src/Fields/MotifPaiementFields.php` — page de champs sur le CPT, avec les conditions d'affichage de Carbon Fields pour ne montrer le montant fixe qu'en régime `fixe` et les paliers qu'en régime `suggere` :

```php
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
```

- [ ] **Step 5: Écrire le dépôt**

`src/Don/MotifRepository.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Don;

use ProphetCore\PostTypes\MotifPaiement;

final class MotifRepository
{
    private const MIN_DEFAUT = 500;
    private const MAX_DEFAUT = 5000000;

    /** @return array<int, array<string, mixed>> */
    public static function actifs(): array
    {
        $posts = get_posts([
            'post_type' => MotifPaiement::SLUG,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);

        $motifs = array_map([self::class, 'depuisPost'], $posts);

        return array_values(array_filter($motifs, static fn (array $m): bool => $m['actif']));
    }

    public static function parId(int $id): ?array
    {
        $post = get_post($id);

        return ($post === null || $post->post_type !== MotifPaiement::SLUG)
            ? null
            : self::depuisPost($post);
    }

    public static function parSlug(string $slug): ?array
    {
        $posts = get_posts([
            'post_type' => MotifPaiement::SLUG,
            'post_status' => 'publish',
            'name' => $slug,
            'posts_per_page' => 1,
            'no_found_rows' => true,
        ]);

        return $posts === [] ? null : self::depuisPost($posts[0]);
    }

    private static function depuisPost(object $post): array
    {
        $lire = static fn (string $champ) => carbon_get_post_meta($post->ID, $champ);

        $suggeres = $lire('motif_montants_suggeres');
        $suggeres = is_array($suggeres)
            ? array_map(static fn (array $ligne): int => (int) ($ligne['valeur'] ?? 0), $suggeres)
            : [];

        return [
            'id' => (int) $post->ID,
            'slug' => (string) get_post_field('post_name', $post->ID),
            'titre' => (string) $post->post_title,
            'description' => (string) $post->post_content,
            'icone' => (string) $lire('motif_icone'),
            'couleur' => (string) $lire('motif_couleur'),
            'regime' => (string) ($lire('motif_regime') ?: MotifPaiement::REGIME_LIBRE),
            'montant_fixe' => (int) $lire('motif_montant_fixe'),
            'suggeres' => array_values(array_filter($suggeres)),
            'min' => (int) $lire('motif_montant_min') ?: self::MIN_DEFAUT,
            'max' => (int) $lire('motif_montant_max') ?: self::MAX_DEFAUT,
            'actif' => (bool) $lire('motif_actif'),
        ];
    }
}
```

- [ ] **Step 6: Brancher et vérifier**

Dans `prophet-core.php` : `MotifPaiement::register();` et `MotifPaiementFields::register();`.

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter MotifRepositoryTest"
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp rewrite flush
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp post-type list --fields=name --format=csv | grep motif_paiement
```

Créer un motif « Dîmes » en administration et vérifier que `/don/dimes/` répond 200.

- [ ] **Step 7: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/Don
git commit -m "feat(don): type de contenu motif de paiement et ses réglages"
```

---

### Task 3: Validation du montant selon le régime

**Files:**
- Create: `src/Don/MontantDon.php`
- Test: `cms/tests/Unit/Don/MontantDonTest.php`

**Interfaces:**
- Consumes: `MotifPaiement::REGIME_*`, `Options::devise()`
- Produces: `MontantDon::resoudre(array $motif, string $montantSoumis): array` — `['montant' => int, 'devise' => string]` ou `['erreur' => string]`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Don/MontantDonTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use Brain\Monkey\Functions;
use ProphetCore\Don\MontantDon;
use ProphetCore\PostTypes\MotifPaiement;
use ProphetCore\Tests\TestCase;

final class MontantDonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('carbon_get_theme_option')->alias(
            static fn (string $cle) => $cle === 'moneroo_devise' ? 'XOF' : ''
        );
    }

    private function motif(string $regime, array $surcharges = []): array
    {
        return array_merge([
            'regime' => $regime,
            'montant_fixe' => 10000,
            'suggeres' => [1000, 5000],
            'min' => 500,
            'max' => 5000000,
        ], $surcharges);
    }

    public function test_en_regime_libre_le_montant_soumis_est_retenu(): void
    {
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_LIBRE), '2500');

        $this->assertSame(2500, $resultat['montant']);
        $this->assertSame('XOF', $resultat['devise']);
    }

    public function test_en_regime_fixe_le_montant_soumis_est_ignore(): void
    {
        // Un champ caché serait modifiable : le montant vient du motif.
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_FIXE), '1');

        $this->assertSame(10000, $resultat['montant']);
    }

    public function test_en_regime_suggere_une_valeur_hors_palier_est_acceptee(): void
    {
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_SUGGERE), '3000');

        $this->assertSame(3000, $resultat['montant']);
    }

    public function test_un_montant_sous_le_minimum_est_refuse(): void
    {
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_LIBRE), '100');

        $this->assertSame('Le montant minimum est de 500 XOF', $resultat['erreur']);
    }

    public function test_un_montant_au_dessus_du_maximum_est_refuse(): void
    {
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_LIBRE), '9999999');

        $this->assertSame('Le montant maximum est de 5000000 XOF', $resultat['erreur']);
    }

    public function test_un_montant_negatif_est_refuse(): void
    {
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_LIBRE), '-500');

        $this->assertArrayHasKey('erreur', $resultat);
    }

    public function test_un_montant_non_entier_est_refuse(): void
    {
        foreach (['2500,50', '2500.50', '1e5', 'beaucoup', ''] as $saisie) {
            $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_LIBRE), $saisie);

            $this->assertArrayHasKey('erreur', $resultat, 'Refusé attendu pour : ' . $saisie);
        }
    }

    public function test_les_espaces_de_saisie_sont_tolerés(): void
    {
        // « 2 500 » est ce qu'un francophone tape naturellement.
        $resultat = MontantDon::resoudre($this->motif(MotifPaiement::REGIME_LIBRE), ' 2 500 ');

        $this->assertSame(2500, $resultat['montant']);
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter MontantDonTest"
```

Attendu : ÉCHEC — classe introuvable.

- [ ] **Step 3: Implémenter**

`src/Don/MontantDon.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Don;

use ProphetCore\Options;
use ProphetCore\PostTypes\MotifPaiement;

final class MontantDon
{
    /**
     * Le montant est ici saisi par le visiteur, contrairement au rendez-vous où
     * il vient du service. D'où une validation serveur stricte : les attributs
     * HTML min et max ne sont qu'un confort d'affichage.
     *
     * @param array<string, mixed> $motif
     * @return array{montant: int, devise: string}|array{erreur: string}
     */
    public static function resoudre(array $motif, string $montantSoumis): array
    {
        $devise = Options::devise();

        if ($motif['regime'] === MotifPaiement::REGIME_FIXE) {
            return ['montant' => (int) $motif['montant_fixe'], 'devise' => $devise];
        }

        // « 2 500 » est ce qu'un francophone tape ; les espaces fines et
        // insécables comptent aussi.
        $nettoye = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', trim($montantSoumis)) ?? '';

        if ($nettoye === '' || preg_match('/^\d+$/', $nettoye) !== 1) {
            return ['erreur' => 'Veuillez saisir un montant en chiffres entiers'];
        }

        $montant = (int) $nettoye;

        if ($montant < (int) $motif['min']) {
            return ['erreur' => sprintf('Le montant minimum est de %d %s', $motif['min'], $devise)];
        }

        if ($montant > (int) $motif['max']) {
            return ['erreur' => sprintf('Le montant maximum est de %d %s', $motif['max'], $devise)];
        }

        return ['montant' => $montant, 'devise' => $devise];
    }
}
```

- [ ] **Step 4: Lancer le test et vérifier qu'il passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter MontantDonTest"
```

Attendu : PASS, 8 tests.

- [ ] **Step 5: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core/src/Don cms/tests/Unit/Don
git commit -m "feat(don): validation serveur du montant selon le régime du motif"
```

---

### Task 4: Type de contenu Don et son dépôt

**Files:**
- Create: `src/PostTypes/Don.php`, `src/Fields/DonFields.php`, `src/Don/DonRepository.php`, `src/Don/DonPayable.php`
- Test: `cms/tests/Unit/Don/DonRepositoryTest.php`
- Modify: `prophet-core.php`

**Interfaces:**
- Consumes: `SujetPaiement`, `MotifRepository`, `Options::devise()`
- Produces:
  - `Don::SLUG = 'don'`, `::META_PREFIX = '_don_'`, `::metaKey()`, statuts `STATUT_EN_ATTENTE = 'don_en_attente'`, `STATUT_RECU = 'don_recu'`, `STATUT_ECHOUE = 'don_echoue'`
  - `DonRepository::creer(array $donnees): string` — renvoie la référence
  - `DonRepository::parRef(string $ref): ?array`
  - `DonPayable::parReference()`, `::parPaiementId()`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Don/DonRepositoryTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use Brain\Monkey\Functions;
use ProphetCore\Don\DonRepository;
use ProphetCore\PostTypes\Don;
use ProphetCore\Tests\TestCase;

final class DonRepositoryTest extends TestCase
{
    private array $metas = [];

    private function base(): void
    {
        $this->metas = [];

        Functions\when('wp_generate_password')->justReturn('REFDON0123456789');
        Functions\when('wp_insert_post')->justReturn(11);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('update_post_meta')->alias(function ($id, $cle, $v) {
            $this->metas[$cle] = $v;

            return true;
        });
    }

    private function donnees(): array
    {
        return [
            'motif_id' => 3,
            'motif_titre' => 'Dîmes',
            'montant' => 2500,
            'devise' => 'XOF',
            'nom' => 'Doe', 'prenom' => 'Jane',
            'email' => 'jane@example.test', 'telephone' => '+22890000000',
            'message' => 'Que Dieu vous bénisse',
        ];
    }

    public function test_la_creation_renvoie_une_reference_et_ecrit_les_metadonnees(): void
    {
        $this->base();

        $ref = (new DonRepository())->creer($this->donnees());

        $this->assertSame('REFDON0123456789', $ref);
        $this->assertSame(3, $this->metas[Don::metaKey('motif')]);
        $this->assertSame(2500, $this->metas[Don::metaKey('montant')]);
        $this->assertSame('jane@example.test', $this->metas[Don::metaKey('email')]);
        $this->assertSame('REFDON0123456789', $this->metas[Don::metaKey('ref')]);
    }

    public function test_toutes_les_cles_portent_le_prefixe_du_type(): void
    {
        // Carbon Fields préfixe d'un underscore : une clé littérale créerait un
        // second jeu de données que l'administration n'afficherait pas.
        $this->base();

        (new DonRepository())->creer($this->donnees());

        foreach (array_keys($this->metas) as $cle) {
            $this->assertStringStartsWith('_don_', $cle);
        }
    }

    public function test_le_don_naît_en_attente_de_paiement(): void
    {
        $capture = [];
        $this->base();
        Functions\when('wp_insert_post')->alias(function ($args) use (&$capture) {
            $capture = $args;

            return 11;
        });

        (new DonRepository())->creer($this->donnees());

        $this->assertSame(Don::SLUG, $capture['post_type']);
        $this->assertSame(Don::STATUT_EN_ATTENTE, $capture['post_status']);
        $this->assertStringContainsString('Dîmes', $capture['post_title']);
    }

    public function test_une_reference_inconnue_renvoie_null(): void
    {
        Functions\when('get_posts')->justReturn([]);

        $this->assertNull((new DonRepository())->parRef('INCONNUE'));
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter DonRepositoryTest"
```

Attendu : ÉCHEC — classes introuvables.

- [ ] **Step 3: Écrire le type de contenu**

`src/PostTypes/Don.php` — privé, sans création manuelle, sur le modèle de `RendezVous` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\PostTypes;

final class Don
{
    public const SLUG = 'don';
    public const META_PREFIX = '_don_';

    public const STATUT_EN_ATTENTE = 'don_en_attente';
    public const STATUT_RECU = 'don_recu';
    public const STATUT_ECHOUE = 'don_echoue';

    private const STATUTS = [
        self::STATUT_EN_ATTENTE => 'En attente',
        self::STATUT_RECU => 'Reçu',
        self::STATUT_ECHOUE => 'Échoué',
    ];

    public static function metaKey(string $champ): string
    {
        return self::META_PREFIX . $champ;
    }

    public static function statutLabel(string $statut): string
    {
        return self::STATUTS[$statut] ?? $statut;
    }

    public static function register(): void
    {
        add_action('init', static function (): void {
            register_post_type(self::SLUG, [
                'labels' => [
                    'name' => 'Dons',
                    'singular_name' => 'Don',
                    'edit_item' => 'Détail du don',
                    'search_items' => 'Rechercher un don',
                ],
                'public' => false,
                'show_ui' => true,
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'menu_icon' => 'dashicons-heart',
                'supports' => ['title'],
                'capabilities' => ['create_posts' => 'do_not_allow'],
                'map_meta_cap' => true,
            ]);

            foreach (self::STATUTS as $slug => $libelle) {
                register_post_status($slug, [
                    'label' => $libelle,
                    'public' => false,
                    'internal' => false,
                    'protected' => true,
                    'show_in_admin_all_list' => true,
                    'show_in_admin_status_list' => true,
                    'label_count' => _n_noop($libelle . ' (%s)', $libelle . ' (%s)'),
                ]);
            }
        });
    }
}
```

`register_post_status()` exige `protected => true` sans quoi les statuts personnalisés n'apparaissent jamais dans la liste — leçon déjà payée sur le rendez-vous.

- [ ] **Step 4: Écrire le dépôt**

`src/Don/DonRepository.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Don;

use ProphetCore\PostTypes\Don;
use RuntimeException;

final class DonRepository
{
    /** @param array<string, mixed> $donnees */
    public function creer(array $donnees): string
    {
        $ref = wp_generate_password(32, false);

        $postId = wp_insert_post([
            'post_type' => Don::SLUG,
            'post_status' => Don::STATUT_EN_ATTENTE,
            'post_title' => sprintf(
                '%s %s — %s %d %s',
                $donnees['prenom'],
                $donnees['nom'],
                $donnees['motif_titre'],
                $donnees['montant'],
                $donnees['devise'],
            ),
        ], true);

        if (is_wp_error($postId)) {
            throw new RuntimeException('Création du don impossible');
        }

        $metas = [
            'motif' => (int) $donnees['motif_id'],
            'motif_titre' => sanitize_text_field((string) $donnees['motif_titre']),
            'montant' => (int) $donnees['montant'],
            'devise' => sanitize_text_field((string) $donnees['devise']),
            'nom' => sanitize_text_field((string) $donnees['nom']),
            'prenom' => sanitize_text_field((string) $donnees['prenom']),
            'email' => sanitize_email((string) $donnees['email']),
            'telephone' => sanitize_text_field((string) $donnees['telephone']),
            'message' => sanitize_textarea_field((string) $donnees['message']),
            'ref' => $ref,
        ];

        foreach ($metas as $champ => $valeur) {
            update_post_meta((int) $postId, Don::metaKey($champ), $valeur);
        }

        return $ref;
    }

    public function parRef(string $ref): ?array
    {
        $posts = get_posts([
            'post_type' => Don::SLUG,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [['key' => Don::metaKey('ref'), 'value' => $ref]],
        ]);

        if ($posts === []) {
            return null;
        }

        $postId = (int) $posts[0];
        $lire = static fn (string $champ): string => (string) get_post_meta($postId, Don::metaKey($champ), true);

        return [
            'post_id' => $postId,
            'ref' => $ref,
            'motif_id' => (int) $lire('motif'),
            'motif_titre' => $lire('motif_titre'),
            'montant' => (int) $lire('montant'),
            'devise' => $lire('devise'),
            'nom' => $lire('nom'),
            'prenom' => $lire('prenom'),
            'email' => $lire('email'),
            'telephone' => $lire('telephone'),
            'message' => $lire('message'),
        ];
    }
}
```

- [ ] **Step 5: Écrire `DonPayable` et les champs d'administration**

`src/Don/DonPayable.php` implémente `SujetPaiement` sur le modèle de `Rdv\RendezVousPayable` : `montant()` renvoie le montant figé du don, `description()` vaut `'Don — ' . motif_titre`, `urlRetour()` pointe vers `/don/merci/?ref=…`, `metaKey()` délègue à `Don::metaKey()`, `typeDePublication()` renvoie `Don::SLUG`.

`src/Fields/DonFields.php` déclare le panneau « Don » en lecture seule (motif, montant, devise, donateur, message, référence) plus les champs de paiement, avec les mêmes noms que le rendez-vous — `paiement_statut` modifiable, le reste en lecture seule.

- [ ] **Step 6: Brancher et vérifier**

Dans `prophet-core.php` : `Don::register();`, `DonFields::register();`, et l'enregistrement du type payable auprès du résolveur, à côté de celui du rendez-vous.

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit"
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp post-type list --fields=name --format=csv | grep '^don$'
```

- [ ] **Step 7: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/Don
git commit -m "feat(don): type de contenu don, dépôt et sujet payable"
```

---

### Task 5: Soumission d'un don

**Files:**
- Create: `src/Don/DonSubmitHandler.php`
- Test: `cms/tests/Unit/Don/DonSubmitHandlerTest.php`
- Modify: `prophet-core.php`

**Interfaces:**
- Consumes: `MotifRepository`, `MontantDon`, `DonRepository`, `InitHandler::demarrer()`, `FlashStore`
- Produces:
  - `DonSubmitHandler::ACTION = 'prophet_don_submit'`, `::NONCE = 'prophet_don'`, `::HONEYPOT = 'prophet_site'`
  - `::cleDeLimite(string $ip, string $email): string`
  - `->process(array $post, string $ip): void`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Don/DonSubmitHandlerTest.php` couvre : pot de miel, nonce invalide, motif inconnu, motif inactif, montant refusé, création réussie, et la forme de la clé de limitation.

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use ProphetCore\Don\DonSubmitHandler;
use ProphetCore\Tests\TestCase;

final class DonSubmitHandlerTest extends TestCase
{
    public function test_la_limite_porte_sur_le_couple_ip_et_email(): void
    {
        // Une limite par IP seule bloquerait une assemblée entière : deux cents
        // personnes qui scannent le même QR pendant un culte sortent par la
        // même adresse publique.
        $a = DonSubmitHandler::cleDeLimite('203.0.113.7', 'jane@example.test');
        $b = DonSubmitHandler::cleDeLimite('203.0.113.7', 'marc@example.test');

        $this->assertNotSame($a, $b);
        $this->assertSame($a, DonSubmitHandler::cleDeLimite('203.0.113.7', 'jane@example.test'));
    }

    public function test_l_email_est_normalise_dans_la_cle(): void
    {
        $this->assertSame(
            DonSubmitHandler::cleDeLimite('203.0.113.7', 'Jane@Example.test'),
            DonSubmitHandler::cleDeLimite('203.0.113.7', 'jane@example.test'),
        );
    }

    public function test_un_pot_de_miel_rempli_est_traite_comme_un_robot(): void
    {
        $this->assertTrue(DonSubmitHandler::isBot(['prophet_site' => 'http://spam.test']));
        $this->assertFalse(DonSubmitHandler::isBot(['prophet_site' => '']));
        $this->assertFalse(DonSubmitHandler::isBot([]));
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter DonSubmitHandlerTest"
```

Attendu : ÉCHEC — classe introuvable.

- [ ] **Step 3: Implémenter**

`src/Don/DonSubmitHandler.php`, sur le modèle de `Rdv\SubmitHandler` (même structure, même `terminer()` surchargeable) :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Don;

use ProphetCore\Paiement\InitHandler;
use ProphetCore\Paiement\MonerooException;
use ProphetCore\Rdv\FlashStore;
use Throwable;

class DonSubmitHandler
{
    public const ACTION = 'prophet_don_submit';
    public const NONCE = 'prophet_don';
    public const HONEYPOT = 'prophet_site';

    /**
     * Bien plus permissive que celle du rendez-vous, et portée sur le couple
     * (IP, email) : une limite par IP seule bloquerait une assemblée entière
     * scannant le même QR depuis un seul réseau.
     */
    private const DELAI_ENTRE_ENVOIS = 20;

    public static function register(): void
    {
        $handler = new self();

        add_action('admin_post_' . self::ACTION, [$handler, 'handle']);
        add_action('admin_post_nopriv_' . self::ACTION, [$handler, 'handle']);
    }

    public function handle(): void
    {
        $this->process(wp_unslash($_POST), (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    }

    public function process(array $post, string $ip): void
    {
        if (self::isBot($post)) {
            $this->rediriger(['global' => 'Votre don n\'a pas pu être traité.'], $post);

            return;
        }

        if (! wp_verify_nonce((string) ($post['prophet_nonce'] ?? ''), self::NONCE)) {
            $this->rediriger(['global' => 'Votre session a expiré. Merci de renvoyer le formulaire.'], $post);

            return;
        }

        $email = strtolower(trim((string) ($post['email'] ?? '')));

        if ($ip !== '' && $email !== '' && get_transient(self::cleDeLimite($ip, $email)) !== false) {
            $this->rediriger(['global' => 'Vous venez déjà d\'envoyer un don. Patientez un instant.'], $post);

            return;
        }

        $motif = MotifRepository::parId((int) ($post['motif'] ?? 0));

        if ($motif === null || ! $motif['actif']) {
            $this->rediriger(['motif' => 'Veuillez choisir un motif de paiement.'], $post);

            return;
        }

        $montant = MontantDon::resoudre($motif, (string) ($post['montant'] ?? ''));

        if (isset($montant['erreur'])) {
            $this->rediriger(['montant' => $montant['erreur']], $post);

            return;
        }

        $erreurs = $this->validerDonateur($post);

        if ($erreurs !== []) {
            $this->rediriger($erreurs, $post);

            return;
        }

        try {
            $ref = (new DonRepository())->creer([
                'motif_id' => $motif['id'],
                'motif_titre' => $motif['titre'],
                'montant' => $montant['montant'],
                'devise' => $montant['devise'],
                'nom' => $post['nom'], 'prenom' => $post['prenom'],
                'email' => $email, 'telephone' => $post['telephone'] ?? '',
                'message' => $post['message'] ?? '',
            ]);
        } catch (Throwable $e) {
            error_log('[Don] enregistrement en échec : ' . $e->getMessage());
            $this->rediriger(['global' => 'Une erreur interne est survenue. Veuillez réessayer.'], $post);

            return;
        }

        if ($ip !== '') {
            set_transient(self::cleDeLimite($ip, $email), 1, self::DELAI_ENTRE_ENVOIS);
        }

        // Le don est enregistré : un échec d'initialisation ne le perd pas.
        try {
            wp_redirect((new InitHandler())->demarrer($ref));
            $this->terminer();

            return;
        } catch (MonerooException $e) {
            error_log('[Don] paiement non initialisable (réf. ' . $ref . ') : ' . $e->getMessage());
        }

        wp_safe_redirect(add_query_arg(['ref' => $ref, 'paiement' => 'erreur'], home_url('/don/merci/')));
        $this->terminer();
    }

    public static function isBot(array $post): bool
    {
        return trim((string) ($post[self::HONEYPOT] ?? '')) !== '';
    }

    public static function cleDeLimite(string $ip, string $email): string
    {
        return 'prophet_don_' . md5($ip . '|' . strtolower(trim($email)));
    }

    /** @return array<string, string> */
    private function validerDonateur(array $post): array
    {
        $erreurs = [];

        if (mb_strlen(trim((string) ($post['prenom'] ?? ''))) < 2) {
            $erreurs['prenom'] = 'Le prénom doit contenir au moins 2 caractères';
        }

        if (mb_strlen(trim((string) ($post['nom'] ?? ''))) < 2) {
            $erreurs['nom'] = 'Le nom doit contenir au moins 2 caractères';
        }

        if (filter_var((string) ($post['email'] ?? ''), FILTER_VALIDATE_EMAIL) === false) {
            $erreurs['email'] = 'Adresse email invalide';
        }

        return $erreurs;
    }

    private function rediriger(array $erreurs, array $valeurs): void
    {
        unset($valeurs['prophet_nonce'], $valeurs[self::HONEYPOT], $valeurs['action']);

        $global = $erreurs['global'] ?? '';
        unset($erreurs['global']);

        $cle = FlashStore::put(['errors' => $erreurs, 'values' => $valeurs, 'global' => $global]);

        wp_safe_redirect(add_query_arg(['e' => $cle], home_url('/don/')) . '#don');
        $this->terminer();
    }

    protected function terminer(): void
    {
        exit;
    }
}
```

- [ ] **Step 4: Lancer les tests et brancher**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit"
```

Dans `prophet-core.php`, à côté de `SubmitHandler::register()` : `DonSubmitHandler::register();`.

- [ ] **Step 5: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/Don
git commit -m "feat(don): traitement de la soumission d'un don"
```

---

### Task 6: QR code

**Files:**
- Create: `src/Don/QrCode.php`
- Test: `cms/tests/Unit/Don/QrCodeTest.php`
- Modify: `cms/composer.json`, `src/Fields/MotifPaiementFields.php`

**Interfaces:**
- Produces:
  - `QrCode::pour(int $motifId, string $format = 'png'): string` — URL publique du fichier, généré si absent
  - `QrCode::chemin(int $motifId, string $format): string`
  - `QrCode::invalider(int $motifId): void`

- [ ] **Step 1: Installer la bibliothèque**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  composer require endroid/qr-code -d /srv
```

**Vérifier l'API de la version installée** avant d'écrire le code : `cat cms/vendor/endroid/qr-code/README.md | head -60`. Les majeures 4, 5 et 6 diffèrent sur la construction du builder. Le code ci-dessous suit la forme `Builder::create()` ; si la version installée impose la forme par constructeur, adaptez et signalez-le dans votre rapport plutôt que de rétrograder la dépendance.

- [ ] **Step 2: Écrire le test qui échoue**

`cms/tests/Unit/Don/QrCodeTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use Brain\Monkey\Functions;
use ProphetCore\Don\QrCode;
use ProphetCore\Tests\TestCase;

final class QrCodeTest extends TestCase
{
    private string $repertoire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repertoire = sys_get_temp_dir() . '/qr-test-' . uniqid();
        mkdir($this->repertoire . '/qr', 0777, true);

        Functions\when('wp_upload_dir')->justReturn([
            'basedir' => $this->repertoire,
            'baseurl' => 'https://exemple.test/uploads',
        ]);
        Functions\when('get_permalink')->justReturn('https://exemple.test/don/dimes/');
        Functions\when('wp_mkdir_p')->justReturn(true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->repertoire . '/qr/*') ?: [] as $fichier) {
            unlink($fichier);
        }

        parent::tearDown();
    }

    public function test_le_fichier_est_genere_a_la_premiere_demande(): void
    {
        $url = QrCode::pour(3, 'png');

        $this->assertFileExists(QrCode::chemin(3, 'png'));
        $this->assertStringStartsWith('https://exemple.test/uploads/qr/', $url);
    }

    public function test_le_fichier_n_est_pas_regenere_s_il_existe(): void
    {
        QrCode::pour(3, 'png');
        $premiere = filemtime(QrCode::chemin(3, 'png'));

        QrCode::pour(3, 'png');

        $this->assertSame($premiere, filemtime(QrCode::chemin(3, 'png')));
    }

    public function test_l_invalidation_supprime_les_deux_formats(): void
    {
        QrCode::pour(3, 'png');
        QrCode::pour(3, 'svg');

        QrCode::invalider(3);

        $this->assertFileDoesNotExist(QrCode::chemin(3, 'png'));
        $this->assertFileDoesNotExist(QrCode::chemin(3, 'svg'));
    }

    public function test_le_qr_encode_bien_le_lien_du_motif(): void
    {
        QrCode::pour(3, 'svg');

        $svg = (string) file_get_contents(QrCode::chemin(3, 'svg'));

        $this->assertNotSame('', $svg);
        $this->assertStringContainsString('<svg', $svg);
    }
}
```

- [ ] **Step 3: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter QrCodeTest"
```

Attendu : ÉCHEC — classe introuvable.

- [ ] **Step 4: Implémenter**

`src/Don/QrCode.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Don;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Génération côté serveur et mise en cache dans les uploads. Un QR produit en
 * JavaScript ne s'imprime pas et ne se partage pas — or c'est précisément
 * l'usage : statut WhatsApp, projection pendant un culte, flyer.
 */
final class QrCode
{
    public static function pour(int $motifId, string $format = 'png'): string
    {
        $chemin = self::chemin($motifId, $format);

        if (! file_exists($chemin)) {
            self::generer($motifId, $format, $chemin);
        }

        $uploads = wp_upload_dir();

        return $uploads['baseurl'] . '/qr/' . basename($chemin);
    }

    public static function chemin(int $motifId, string $format): string
    {
        $uploads = wp_upload_dir();

        return $uploads['basedir'] . '/qr/motif-' . $motifId . '.' . $format;
    }

    public static function invalider(int $motifId): void
    {
        foreach (['png', 'svg'] as $format) {
            $chemin = self::chemin($motifId, $format);

            if (file_exists($chemin)) {
                unlink($chemin);
            }
        }
    }

    private static function generer(int $motifId, string $format, string $chemin): void
    {
        wp_mkdir_p(dirname($chemin));

        $resultat = Builder::create()
            ->writer($format === 'svg' ? new SvgWriter() : new PngWriter())
            ->data((string) get_permalink($motifId))
            ->size(600)
            ->margin(16)
            ->build();

        $resultat->saveToFile($chemin);
    }
}
```

- [ ] **Step 5: Invalider le cache au changement d'URL**

Dans `src/Fields/MotifPaiementFields.php`, ajouter au bas de `register()` :

```php
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
```

- [ ] **Step 6: Lancer les tests et vérifier en administration**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter QrCodeTest"
```

Puis afficher le QR d'un motif réel et le scanner avec un téléphone : il doit ouvrir `/don/<slug>/`.

- [ ] **Step 7: Commit**

```bash
git add cms/composer.json cms/composer.lock cms/web/app/mu-plugins/prophet-core cms/tests/Unit/Don
git commit -m "feat(don): génération et mise en cache des QR codes de motif"
```

---

### Task 7: Administration des motifs et des dons

**Files:**
- Modify: `src/PostTypes/MotifPaiement.php`, `src/PostTypes/Don.php`
- Create: `src/Don/ExportCsv.php`
- Test: `cms/tests/Unit/Don/ExportCsvTest.php`

**Interfaces:**
- Produces:
  - Colonne « Lien et QR » dans la liste des motifs, avec les deux téléchargements
  - Colonnes motif, montant, statut de paiement, date dans la liste des dons ; filtres par motif et par statut
  - `ExportCsv::lignes(array $dons): array` — en-tête plus une ligne par don
  - `ExportCsv::ACTION = 'prophet_export_dons'`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Don/ExportCsvTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Don;

use ProphetCore\Don\ExportCsv;
use ProphetCore\Tests\TestCase;

final class ExportCsvTest extends TestCase
{
    private function don(): array
    {
        return [
            'date' => '2026-08-04 10:00:00',
            'motif_titre' => 'Dîmes',
            'montant' => 2500,
            'devise' => 'XOF',
            'prenom' => 'Jane', 'nom' => 'Doe',
            'email' => 'jane@example.test', 'telephone' => '+22890000000',
            'statut_paiement' => 'paye',
            'ref' => 'REF123',
        ];
    }

    public function test_l_entete_est_en_francais_et_dans_un_ordre_stable(): void
    {
        $lignes = ExportCsv::lignes([]);

        $this->assertSame(
            ['Date', 'Motif', 'Montant', 'Devise', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Statut', 'Référence'],
            $lignes[0]
        );
    }

    public function test_un_don_produit_une_ligne_dans_le_meme_ordre(): void
    {
        $lignes = ExportCsv::lignes([$this->don()]);

        $this->assertCount(2, $lignes);
        $this->assertSame('Dîmes', $lignes[1][1]);
        $this->assertSame(2500, $lignes[1][2]);
        $this->assertSame('REF123', $lignes[1][9]);
    }

    public function test_le_statut_est_traduit_en_francais(): void
    {
        $lignes = ExportCsv::lignes([$this->don()]);

        $this->assertSame('Réglé', $lignes[1][8]);
    }
}
```

- [ ] **Step 2: Lancer le test, vérifier l'échec, implémenter, vérifier le passage**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter ExportCsvTest"
```

`src/Don/ExportCsv.php` construit les lignes et expose une action `admin_post_prophet_export_dons` qui pousse un `text/csv` avec un BOM UTF-8 — sans lui, Excel ouvre les accents en mojibake, et c'est un trésorier qui ouvrira ce fichier.

- [ ] **Step 3: Ajouter les colonnes et filtres**

Dans `MotifPaiement::register()`, une colonne « Lien et QR » affichant l'URL du motif et deux liens de téléchargement (PNG, SVG) construits avec `QrCode::pour()`.

Dans `Don::register()`, les colonnes `don_motif`, `don_montant`, `don_paiement`, `don_date`, les filtres par motif et par statut de paiement sur `restrict_manage_posts` / `pre_get_posts`, et le total encaissé sur la période visible affiché au-dessus de la liste.

- [ ] **Step 4: Vérifier en administration et commiter**

Créer deux dons par le formulaire, vérifier les colonnes, les filtres, le total, puis l'export CSV ouvert dans un tableur avec les accents corrects. Supprimer les dons de test.

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/Don
git commit -m "feat(don): administration des motifs et des dons, export CSV"
```

---

### Task 8: Page de don

**Files:**
- Create: `cms/web/app/themes/prophet/app/View/Composers/Don.php`, `resources/views/template-don.blade.php`, `resources/views/partials/don-form.blade.php`, `resources/css/sections/_don.css`
- Modify: `resources/css/app.css`

**Interfaces:**
- Consumes: `MotifRepository::actifs()`, `MotifRepository::parSlug()`, `FlashStore::pull()`, `DonSubmitHandler::ACTION/NONCE/HONEYPOT`
- Produces: page `/don/` avec sélecteur de motif, montant selon régime, et lien profond `/don/<slug>/` présélectionnant le motif

- [ ] **Step 1: Écrire le composer**

`app/View/Composers/Don.php` expose `$motifs` (les motifs actifs), `$motifPreselectionne` (résolu depuis l'URL), `$erreurs`, `$valeurs`, `$erreurGlobale` (depuis `FlashStore`, **mémorisés par requête** — `pull()` supprime le transient et le composer sert deux vues, piège déjà payé sur le rendez-vous), et `$actionDon`.

- [ ] **Step 2: Écrire le gabarit et le formulaire**

`template-don.blade.php` porte l'en-tête et inclut `partials.don-form`. Le formulaire poste sur `admin-post.php` avec nonce et pot de miel, comme celui du rendez-vous.

Le bloc montant est piloté par Alpine sur le motif choisi :

```blade
<div x-data="donForm(@js($motifs), @js($motifPreselectionne))">
  <select name="motif" x-model.number="motifId" required>
    <option value="">Choisissez un motif</option>
    @foreach ($motifs as $motif)
      <option value="{{ $motif['id'] }}">{{ $motif['titre'] }}</option>
    @endforeach
  </select>

  <p x-show="motif" x-text="motif?.description"></p>

  <template x-if="motif?.regime === 'fixe'">
    <p>Montant : <strong x-text="`${motif.montant_fixe} ${devise}`"></strong></p>
  </template>

  <template x-if="motif?.regime === 'suggere'">
    <div>
      <template x-for="palier in motif.suggeres" :key="palier">
        <button type="button" x-on:click="montant = palier" x-text="palier"></button>
      </template>
      <input type="number" name="montant" x-model.number="montant" :min="motif.min" :max="motif.max" required>
    </div>
  </template>

  <template x-if="motif?.regime === 'libre'">
    <input type="number" name="montant" x-model.number="montant" :min="motif.min" :max="motif.max" required>
  </template>
</div>
```

En régime `fixe`, aucun champ `montant` n'est soumis — le serveur prend celui du motif de toute façon.

- [ ] **Step 3: Ajouter le composant Alpine**

Dans `resources/js/app.js`, à côté de `rdvForm` :

```js
Alpine.data('donForm', (motifs, preselection) => ({
  motifs,
  motifId: preselection ?? '',
  montant: '',
  get motif() {
    return this.motifs.find((m) => m.id === this.motifId) ?? null
  },
}))
```

- [ ] **Step 4: Styler, construire, vérifier**

`_don.css` suit la procédure de port des sections : enveloppe `.sec-don`, tokens de `tokens.css`, sélecteurs racines composés avec `&`.

```bash
cd cms/web/app/themes/prophet && npm run build && cd -
curl -sSL -o /dev/null -w '%{http_code}\n' http://localhost:8080/don/
curl -sSL http://localhost:8080/don/dimes/ | grep -c 'value="3" selected'
```

- [ ] **Step 5: Commit**

```bash
git add cms/web/app/themes/prophet
git commit -m "feat(don): page de don avec sélecteur de motif et régimes de montant"
```

---

### Task 9: Page de remerciement et vérification du retour

**Files:**
- Create: `resources/views/template-don-merci.blade.php`, `app/View/Composers/DonMerci.php`
- Modify: `resources/css/sections/_don.css`

**Interfaces:**
- Consumes: `DonRepository::parRef()`, `RetourHandler::verifier()`, `PaiementRepository`
- Produces: page `/don/merci/?ref=…` avec les trois états de paiement

- [ ] **Step 1: Écrire le composer**

Comme celui de la confirmation de rendez-vous : si l'URL porte `paymentId`, appeler `RetourHandler::verifier()` avec le `DonPayable` correspondant, **sans jamais lire `paymentStatus`**. Puis exposer `$don`, `$paiement`, `$introuvable`.

- [ ] **Step 2: Écrire le gabarit**

Trois états : reçu (montant et motif rappelés), en attente ou échoué (bouton pour réessayer, pointant sur `InitHandler::ACTION` avec la référence), introuvable (message poli, lien vers `/don/`).

- [ ] **Step 3: Créer la page et vérifier**

```bash
docker compose -f docker-compose.cms.dev.yml exec -T wpcli \
  wp post create --post_type=page --post_title="Merci" --post_name=merci \
    --post_parent=$(docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp post list --post_type=page --name=don --field=ID) \
    --post_status=publish --page_template=template-don-merci.blade.php --porcelain
```

Vérifier les trois états, dont la falsification : `?ref=<ref>&paymentStatus=success` sur un don en attente ne doit rien changer.

- [ ] **Step 4: Commit**

```bash
git add cms/web/app/themes/prophet
git commit -m "feat(don): page de remerciement et vérification serveur du retour"
```

---

### Task 10: Documentation et recette

**Files:**
- Modify: `README.md`
- Create: `docs/superpowers/notes/2026-08-04-recette-dons.md`

- [ ] **Step 1: Documenter dans le README**

Une section « Motifs de paiement et dons » : créer un motif, choisir son régime, récupérer son lien et son QR, où retrouver les dons, comment exporter le CSV. Rappeler la distinction **mode** / **motif**.

- [ ] **Step 2: Dérouler la recette**

| # | Vérification | Attendu |
|---|---|---|
| 1 | `/don/` sans motif actif | message clair, pas d'erreur |
| 2 | Trois motifs actifs | tous listés dans le sélecteur |
| 3 | Lien profond `/don/dimes/` | motif présélectionné |
| 4 | Lien profond d'un motif désactivé | message, liste des motifs disponibles |
| 5 | Régime libre | saisie acceptée dans les bornes |
| 6 | Montant sous le minimum | refusé, message serveur |
| 7 | Montant au-dessus du maximum | refusé |
| 8 | Montant non entier, négatif, `1e5` | refusés |
| 9 | Régime fixe, montant falsifié dans la requête | montant du motif retenu |
| 10 | Régime suggéré, valeur hors palier dans les bornes | acceptée |
| 11 | Don valide | enregistré puis redirection Moneroo |
| 12 | Moneroo injoignable | don enregistré, page de remerciement avec message |
| 13 | Retour avec `paymentStatus` falsifié | statut inchangé |
| 14 | Webhook signé sur un don | don marqué reçu |
| 15 | Webhook rejoué | aucun second effet |
| 16 | QR scanné au téléphone | ouvre le bon motif |
| 17 | Identifiant du motif modifié | QR régénéré vers la nouvelle URL |
| 18 | Deux dons de deux emails depuis la même IP | tous deux acceptés |
| 19 | Deux dons du même email en rafale | second refusé |
| 20 | Export CSV | accents corrects dans un tableur |
| 21 | Journaux | aucune donnée personnelle, aucune clé |
| 22 | Suite PHPUnit | verte |

Le point 18 est celui qui prouve que la limitation ne bloque pas une assemblée.

- [ ] **Step 3: Commit**

```bash
git add README.md docs/superpowers/notes
git commit -m "docs(don): mode d'emploi des motifs de paiement et recette"
```

---

## Notes de relecture

1. **La tâche 1 est la seule risquée du plan.** Son critère de réussite est négatif : aucune assertion existante ne bouge. Si l'implémenteur doit en modifier une, c'est un signal, pas un détail d'intendance.
2. **`register_post_status()` exige `protected => true`** — sans quoi les statuts n'apparaissent jamais dans la liste d'administration. Leçon déjà payée sur le rendez-vous, rappelée en tâche 4.
3. **Le composer de la page de don doit mémoriser le flash par requête** : `FlashStore::pull()` supprime le transient et un composer servant deux vues consomme les erreurs au premier rendu. Piège déjà payé sur le formulaire de rendez-vous.
4. **La limitation de débit est volontairement différente de celle du rendez-vous** : couple (IP, email) et fenêtre courte. Le point 18 de la recette existe pour le prouver.
5. **L'API d'`endroid/qr-code` change entre majeures.** La tâche 6 impose de vérifier la version installée avant d'écrire le code plutôt que de faire confiance au fragment.
