# Migration Bedrock + Sage — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remplacer l'application Nuxt 3 par un site WordPress Bedrock + Sage au design identique, dont tout le contenu est éditable depuis l'administration.

**Architecture:** Bedrock fournit le socle WordPress géré par Composer ; un mu-plugin `prophet-core` porte tout le métier (types de contenu, rendez-vous, intégrations, emails) ; le thème Sage ne fait que du rendu Blade. Le CSS des composants Vue est copié, pas réécrit, avec un scoping par section qui remplace `<style scoped>`.

**Tech Stack:** PHP 8.2, WordPress 6.x, Bedrock, Sage 11 (Acorn 5, Blade, Vite), Carbon Fields 3, MySQL 8, Alpine.js 3, flatpickr, PHPUnit 10 + Brain Monkey, Docker (Caddy + PHP-FPM + MySQL).

**Spec:** `docs/superpowers/specs/2026-07-30-migration-bedrock-sage-design.md`

## Global Constraints

- PHP 8.2 minimum ; extensions requises : `mysqli`, `intl`, `gd`, `zip`, `opcache`.
- Tout le code métier vit dans `cms/web/app/mu-plugins/prophet-core/`, namespace `ProphetCore\`. Le thème ne contient aucune logique métier.
- Aucune dépendance Vue, Radix, vee-validate, zod, Tailwind dans le résultat final.
- Le design doit être identique au Nuxt actuel. Le CSS est **copié** depuis les `<style scoped>`, jamais réécrit en classes utilitaires.
- Chaque fichier CSS de section est enveloppé dans son sélecteur racine (`.sec-<nom> { … }`) ; la partial Blade porte cette classe sur son élément racine.
- Toute chaîne visible par l'utilisateur est en français, reprise mot pour mot des composants Vue existants.
- Les dates affichées passent par `ProphetCore\Support\Date::formatFr()` (IntlDateFormatter, locale `fr_FR`), jamais par `date_i18n`.
- Toute sortie est échappée (`esc_html`, `esc_attr`, `esc_url`) ; toute entrée est assainie avant écriture.
- Le Nuxt reste intact et lançable jusqu'à la tâche 23.
- Commits en français, un par tâche minimum.

## Structure des fichiers

```
cms/
├── composer.json                     # deps + autoload PSR-4 ProphetCore\
├── phpunit.xml
├── .env / .env.example
├── config/application.php
├── tests/
│   ├── bootstrap.php
│   ├── TestCase.php
│   └── Unit/…                        # miroir de src/
└── web/
    ├── wp/                           # cœur WP (Composer)
    └── app/
        ├── mu-plugins/prophet-core/
        │   ├── prophet-core.php      # bootstrap, appelle les register()
        │   └── src/
        │       ├── Support/Date.php
        │       ├── Options.php
        │       ├── PostTypes/{Service,Temoignage,Photo,RendezVous}.php
        │       ├── Fields/{ServiceFields,TemoignageFields,PhotoFields,
        │       │           RendezVousFields,ContenuOptions,RdvOptions}.php
        │       ├── Services/{GoogleCalendar,Youtube,Whatsapp,Mailer,
        │       │            SmtpConfigurator}.php
        │       ├── Rdv/{Validator,ValidationResult,Repository,
        │       │       FlashStore,SubmitHandler}.php
        │       └── Cli/SeedCommand.php
        └── themes/prophet/           # Sage 11
            ├── app/setup.php
            ├── app/View/Composers/{Services,Testimonials,Gallery,Events,
            │                       Videos,Content,Rdv,Confirmation}.php
            └── resources/
                ├── views/
                │   ├── layouts/app.blade.php
                │   ├── front-page.blade.php
                │   ├── template-rdv.blade.php
                │   ├── template-confirmation.blade.php
                │   ├── components/icon.blade.php
                │   ├── partials/{navbar,footer,floating-whatsapp,
                │   │             hero-left,testimonial-card,rdv-form}.blade.php
                │   ├── sections/{hero,stats,about,services,events,videos,
                │   │             gallery,testimonials,cta}.blade.php
                │   └── emails/{client,prophet}.blade.php
                ├── css/
                │   ├── app.css              # @import des fichiers ci-dessous
                │   ├── tokens.css           # copie de assets/css/main.css
                │   └── sections/_*.css      # une par section
                ├── js/app.js                # Alpine + flatpickr + observer
                └── images/icons/*.svg       # lucide-static
docker/
├── php/Dockerfile
├── Caddyfile.cms
└── Caddyfile.cms.dev
docker-compose.cms.dev.yml
docker-compose.cms.yml
```

## Procédure de port d'une section

Cette procédure s'applique aux tâches 14 à 19. Chaque tâche la suit intégralement avec ses paramètres propres (fichier source, fichier cible, classe de scope, source de données).

1. Ouvrir le composant Vue source et repérer ses trois blocs : `<script setup>`, `<template>`, `<style scoped>`.
2. Créer `resources/css/sections/_<nom>.css` : coller le contenu du `<style scoped>` **sans le modifier**, puis l'envelopper dans `.sec-<nom> { … }`. Les `@keyframes` et les règles `:root` sortent de l'enveloppe (elles sont globales) ; si le composant en déclare, les déplacer en tête de fichier, hors du bloc.

   **Piège du nesting, découvert en tâche 14.** Un sélecteur imbriqué nu compile en
   combinateur de **descendance**. La règle `.sec-hero { .hero { position: fixed } }`
   devient donc `.sec-hero .hero`, qui ne s'applique **jamais** à l'élément racine —
   celui qui porte à la fois `sec-hero` et `hero`. Le style disparaît en silence.
   Toute règle visant l'élément racine doit être composée avec `&` :
   `.sec-hero { &.hero { position: fixed } }`. Concrètement : repérer dans le
   `<style scoped>` les sélecteurs qui ciblent la racine du composant et leur
   préfixer `&`. Les sélecteurs visant des descendants restent tels quels.
3. Ajouter `@import "./sections/_<nom>.css";` dans `resources/css/app.css`.
4. Créer la vue Blade : transposer le `<template>` en HTML, élément racine portant `class="sec-<nom>"` en plus de ses classes d'origine.
5. Transpositions systématiques :
   - `:attr="expr"` → `attr="{{ $expr }}"` ; `{{ expr }}` → `{{ $expr }}` (échappé par défaut).
   - `v-for="x in liste"` → `@foreach ($liste as $x)` ; `v-if` → `@if` ; `v-else` → `@else`.
   - `<Component :icon="X" />` de lucide → `<x-icon name="x" />`.
   - `@click`, `v-model`, `ref()` → attributs Alpine (`x-data`, `x-on:click`, `x-model`, `x-show`).
   - `NuxtLink to="/rdv"` → `<a href="{{ home_url('/rdv') }}">`.
   - Toute image de `public/images/` est copiée dans `resources/images/`, puis
     référencée par **`@asset('resources/images/<nom>')`** — le chemin complet, pas
     `@asset('images/<nom>')`. La clé du manifeste Vite est le chemin depuis la
     racine du thème ; la forme raccourcie ne résout rien et l'image manque en
     silence. Vite n'inclut par ailleurs une image au build que si quelque chose la
     référence : `app.js` porte pour cela un `import.meta.glob` sur
     `resources/images/**` (posé en tâche 15). Toute image ajoutée ensuite est
     couverte par ce glob, sans autre intervention.
6. Les données viennent d'un View Composer, jamais d'une requête dans la vue.
7. Vérification. **Le serveur de développement Nuxt ne démarre pas dans cet
   environnement** (`nitro` échoue sur `spawn EBADF`, sandbox ou non — constaté en
   tâche 14 puis reconfirmé). La comparaison côte à côte est donc impossible, et il
   ne faut pas prétendre l'avoir faite. À la place, trois contrôles qui prouvent
   réellement quelque chose :

   a. **CSS** : `diff` entre le `<style scoped>` source et le fichier de section.
      Seuls l'enveloppe `.sec-<nom>` et les `&` de composition doivent différer.
   b. **Structure** : comparer le `<template>` Vue et la vue Blade élément par
      élément — même ordre, mêmes classes, mêmes attributs. Le rendu est
      entièrement déterminé par le CSS copié et ces classes : si les deux
      correspondent, l'affichage correspond.
   c. **Rendu réel** : charger la page WordPress dans le navigateur et vérifier que
      la section s'affiche, que ses règles s'appliquent (inspecter un élément
      racine, pas seulement la présence du markup — c'est ainsi qu'on attrape le
      piège du nesting ci-dessus) et que ses comportements Alpine fonctionnent.

   La validation visuelle finale contre le Nuxt appartient à la recette de la
   tâche 23, sur un environnement où les deux sites peuvent tourner ensemble.

**Styles globaux de page.** L'audit de la tâche 4 ne scanne que les blocs
`<style scoped>`. Or `pages/index.vue` et `pages/rdv.vue` portent chacun un bloc
`<style>` **non scopé** (`.site`, `.rdv-page`, `.rdv-back`, `.rdv-layout*`) qui
échappe donc à l'audit comme au scoping par section. Ces règles sont globales par
nature : elles vont dans `resources/css/app.css` ou dans un fichier
`sections/_page-<nom>.css` importé sans enveloppe `.sec-*`, et non dans le fichier
d'une section. La tâche 15 traite celui de `pages/index.vue`, la tâche 19 celui de
`pages/rdv.vue`.

---

## Phase A — Socle

### Task 1: Bedrock + Docker de développement

**Files:**
- Create: `cms/` (via `composer create-project`), `cms/.env`, `cms/.env.example`
- Create: `docker/php/Dockerfile`, `docker/Caddyfile.cms.dev`, `docker-compose.cms.dev.yml`
- Create: `cms/scripts/install-wp.sh`

**Interfaces:**
- Consumes: rien
- Produces: un WordPress installé accessible sur `http://localhost:8080`, base MySQL `prophet`, conteneurs `prophet_cms_app`, `prophet_cms_caddy`, `prophet_cms_db`, `prophet_cms_wpcli`.

- [ ] **Step 1: Créer le squelette Bedrock**

```bash
cd /Users/nehiyr/Labs/prophet-rdv
composer create-project roots/bedrock cms --no-install
```

Si `composer` n'est pas disponible localement, l'exécuter dans un conteneur :

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 create-project roots/bedrock cms --no-install
```

- [ ] **Step 2: Écrire le Dockerfile PHP**

Créer `docker/php/Dockerfile` :

```dockerfile
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
      icu-dev icu-data-full libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev \
      $PHPIZE_DEPS \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" mysqli intl gd zip opcache \
 && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /srv
```

L'extension `intl` est obligatoire : `Support\Date::formatFr()` en dépend (tâche 5).
`icu-data-full` l'est tout autant : l'image Alpine n'embarque sinon que les données
ICU anglaises, et `IntlDateFormatter('fr_FR', …)` retombe **silencieusement** sur
l'anglais, sans erreur ni avertissement. Vérifier la locale, pas seulement la
présence de l'extension.

- [ ] **Step 3: Écrire le Caddyfile de développement**

Créer `docker/Caddyfile.cms.dev` :

```
:8080 {
	root * /srv/web
	encode zstd gzip
	php_fastcgi app:9000
	file_server
}
```

- [ ] **Step 4: Écrire le compose de développement**

Créer `docker-compose.cms.dev.yml`. Le `name:` en tête est obligatoire : sans lui,
Compose dérive le nom de projet du répertoire, cette pile partage son identité avec
le `docker-compose.yml` du Nuxt — dont les services s'appellent aussi `caddy` et
`app` — et un `docker compose up` recrée les conteneurs de l'autre pile.

```yaml
name: prophet_cms_dev

services:
  caddy:
    image: caddy:2-alpine
    container_name: prophet_cms_caddy
    ports:
      - "8080:8080"
    volumes:
      - ./docker/Caddyfile.cms.dev:/etc/caddy/Caddyfile:ro
      - ./cms:/srv
    depends_on:
      - app
    networks: [cms_net]

  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: prophet_cms_app
    env_file: ./cms/.env
    volumes:
      - ./cms:/srv
    depends_on:
      db:
        condition: service_healthy
    networks: [cms_net]

  db:
    image: mysql:8
    container_name: prophet_cms_db
    environment:
      MYSQL_DATABASE: prophet
      MYSQL_USER: prophet
      MYSQL_PASSWORD: prophet
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - cms_mysql:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-uroot", "-proot"]
      interval: 5s
      timeout: 5s
      retries: 20
    networks: [cms_net]

  wpcli:
    image: wordpress:cli-php8.2
    container_name: prophet_cms_wpcli
    user: "33:33"
    env_file: ./cms/.env
    volumes:
      - ./cms:/srv
    working_dir: /srv/web/wp
    entrypoint: ["tail", "-f", "/dev/null"]
    depends_on:
      - app
    networks: [cms_net]

volumes:
  cms_mysql:

networks:
  cms_net:
    driver: bridge
```

- [ ] **Step 5: Configurer l'environnement**

Écrire `cms/.env.example` (et le copier en `cms/.env` en remplissant les secrets réels) :

```
WP_ENV=development
WP_HOME=http://localhost:8080
WP_SITEURL=${WP_HOME}/wp

DB_NAME=prophet
DB_USER=prophet
DB_PASSWORD=prophet
DB_HOST=db

AUTH_KEY='generateme'
SECURE_AUTH_KEY='generateme'
LOGGED_IN_KEY='generateme'
NONCE_KEY='generateme'
AUTH_SALT='generateme'
SECURE_AUTH_SALT='generateme'
LOGGED_IN_SALT='generateme'
NONCE_SALT='generateme'

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=false
SMTP_USER=
SMTP_PASS=

PROPHET_EMAIL=
PROPHET_PHONE_1=+22897169090
PROPHET_PHONE_2=+2348119265483

GOOGLE_API_KEY=
GOOGLE_CALENDAR_ID=
YOUTUBE_API_KEY=
YOUTUBE_CHANNEL_ID=
```

Générer les salts : `curl https://roots.io/salts.html` ou `wp config shuffle-salts`.

- [ ] **Step 6: Installer les dépendances et démarrer**

```bash
docker compose -f docker-compose.cms.dev.yml build
docker compose -f docker-compose.cms.dev.yml run --rm app composer install -d /srv
docker compose -f docker-compose.cms.dev.yml up -d
```

- [ ] **Step 7: Installer WordPress**

Créer `cms/scripts/install-wp.sh` :

```bash
#!/usr/bin/env bash
set -euo pipefail

docker compose -f docker-compose.cms.dev.yml exec -T wpcli \
  wp core install \
    --url="http://localhost:8080" \
    --title="Prophète Jeremiah Nahoum" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@example.test \
    --skip-email

docker compose -f docker-compose.cms.dev.yml exec -T wpcli \
  wp rewrite structure '/%postname%/'
```

Puis :

```bash
chmod +x cms/scripts/install-wp.sh && ./cms/scripts/install-wp.sh
```

- [ ] **Step 8: Vérifier**

```bash
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8080/
curl -sS http://localhost:8080/ | grep -c 'Prophète Jeremiah Nahoum'
```

Attendu : `200`, puis un compte ≥ 1.

- [ ] **Step 9: Ignorer les fichiers non versionnables**

Ajouter à `.gitignore` :

```
cms/.env
cms/vendor/
cms/web/wp/
cms/web/app/uploads/
cms/web/app/plugins/
cms/web/app/mu-plugins/bedrock-autoloader.php
cms/web/app/themes/prophet/node_modules/
cms/web/app/themes/prophet/public/
cms/web/app/themes/prophet/vendor/
```

- [ ] **Step 10: Commit**

```bash
git add .gitignore cms docker docker-compose.cms.dev.yml
git commit -m "feat(cms): socle Bedrock et environnement Docker de développement"
```

---

### Task 2: Thème Sage installé et sans Tailwind

**Files:**
- Create: `cms/web/app/themes/prophet/` (via `composer create-project roots/sage`)
- Modify: `cms/web/app/themes/prophet/vite.config.js`
- Modify: `cms/web/app/themes/prophet/resources/css/app.css`
- Modify: `cms/web/app/themes/prophet/package.json`
- Modify: `cms/web/app/themes/prophet/resources/views/layouts/app.blade.php`

**Interfaces:**
- Consumes: environnement de la tâche 1
- Produits: thème `prophet` actif, `@vite` fonctionnel, aucune classe Tailwind disponible. Les tâches 14+ ajoutent leurs `@import` dans `resources/css/app.css`.

- [ ] **Step 1: Créer le thème**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  composer create-project roots/sage /srv/web/app/themes/prophet
```

- [ ] **Step 2: Retirer Tailwind**

Le design du site n'utilise pas Tailwind (il ne couvrait que `components/ui/**`). Supprimer le plugin de `vite.config.js` :

```js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import { wordpressPlugin, wordpressThemeJson } from '@roots/vite-plugin'

export default defineConfig({
  base: '/app/themes/prophet/public/build/',
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    wordpressPlugin(),
    wordpressThemeJson({
      disableTailwindColors: true,
      disableTailwindFonts: true,
      disableTailwindFontSizes: true,
    }),
  ],
  resolve: {
    alias: {
      '@scripts': '/resources/js',
      '@styles': '/resources/css',
      '@fonts': '/resources/fonts',
      '@images': '/resources/images',
    },
  },
})
```

Si `wordpressThemeJson` refuse les options ci-dessus dans la version installée, le retirer purement et simplement de la liste des plugins : `theme.json` n'est pas utilisé par ce design.

- [ ] **Step 3: Vider app.css**

Remplacer `resources/css/app.css` par :

```css
/* Les imports de sections sont ajoutés au fil des tâches 14 à 19. */
```

- [ ] **Step 4: Désinstaller les paquets Tailwind**

```bash
cd cms/web/app/themes/prophet
npm pkg delete devDependencies.tailwindcss devDependencies.@tailwindcss/vite
rm -f tailwind.config.js
npm install
```

- [ ] **Step 5: Réduire le layout au strict nécessaire**

Remplacer `resources/views/layouts/app.blade.php` :

```blade
<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="{{ get_bloginfo('charset') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php(wp_head())
  </head>
  <body @php(body_class())>
    @php(do_action('get_header'))
    <main id="main">
      @yield('content')
    </main>
    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>
```

La directive `@vite` est ce qui charge la feuille de styles et le JavaScript du thème :
sans elle, aucune page ne reçoit le moindre style. Les deux entrées correspondent
exactement au tableau `input` de `vite.config.js`.

Les partials `navbar` et `footer` sont ajoutées à la tâche 14.

- [ ] **Step 6: Construire et activer**

```bash
cd cms/web/app/themes/prophet && npm run build && cd -
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp theme activate prophet
```

- [ ] **Step 7: Vérifier**

```bash
curl -sS http://localhost:8080/ | grep -c '<main id="main">'
```

Attendu : `1`. Vérifier aussi qu'aucune classe Tailwind n'est générée :

```bash
grep -rc 'tailwind' cms/web/app/themes/prophet/resources/css/app.css
```

Attendu : `0`.

- [ ] **Step 8: Commit**

```bash
git add cms/web/app/themes/prophet
git commit -m "feat(theme): thème Sage installé, Tailwind retiré"
```

---

### Task 3: Harnais de tests du mu-plugin

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/prophet-core.php`
- Create: `cms/web/app/mu-plugins/prophet-core/src/Support/Version.php`
- Create: `cms/tests/bootstrap.php`, `cms/tests/TestCase.php`, `cms/tests/Unit/Support/VersionTest.php`
- Create: `cms/phpunit.xml`
- Modify: `cms/composer.json`

**Interfaces:**
- Consumes: environnement de la tâche 1
- Produces: `ProphetCore\` autochargé depuis `web/app/mu-plugins/prophet-core/src/` ; classe de base `ProphetCore\Tests\TestCase` (Brain Monkey monté dans `setUp`/`tearDown`) dont héritent tous les tests des tâches suivantes ; commande `composer test`.

- [ ] **Step 1: Déclarer l'autoload et les dépendances de test**

Dans `cms/composer.json`, ajouter :

```json
{
  "require": {
    "htmlburger/carbon-fields": "^3.6"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.5",
    "brain/monkey": "^2.6",
    "php-stubs/wordpress-stubs": "^6.4"
  },
  "autoload": {
    "psr-4": {
      "ProphetCore\\": "web/app/mu-plugins/prophet-core/src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "ProphetCore\\Tests\\": "tests/"
    }
  },
  "scripts": {
    "test": "phpunit"
  }
}
```

Puis :

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app composer update -d /srv
```

- [ ] **Step 2: Écrire le bootstrap et la classe de base**

`cms/tests/bootstrap.php` :

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
```

`cms/tests/TestCase.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
```

`cms/phpunit.xml` :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         failOnWarning="true"
         beStrictAboutOutputDuringTests="true">
  <testsuites>
    <testsuite name="unit">
      <directory>tests/Unit</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

- [ ] **Step 3: Écrire le test qui échoue**

`cms/tests/Unit/Support/VersionTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Support;

use ProphetCore\Support\Version;
use ProphetCore\Tests\TestCase;

final class VersionTest extends TestCase
{
    public function test_la_version_du_plugin_est_exposee(): void
    {
        $this->assertSame('1.0.0', Version::current());
    }
}
```

- [ ] **Step 4: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter VersionTest"
```

Attendu : ÉCHEC — `Class "ProphetCore\Support\Version" not found`.

- [ ] **Step 5: Écrire le minimum**

`cms/web/app/mu-plugins/prophet-core/src/Support/Version.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Support;

final class Version
{
    public static function current(): string
    {
        return '1.0.0';
    }
}
```

- [ ] **Step 6: Écrire le point d'entrée du mu-plugin**

`cms/web/app/mu-plugins/prophet-core/prophet-core.php` :

```php
<?php

declare(strict_types=1);

/**
 * Plugin Name: Prophet Core
 * Description: Types de contenu, rendez-vous et intégrations du site du Prophète Jeremiah Nahoum.
 * Version: 1.0.0
 */

// Les register() des tâches suivantes viennent ici.
```

Bedrock charge automatiquement les mu-plugins imbriqués via son autoloader.

- [ ] **Step 7: Lancer le test et vérifier qu'il passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit"
```

Attendu : `OK (1 test, 1 assertion)`.

- [ ] **Step 8: Commit**

```bash
git add cms/composer.json cms/composer.lock cms/phpunit.xml cms/tests \
        cms/web/app/mu-plugins/prophet-core
git commit -m "test(cms): harnais PHPUnit et squelette du mu-plugin prophet-core"
```

---

## Phase B — Fondations du design

### Task 4: Audit des collisions CSS et port des tokens

**Files:**
- Create: `scripts/audit-css-classes.mjs`
- Create: `docs/superpowers/notes/2026-07-30-audit-collisions-css.md`
- Create: `cms/web/app/themes/prophet/resources/css/tokens.css`
- Modify: `cms/web/app/themes/prophet/resources/css/app.css`
- Modify: `cms/web/app/themes/prophet/app/setup.php`

**Interfaces:**
- Consumes: thème de la tâche 2
- Produces: `tokens.css` chargé sur toutes les pages ; les polices Google enregistrées ; le rapport d'audit qui conditionne les tâches 14 à 19.

- [ ] **Step 1: Écrire le script d'audit**

`scripts/audit-css-classes.mjs` :

```js
#!/usr/bin/env node
// Relève les noms de classes déclarés dans les <style scoped> des composants Vue
// et signale ceux utilisés par plus d'un composant.
import { readFileSync, readdirSync } from 'node:fs'
import { join } from 'node:path'

const SOURCES = ['components', 'pages']
const files = SOURCES.flatMap((dir) =>
  readdirSync(dir, { recursive: true })
    .filter((f) => f.endsWith('.vue'))
    .map((f) => join(dir, f))
)

const byClass = new Map()

for (const file of files) {
  const src = readFileSync(file, 'utf8')
  const style = src.match(/<style scoped>([\s\S]*?)<\/style>/)
  if (!style) continue
  const classes = new Set(
    [...style[1].matchAll(/\.(-?[_a-zA-Z][\w-]*)/g)].map((m) => m[1])
  )
  for (const cls of classes) {
    if (!byClass.has(cls)) byClass.set(cls, [])
    byClass.get(cls).push(file)
  }
}

const collisions = [...byClass.entries()]
  .filter(([, files]) => files.length > 1)
  .sort((a, b) => b[1].length - a[1].length)

console.log(`${files.length} composants analysés, ${byClass.size} classes distinctes.`)
console.log(`${collisions.length} classes utilisées par plusieurs composants :\n`)
for (const [cls, owners] of collisions) {
  console.log(`.${cls} (${owners.length})`)
  for (const owner of owners) console.log(`    ${owner}`)
}
```

- [ ] **Step 2: Lancer l'audit et consigner le résultat**

```bash
node scripts/audit-css-classes.mjs | tee /tmp/audit-css.txt
```

Créer `docs/superpowers/notes/2026-07-30-audit-collisions-css.md` avec la sortie du script, puis, sous chaque classe en collision, noter si les règles diffèrent entre composants. Le scoping par section (`.sec-<nom>`) les neutralise toutes ; l'audit sert à repérer le cas dangereux : **une règle d'un composant qui stylait un élément rendu par un autre composant**. Si un tel cas apparaît, le documenter explicitement — il ne pourra pas être porté par simple copie.

- [ ] **Step 3: Porter les tokens**

Copier `assets/css/main.css` vers `cms/web/app/themes/prophet/resources/css/tokens.css` **sans modification**, à trois retraits près :

- supprimer les deux lignes `@tailwind base;` et `@tailwind utilities;` ;
- supprimer le bloc `[data-radix-popper-content-wrapper] > * { … }` (Radix disparaît) ;
- conserver tout le reste tel quel, y compris `:root`, le reset, `.section-ornament`, les `@keyframes`, la scrollbar et `.sr-only`.

```bash
cp assets/css/main.css cms/web/app/themes/prophet/resources/css/tokens.css
```

puis appliquer les trois retraits ci-dessus dans le fichier copié.

- [ ] **Step 4: Importer les tokens**

`cms/web/app/themes/prophet/resources/css/app.css` :

```css
@import "./tokens.css";

/* Les imports de sections sont ajoutés au fil des tâches 14 à 19. */
```

- [ ] **Step 5: Enregistrer les polices**

Dans `cms/web/app/themes/prophet/app/setup.php`, ajouter au bloc `wp_enqueue_scripts` existant :

```php
add_action('wp_enqueue_scripts', function () {
    add_filter('style_loader_tag', function (string $tag, string $handle): string {
        if ($handle !== 'prophet-fonts') {
            return $tag;
        }

        // Chargement non bloquant, identique à nuxt.config.ts:57-66.
        return str_replace(
            "media='all'",
            "media='print' onload=\"this.media='all'\"",
            $tag
        );
    }, 10, 2);

    wp_enqueue_style(
        'prophet-fonts',
        'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;900'
        . '&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600'
        . '&family=JetBrains+Mono:wght@400;500;600'
        . '&family=Inter:wght@400;500;600&display=swap',
        [],
        null
    );
}, 90);

add_action('wp_head', function () {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}, 1);
```

- [ ] **Step 6: Vérifier**

```bash
cd cms/web/app/themes/prophet && npm run build && cd -
curl -sS http://localhost:8080/ | grep -c 'fonts.googleapis.com'
```

Attendu : ≥ 1. Ouvrir ensuite `http://localhost:8080/` dans un navigateur et vérifier dans l'inspecteur que `getComputedStyle(document.documentElement).getPropertyValue('--gold')` vaut ` #C8921C`.

- [ ] **Step 7: Commit**

```bash
git add scripts/audit-css-classes.mjs docs/superpowers/notes \
        cms/web/app/themes/prophet/resources/css \
        cms/web/app/themes/prophet/app/setup.php
git commit -m "feat(theme): tokens de design portés et polices enregistrées"
```

---
## Phase C — Métier

### Task 5: Formatage des dates et lien WhatsApp

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Support/Date.php`
- Create: `cms/web/app/mu-plugins/prophet-core/src/Services/Whatsapp.php`
- Create: `cms/tests/Unit/Support/DateTest.php`, `cms/tests/Unit/Services/WhatsappTest.php`

**Interfaces:**
- Consumes: `ProphetCore\Tests\TestCase` (tâche 3)
- Produces:
  - `Date::formatFr(\DateTimeImmutable $d): string` — « jeudi 30 juillet 2026 »
  - `Date::monthAbbrFr(int $monthIndexZeroBased): string` — `Jan`…`Déc`
  - `Date::formatTime(\DateTimeImmutable $d): string` — `20h00`, `09h30`
  - `Whatsapp::url(string $phone, string $message): string`
  - `Whatsapp::confirmationMessage(array $rdv): string`
  - `Whatsapp::confirmationUrl(string $phone, array $rdv): string`
  - Forme de `$rdv` attendue partout : `['nom','prenom','email','telephone','pays','date' => \DateTimeImmutable,'heure','type_consultation','mode_paiement','message']`

- [ ] **Step 1: Écrire les tests qui échouent**

`cms/tests/Unit/Support/DateTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Support;

use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\Support\Date;
use ProphetCore\Tests\TestCase;

final class DateTest extends TestCase
{
    public function test_format_fr_reproduit_intl_datetimeformat_du_front(): void
    {
        $date = new DateTimeImmutable('2026-07-30 10:00:00', new DateTimeZone('UTC'));

        $this->assertSame('jeudi 30 juillet 2026', Date::formatFr($date));
    }

    public function test_format_fr_gere_un_mois_accentue(): void
    {
        $date = new DateTimeImmutable('2026-02-01 10:00:00', new DateTimeZone('UTC'));

        $this->assertSame('dimanche 1 février 2026', Date::formatFr($date));
    }

    public function test_mois_abrege_suit_la_table_du_bridge(): void
    {
        $this->assertSame('Jan', Date::monthAbbrFr(0));
        $this->assertSame('Fév', Date::monthAbbrFr(1));
        $this->assertSame('Juil', Date::monthAbbrFr(6));
        $this->assertSame('Déc', Date::monthAbbrFr(11));
    }

    public function test_heure_pleine_et_heure_avec_minutes(): void
    {
        $tz = new DateTimeZone('UTC');

        $this->assertSame('20h00', Date::formatTime(new DateTimeImmutable('2026-04-18 20:00:00', $tz)));
        $this->assertSame('09h30', Date::formatTime(new DateTimeImmutable('2026-04-18 09:30:00', $tz)));
    }
}
```

`cms/tests/Unit/Services/WhatsappTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Services;

use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\Services\Whatsapp;
use ProphetCore\Tests\TestCase;

final class WhatsappTest extends TestCase
{
    private function rdv(): array
    {
        return [
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane@example.test',
            'telephone' => '+22890000000',
            'pays' => 'Togo',
            'date' => new DateTimeImmutable('2026-07-30 10:00:00', new DateTimeZone('UTC')),
            'heure' => '10h00',
            'type_consultation' => 'Mariage',
            'mode_paiement' => 'mobile_money',
            'message' => '',
        ];
    }

    public function test_le_numero_est_nettoye_et_le_message_encode(): void
    {
        $url = Whatsapp::url('+228 97 16 90 90', 'Bonjour à vous');

        $this->assertSame('https://wa.me/+22897169090?text=Bonjour%20%C3%A0%20vous', $url);
    }

    public function test_le_message_reprend_les_champs_du_rendez_vous(): void
    {
        $message = Whatsapp::confirmationMessage($this->rdv());

        $this->assertStringContainsString('👤 Nom : Jane Doe', $message);
        $this->assertStringContainsString('🔮 Consultation : Mariage', $message);
        $this->assertStringContainsString('📅 Date souhaitée : jeudi 30 juillet 2026', $message);
        $this->assertStringContainsString('⏰ Heure : 10h00', $message);
        $this->assertStringContainsString('🌍 Pays : Togo', $message);
        $this->assertStringContainsString('📞 Contact : +22890000000', $message);
        $this->assertStringContainsString('Que Dieu vous bénisse.', $message);
    }

    public function test_l_url_de_confirmation_combine_numero_et_message(): void
    {
        $url = Whatsapp::confirmationUrl('+22897169090', $this->rdv());

        $this->assertStringStartsWith('https://wa.me/+22897169090?text=', $url);
        $this->assertStringContainsString(rawurlencode('Jane Doe'), $url);
    }
}
```

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter 'DateTest|WhatsappTest'"
```

Attendu : ÉCHEC — classes `Date` et `Whatsapp` introuvables.

- [ ] **Step 3: Implémenter `Date`**

`cms/web/app/mu-plugins/prophet-core/src/Support/Date.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Support;

use DateTimeImmutable;
use IntlDateFormatter;

final class Date
{
    /** Table reprise de api-bridge/src/services/calendar.ts:6-9. */
    private const MOIS_ABREGES = [
        'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin',
        'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc',
    ];

    /**
     * Reproduit Intl.DateTimeFormat('fr-FR', {weekday, year, month, day})
     * utilisé par lib/utils.ts:13-20.
     */
    public static function formatFr(DateTimeImmutable $date): string
    {
        $formatter = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $date->getTimezone(),
            IntlDateFormatter::GREGORIAN,
            'EEEE d MMMM y'
        );

        return $formatter->format($date);
    }

    public static function monthAbbrFr(int $monthIndexZeroBased): string
    {
        return self::MOIS_ABREGES[$monthIndexZeroBased];
    }

    public static function formatTime(DateTimeImmutable $date): string
    {
        return $date->format('H') . 'h' . $date->format('i');
    }
}
```

- [ ] **Step 4: Implémenter `Whatsapp`**

`cms/web/app/mu-plugins/prophet-core/src/Services/Whatsapp.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Services;

use ProphetCore\Support\Date;

final class Whatsapp
{
    /** Reproduit lib/utils.ts:23-26. */
    public static function url(string $phone, string $message): string
    {
        $cleaned = preg_replace('/[^+\d]/', '', $phone) ?? '';

        return 'https://wa.me/' . $cleaned . '?text=' . rawurlencode($message);
    }

    /** Reproduit server/utils/whatsapp.ts:20-38. */
    public static function confirmationMessage(array $rdv): string
    {
        $dateFr = Date::formatFr($rdv['date']);

        return <<<TXT
        Bonjour Prophète Jeremiah Nahoum,

        Je viens de soumettre une demande de rendez-vous sur votre site :

        👤 Nom : {$rdv['prenom']} {$rdv['nom']}
        🔮 Consultation : {$rdv['type_consultation']}
        📅 Date souhaitée : {$dateFr}
        ⏰ Heure : {$rdv['heure']}
        🌍 Pays : {$rdv['pays']}
        📞 Contact : {$rdv['telephone']}

        Je confirme ma demande et attends votre retour.

        Que Dieu vous bénisse.
        TXT;
    }

    public static function confirmationUrl(string $phone, array $rdv): string
    {
        return self::url($phone, self::confirmationMessage($rdv));
    }
}
```

- [ ] **Step 5: Lancer les tests et vérifier qu'ils passent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter 'DateTest|WhatsappTest'"
```

Attendu : PASS, 7 tests.

Si `formatFr` renvoie une date en anglais, l'extension `intl` ou les données locales manquent dans l'image : reprendre l'étape 2 de la tâche 1.

- [ ] **Step 6: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core/src cms/tests/Unit
git commit -m "feat(core): formatage des dates en français et construction du lien WhatsApp"
```

---

### Task 6: Service Google Calendar

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Services/GoogleCalendar.php`
- Create: `cms/tests/Unit/Services/GoogleCalendarTest.php`

**Interfaces:**
- Consumes: `Date::monthAbbrFr()`, `Date::formatTime()` (tâche 5)
- Produces:
  - `new GoogleCalendar(string $apiKey, string $calendarId)`
  - `->events(): array` — liste d'événements, cache transient `prophet_calendar_events` (600 s), repli sur `GoogleCalendar::MOCK_EVENTS`
  - `GoogleCalendar::parseItems(array $items): array` — pur, testable sans HTTP
  - Forme d'un événement : `['day','month','year','title','lieu','heure','places','type','featured']`, toutes valeurs `string` sauf `featured` (`bool`)

**Divergence assumée sur le fuseau horaire.** Le bridge Node construit `new Date(…)`
puis lit `getHours()`, ce qui rend l'heure dans le fuseau du **process**, pas dans
celui de l'événement : un événement à 20h00 heure de Paris s'affiche `18h00` sur un
serveur en UTC. Le port PHP conserve le décalage porté par la charge utile Google et
rend donc `20h00`. C'est l'heure que le public doit lire — celle de l'événement, sur
place. On garde le comportement PHP : il corrige un défaut latent de l'existant au
lieu de le reproduire.

**Note d'outillage.** Brain Monkey ne peut pas remplacer les fonctions internes de
PHP (ici `error_log`) sans que Patchwork y soit autorisé. Un fichier
`cms/patchwork.json` déclarant ces fonctions est nécessaire pour que les tests de
repli s'exécutent.

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Services/GoogleCalendarTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Services;

use Brain\Monkey\Functions;
use ProphetCore\Services\GoogleCalendar;
use ProphetCore\Tests\TestCase;

final class GoogleCalendarTest extends TestCase
{
    public function test_un_evenement_horaire_est_mis_en_forme_comme_le_bridge(): void
    {
        $items = [[
            'summary' => 'Nuit de Prophétie',
            'description' => "lieu: Paris, France\nplaces: Entrée libre\ntype: Croisade",
            'start' => ['dateTime' => '2026-04-18T20:00:00+00:00'],
            'end' => ['dateTime' => '2026-04-19T00:00:00+00:00'],
        ]];

        $events = GoogleCalendar::parseItems($items);

        $this->assertCount(1, $events);
        $this->assertSame('18', $events[0]['day']);
        $this->assertSame('Avr', $events[0]['month']);
        $this->assertSame('2026', $events[0]['year']);
        $this->assertSame('Nuit de Prophétie', $events[0]['title']);
        $this->assertSame('Paris, France', $events[0]['lieu']);
        $this->assertSame('20h00 – 00h00', $events[0]['heure']);
        $this->assertSame('Entrée libre', $events[0]['places']);
        $this->assertSame('Croisade', $events[0]['type']);
        $this->assertTrue($events[0]['featured']);
    }

    public function test_la_description_html_est_nettoyee_avant_extraction(): void
    {
        $items = [[
            'summary' => 'Conférence',
            'description' => '<p>lieu: Abidjan</p><br><p>places: Places limitées</p>',
            'start' => ['dateTime' => '2026-04-26T09:00:00+00:00'],
            'end' => ['dateTime' => '2026-04-26T17:00:00+00:00'],
        ]];

        $events = GoogleCalendar::parseItems($items);

        $this->assertSame('Abidjan', $events[0]['lieu']);
        $this->assertSame('Places limitées', $events[0]['places']);
    }

    public function test_un_evenement_sur_la_journee_entiere_affiche_deux_jours(): void
    {
        $items = [[
            'summary' => 'Retraite',
            'description' => '',
            'start' => ['date' => '2026-05-24'],
        ]];

        $events = GoogleCalendar::parseItems($items);

        $this->assertSame('2 jours', $events[0]['heure']);
    }

    public function test_les_valeurs_par_defaut_reprennent_celles_du_bridge(): void
    {
        $items = [[
            'summary' => 'Sans description',
            'location' => 'Lomé',
            'start' => ['dateTime' => '2026-06-01T18:00:00+00:00'],
        ]];

        $events = GoogleCalendar::parseItems($items);

        $this->assertSame('Lomé', $events[0]['lieu']);
        $this->assertSame('Entrée libre', $events[0]['places']);
        $this->assertSame('Conférence', $events[0]['type']);
        $this->assertSame('18h00', $events[0]['heure']);
    }

    public function test_les_entrees_sans_titre_sont_ignorees_et_seule_la_premiere_est_mise_en_avant(): void
    {
        $items = [
            ['description' => '', 'start' => ['dateTime' => '2026-06-01T18:00:00+00:00']],
            ['summary' => 'A', 'description' => '', 'start' => ['dateTime' => '2026-06-02T18:00:00+00:00']],
            ['summary' => 'B', 'description' => '', 'start' => ['dateTime' => '2026-06-03T18:00:00+00:00']],
        ];

        $events = GoogleCalendar::parseItems($items);

        $this->assertCount(2, $events);
        $this->assertTrue($events[0]['featured']);
        $this->assertFalse($events[1]['featured']);
    }

    public function test_sans_cle_api_le_service_renvoie_les_donnees_de_demonstration(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('set_transient')->justReturn(true);

        $events = (new GoogleCalendar('', ''))->events();

        $this->assertSame(GoogleCalendar::MOCK_EVENTS, $events);
    }

    public function test_une_erreur_http_retombe_sur_les_donnees_de_demonstration(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('set_transient')->justReturn(true);
        Functions\when('is_wp_error')->justReturn(true);
        Functions\when('wp_remote_get')->justReturn('erreur');
        Functions\when('error_log')->justReturn(true);

        $events = (new GoogleCalendar('cle', 'agenda'))->events();

        $this->assertSame(GoogleCalendar::MOCK_EVENTS, $events);
    }

    public function test_le_cache_est_servi_sans_appel_http(): void
    {
        Functions\when('get_transient')->justReturn([['title' => 'depuis le cache']]);
        Functions\expect('wp_remote_get')->never();

        $events = (new GoogleCalendar('cle', 'agenda'))->events();

        $this->assertSame('depuis le cache', $events[0]['title']);
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter GoogleCalendarTest"
```

Attendu : ÉCHEC — classe `GoogleCalendar` introuvable.

- [ ] **Step 3: Implémenter le service**

`cms/web/app/mu-plugins/prophet-core/src/Services/GoogleCalendar.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Services;

use DateTimeImmutable;
use ProphetCore\Support\Date;

final class GoogleCalendar
{
    public const TRANSIENT = 'prophet_calendar_events';
    public const TTL = 600; // 10 minutes, TTL du bridge Node.
    public const TTL_ECHEC = 60; // Un échec n'est mis en cache qu'une minute.

    /** Reprises de server/api/calendar/events.get.ts:1-40. */
    public const MOCK_EVENTS = [
        [
            'day' => '18', 'month' => 'Avr', 'year' => '2026',
            'title' => '[MOCK] Nuit de Prophétie & Délivrance',
            'lieu' => 'Paris, France', 'heure' => '20h00 – 00h00',
            'places' => 'Entrée libre', 'type' => 'Croisade', 'featured' => true,
        ],
        [
            'day' => '26', 'month' => 'Avr', 'year' => '2026',
            'title' => '[MOCK] Conférence des Leaders & Entrepreneurs',
            'lieu' => "Abidjan, Côte d'Ivoire", 'heure' => '09h00 – 17h00',
            'places' => 'Places limitées', 'type' => 'Conférence', 'featured' => false,
        ],
        [
            'day' => '10', 'month' => 'Mai', 'year' => '2026',
            'title' => '[MOCK] Crusade Prophétique Internationale',
            'lieu' => 'Lagos, Nigeria', 'heure' => '18h00 – 23h00',
            'places' => 'Entrée libre', 'type' => 'Croisade', 'featured' => false,
        ],
        [
            'day' => '24', 'month' => 'Mai', 'year' => '2026',
            'title' => '[MOCK] Retraite Spirituelle — Activation des Appels',
            'lieu' => 'Montréal, Canada', 'heure' => '2 jours (Sam & Dim)',
            'places' => 'Sur inscription', 'type' => 'Retraite', 'featured' => false,
        ],
    ];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $calendarId,
    ) {
    }

    public function events(): array
    {
        $cached = get_transient(self::TRANSIENT);
        if (is_array($cached)) {
            return $cached;
        }

        $events = $this->fetch();

        if ($events === null) {
            // Un échec ne fige pas les données de démonstration pour 10 minutes :
            // le site se remet tout seul à la minute suivante, sans pour autant
            // rappeler l'API à chaque affichage de page.
            set_transient(self::TRANSIENT, self::MOCK_EVENTS, self::TTL_ECHEC);

            return self::MOCK_EVENTS;
        }

        set_transient(self::TRANSIENT, $events, self::TTL);

        return $events;
    }

    /** Renvoie null en cas d'échec, pour que l'appelant distingue succès et repli. */
    private function fetch(): ?array
    {
        if ($this->apiKey === '' || $this->calendarId === '') {
            return null;
        }

        $url = 'https://www.googleapis.com/calendar/v3/calendars/'
            . rawurlencode($this->calendarId) . '/events?' . http_build_query([
                'key' => $this->apiKey,
                'timeMin' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
                'orderBy' => 'startTime',
                'singleEvents' => 'true',
                'maxResults' => '10',
            ]);

        $response = wp_remote_get($url, ['timeout' => 8]);

        if (is_wp_error($response)) {
            error_log('[Calendar] appel en échec, retour des données de démonstration');

            return null;
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);

        if (! is_array($payload) || ! isset($payload['items'])) {
            error_log('[Calendar] réponse inattendue, retour des données de démonstration');

            return null;
        }

        return self::parseItems($payload['items']);
    }

    /** Port de api-bridge/src/services/calendar.ts:66-97. */
    public static function parseItems(array $items): array
    {
        $events = [];

        foreach ($items as $item) {
            // Pas `empty()` : en PHP `empty('0')` est vrai, alors que le bridge
            // conserve un titre valant littéralement « 0 ».
            if (($item['summary'] ?? '') === '') {
                continue;
            }

            $description = (string) ($item['description'] ?? '');
            $startRaw = $item['start']['dateTime'] ?? $item['start']['date'] ?? '';
            $start = new DateTimeImmutable($startRaw);
            $endRaw = $item['end']['dateTime'] ?? null;

            if (isset($item['start']['dateTime'])) {
                $heure = $endRaw !== null
                    ? Date::formatTime($start) . ' – ' . Date::formatTime(new DateTimeImmutable($endRaw))
                    : Date::formatTime($start);
            } else {
                $heure = '2 jours';
            }

            $events[] = [
                'day' => str_pad($start->format('j'), 2, '0', STR_PAD_LEFT),
                'month' => Date::monthAbbrFr((int) $start->format('n') - 1),
                'year' => $start->format('Y'),
                'title' => (string) $item['summary'],
                'lieu' => self::parseField($description, 'lieu') ?: (string) ($item['location'] ?? ''),
                'heure' => $heure,
                'places' => self::parseField($description, 'places') ?: 'Entrée libre',
                'type' => self::parseField($description, 'type') ?: 'Conférence',
                'featured' => $events === [],
            ];
        }

        return $events;
    }

    private static function parseField(string $description, string $key): string
    {
        $plain = preg_replace('/<[^>]+>/', "\n", $description) ?? '';
        $plain = str_replace('&nbsp;', ' ', $plain);
        $plain = preg_replace('/\n{2,}/', "\n", $plain) ?? '';

        if (preg_match('/' . preg_quote($key, '/') . ':\s*([^\n]+)/u', $plain, $matches) === 1) {
            return trim($matches[1]);
        }

        return '';
    }
}
```

- [ ] **Step 4: Lancer les tests et vérifier qu'ils passent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter GoogleCalendarTest"
```

Attendu : PASS, 8 tests.

- [ ] **Step 5: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core/src/Services/GoogleCalendar.php \
        cms/tests/Unit/Services/GoogleCalendarTest.php
git commit -m "feat(core): service Google Calendar avec cache et repli sur données de démonstration"
```

---

### Task 7: Service YouTube

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Services/Youtube.php`
- Create: `cms/tests/Unit/Services/YoutubeTest.php`

**Interfaces:**
- Consumes: rien des tâches précédentes hors `TestCase`
- Produces:
  - `new Youtube(string $apiKey, string $channelId)`
  - `->videos(): array` — cache transient `prophet_youtube_videos` (1800 s), repli sur `Youtube::MOCK_VIDEOS`
  - `Youtube::parseItems(array $items): array` — pur
  - `Youtube::uploadsPlaylistId(string $channelId): string`
  - Forme d'une vidéo : `['id','title','desc','thumbnail','type','duration']`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Services/YoutubeTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Services;

use Brain\Monkey\Functions;
use ProphetCore\Services\Youtube;
use ProphetCore\Tests\TestCase;

final class YoutubeTest extends TestCase
{
    public function test_l_identifiant_de_playlist_derive_de_l_identifiant_de_chaine(): void
    {
        $this->assertSame('UUabc123', Youtube::uploadsPlaylistId('UCabc123'));
    }

    public function test_seule_la_premiere_occurrence_de_uc_est_remplacee(): void
    {
        $this->assertSame('UUxUCy', Youtube::uploadsPlaylistId('UCxUCy'));
    }

    public function test_la_miniature_haute_definition_est_preferee(): void
    {
        $items = [[
            'snippet' => [
                'title' => 'Prophétie sur les nations',
                'description' => 'Description de la vidéo.',
                'resourceId' => ['videoId' => 'abc'],
                'thumbnails' => [
                    'high' => ['url' => 'https://i.ytimg.com/high.jpg'],
                    'medium' => ['url' => 'https://i.ytimg.com/medium.jpg'],
                ],
            ],
        ]];

        $videos = Youtube::parseItems($items);

        $this->assertSame('abc', $videos[0]['id']);
        $this->assertSame('Prophétie sur les nations', $videos[0]['title']);
        $this->assertSame('Description de la vidéo.', $videos[0]['desc']);
        $this->assertSame('https://i.ytimg.com/high.jpg', $videos[0]['thumbnail']);
        $this->assertSame('Vidéo', $videos[0]['type']);
        $this->assertSame('', $videos[0]['duration']);
    }

    public function test_sans_miniature_on_retombe_sur_img_youtube_com(): void
    {
        $items = [[
            'snippet' => [
                'title' => 'Sans miniature',
                'description' => '',
                'resourceId' => ['videoId' => 'xyz'],
            ],
        ]];

        $videos = Youtube::parseItems($items);

        $this->assertSame('https://img.youtube.com/vi/xyz/hqdefault.jpg', $videos[0]['thumbnail']);
    }

    public function test_sans_cle_api_le_service_renvoie_les_donnees_de_demonstration(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('set_transient')->justReturn(true);

        $videos = (new Youtube('', ''))->videos();

        $this->assertSame(Youtube::MOCK_VIDEOS, $videos);
    }

    public function test_une_erreur_http_retombe_sur_les_donnees_de_demonstration(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('set_transient')->justReturn(true);
        Functions\when('is_wp_error')->justReturn(true);
        Functions\when('wp_remote_get')->justReturn('erreur');
        Functions\when('error_log')->justReturn(true);

        $videos = (new Youtube('cle', 'UCabc'))->videos();

        $this->assertSame(Youtube::MOCK_VIDEOS, $videos);
    }

    public function test_le_cache_est_servi_sans_appel_http(): void
    {
        Functions\when('get_transient')->justReturn([['title' => 'depuis le cache']]);
        Functions\expect('wp_remote_get')->never();

        $videos = (new Youtube('cle', 'UCabc'))->videos();

        $this->assertSame('depuis le cache', $videos[0]['title']);
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter YoutubeTest"
```

Attendu : ÉCHEC — classe `Youtube` introuvable.

- [ ] **Step 3: Implémenter le service**

`cms/web/app/mu-plugins/prophet-core/src/Services/Youtube.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Services;

final class Youtube
{
    public const TRANSIENT = 'prophet_youtube_videos';
    public const TTL = 1800; // 30 minutes, TTL du bridge Node.
    public const TTL_ECHEC = 60; // Un échec n'est mis en cache qu'une minute.

    /** Reprises de server/api/youtube/latest.get.ts:1-27. */
    public const MOCK_VIDEOS = [
        [
            'id' => 'dQw4w9WgXcQ',
            'title' => '[MOCK] Prophétie sur les nations — 2026',
            'desc' => 'Le Prophète Jeremiah annonce ce que Dieu prépare pour les nations en cette nouvelle saison.',
            'thumbnail' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            'type' => 'Prophétie', 'duration' => '',
        ],
        [
            'id' => 'dQw4w9WgXcQ',
            'title' => '[MOCK] Consultation prophétique en direct',
            'desc' => 'Session de consultations prophétiques en direct avec des révélations précises et vérifiables.',
            'thumbnail' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            'type' => 'En direct', 'duration' => '',
        ],
        [
            'id' => 'dQw4w9WgXcQ',
            'title' => '[MOCK] Témoignages — Prophéties accomplies',
            'desc' => 'Des personnes témoignent de la précision des prophéties reçues lors de leurs consultations.',
            'thumbnail' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            'type' => 'Témoignages', 'duration' => '',
        ],
    ];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $channelId,
    ) {
    }

    public function videos(): array
    {
        $cached = get_transient(self::TRANSIENT);
        if (is_array($cached)) {
            return $cached;
        }

        $videos = $this->fetch();

        if ($videos === null) {
            // Même règle que pour l'agenda : un échec ne fige pas les données de
            // démonstration pour 30 minutes.
            set_transient(self::TRANSIENT, self::MOCK_VIDEOS, self::TTL_ECHEC);

            return self::MOCK_VIDEOS;
        }

        set_transient(self::TRANSIENT, $videos, self::TTL);

        return $videos;
    }

    /** playlistItems.list coûte 1 unité de quota contre 100 pour search.list. */
    public static function uploadsPlaylistId(string $channelId): string
    {
        return preg_replace('/^UC/', 'UU', $channelId, 1) ?? $channelId;
    }

    /** Renvoie null en cas d'échec, pour que l'appelant distingue succès et repli. */
    private function fetch(): ?array
    {
        if ($this->apiKey === '' || $this->channelId === '') {
            return null;
        }

        $url = 'https://www.googleapis.com/youtube/v3/playlistItems?' . http_build_query([
            'key' => $this->apiKey,
            'playlistId' => self::uploadsPlaylistId($this->channelId),
            'part' => 'snippet',
            'maxResults' => '3',
        ]);

        $response = wp_remote_get($url, ['timeout' => 8]);

        if (is_wp_error($response)) {
            error_log('[YouTube] appel en échec, retour des données de démonstration');

            return null;
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);

        if (! is_array($payload) || ! isset($payload['items'])) {
            error_log('[YouTube] réponse inattendue, retour des données de démonstration');

            return null;
        }

        return self::parseItems($payload['items']);
    }

    /** Port de api-bridge/src/services/youtube.ts:52-68. */
    public static function parseItems(array $items): array
    {
        $videos = [];

        foreach ($items as $item) {
            $snippet = $item['snippet'] ?? [];
            $videoId = (string) ($snippet['resourceId']['videoId'] ?? '');

            $videos[] = [
                'id' => $videoId,
                'title' => (string) ($snippet['title'] ?? ''),
                'desc' => (string) ($snippet['description'] ?? ''),
                'thumbnail' => $snippet['thumbnails']['high']['url']
                    ?? $snippet['thumbnails']['medium']['url']
                    ?? 'https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg',
                'type' => 'Vidéo',
                'duration' => '',
            ];
        }

        return $videos;
    }
}
```

- [ ] **Step 4: Lancer les tests et vérifier qu'ils passent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter YoutubeTest"
```

Attendu : PASS, 7 tests.

- [ ] **Step 5: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core/src/Services/Youtube.php \
        cms/tests/Unit/Services/YoutubeTest.php
git commit -m "feat(core): service YouTube avec cache et repli sur données de démonstration"
```

---
### Task 8: Réglages, rendu de gabarits et envoi des emails

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Options.php`
- Create: `cms/web/app/mu-plugins/prophet-core/src/Services/TemplateRenderer.php`
- Create: `cms/web/app/mu-plugins/prophet-core/src/Services/BladeRenderer.php`
- Create: `cms/web/app/mu-plugins/prophet-core/src/Services/Mailer.php`
- Create: `cms/web/app/mu-plugins/prophet-core/src/Services/SmtpConfigurator.php`
- Create: `cms/tests/Unit/OptionsTest.php`, `cms/tests/Unit/Services/MailerTest.php`
- Create: `cms/web/app/themes/prophet/resources/views/emails/client.blade.php`
- Create: `cms/web/app/themes/prophet/resources/views/emails/prophet.blade.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/prophet-core.php`

**Interfaces:**
- Consumes: `Date::formatFr()` (tâche 5)
- Produces:
  - `Options::phone1(): string`, `phone2(): string`, `prophetEmail(): string`
  - `Options::heures(): array<int, string>`
  - `Options::modesPaiement(): array<int, array{value: string, label: string}>`
  - `Options::modePaiementLabel(string $value): string`
  - `Options::content(string $key, string $default = ''): string`
  - `Options::env(string $key, string $default = ''): string`
  - `interface TemplateRenderer { public function render(string $view, array $data): string; }`
  - `new Mailer(TemplateRenderer $renderer)` avec `sendClientConfirmation(array $rdv): bool` et `sendProphetNotification(array $rdv): bool`
  - `SmtpConfigurator::register(): void`

- [ ] **Step 1: Écrire les tests qui échouent**

`cms/tests/Unit/OptionsTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit;

use Brain\Monkey\Functions;
use ProphetCore\Options;
use ProphetCore\Tests\TestCase;

final class OptionsTest extends TestCase
{
    public function test_le_numero_vient_des_reglages_quand_il_est_saisi(): void
    {
        Functions\when('carbon_get_theme_option')->justReturn('+22800000000');

        $this->assertSame('+22800000000', Options::phone1());
    }

    public function test_le_numero_retombe_sur_l_environnement_quand_le_reglage_est_vide(): void
    {
        Functions\when('carbon_get_theme_option')->justReturn('');
        $_ENV['PROPHET_PHONE_1'] = '+22897169090';

        $this->assertSame('+22897169090', Options::phone1());

        unset($_ENV['PROPHET_PHONE_1']);
    }

    public function test_les_creneaux_horaires_sont_aplatis_depuis_le_repeteur(): void
    {
        Functions\when('carbon_get_theme_option')->justReturn([
            ['valeur' => '08h00'],
            ['valeur' => '09h00'],
        ]);

        $this->assertSame(['08h00', '09h00'], Options::heures());
    }

    public function test_le_libelle_d_un_mode_de_paiement_est_resolu(): void
    {
        Functions\when('carbon_get_theme_option')->justReturn([
            ['valeur' => 'mobile_money', 'libelle' => 'Mobile Money'],
            ['valeur' => 'crypto', 'libelle' => 'Crypto USDT'],
        ]);

        $this->assertSame('Crypto USDT', Options::modePaiementLabel('crypto'));
    }

    public function test_un_mode_de_paiement_inconnu_renvoie_sa_valeur_brute(): void
    {
        Functions\when('carbon_get_theme_option')->justReturn([]);

        $this->assertSame('inconnu', Options::modePaiementLabel('inconnu'));
    }
}
```

`cms/tests/Unit/Services/MailerTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Services;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\Services\Mailer;
use ProphetCore\Services\TemplateRenderer;
use ProphetCore\Tests\TestCase;

final class MailerTest extends TestCase
{
    private array $envois = [];

    private function renderer(): TemplateRenderer
    {
        return new class () implements TemplateRenderer {
            public function render(string $view, array $data): string
            {
                return '<html>' . $view . '</html>';
            }
        };
    }

    private function rdv(): array
    {
        return [
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane@example.test',
            'telephone' => '+22890000000',
            'pays' => 'Togo',
            'date' => new DateTimeImmutable('2026-07-30 10:00:00', new DateTimeZone('UTC')),
            'heure' => '10h00',
            'type_consultation' => 'Mariage',
            'mode_paiement' => 'mobile_money',
            'message' => '',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // sendClientConfirmation() passe l'URL du site au gabarit : sans ce
        // remplacement, l'appel à home_url() lève une erreur fatale.
        Functions\when('home_url')->justReturn('https://exemple.test/');
    }

    private function capturerLesEnvois(): void
    {
        $this->envois = [];

        Functions\when('wp_mail')->alias(function ($to, $subject, $body, $headers) {
            $this->envois[] = compact('to', 'subject', 'body', 'headers');

            return true;
        });
        Functions\when('carbon_get_theme_option')->justReturn('');
    }

    public function test_l_email_client_part_a_la_bonne_adresse_avec_le_bon_sujet(): void
    {
        $this->capturerLesEnvois();
        $_ENV['SMTP_USER'] = 'site@example.test';

        (new Mailer($this->renderer()))->sendClientConfirmation($this->rdv());

        $this->assertCount(1, $this->envois);
        $this->assertSame('jane@example.test', $this->envois[0]['to']);
        $this->assertSame(
            '✅ Confirmation de votre demande de RDV — Mariage',
            $this->envois[0]['subject']
        );
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $this->envois[0]['headers']);

        unset($_ENV['SMTP_USER']);
    }

    public function test_la_notification_part_au_prophete_avec_un_reply_to_client(): void
    {
        $this->capturerLesEnvois();
        $_ENV['PROPHET_EMAIL'] = 'prophete@example.test';
        $_ENV['SMTP_USER'] = 'site@example.test';

        (new Mailer($this->renderer()))->sendProphetNotification($this->rdv());

        $this->assertSame('prophete@example.test', $this->envois[0]['to']);
        $this->assertSame(
            '📅 Nouveau RDV : Jane Doe — Mariage — jeudi 30 juillet 2026',
            $this->envois[0]['subject']
        );
        $this->assertContains('Reply-To: jane@example.test', $this->envois[0]['headers']);

        unset($_ENV['PROPHET_EMAIL'], $_ENV['SMTP_USER']);
    }

    public function test_un_echec_d_envoi_est_journalise_et_ne_leve_pas_d_exception(): void
    {
        $this->envois = [];
        Functions\when('carbon_get_theme_option')->justReturn('');
        Functions\when('wp_mail')->justReturn(false);
        Functions\when('error_log')->justReturn(true);

        $resultat = (new Mailer($this->renderer()))->sendClientConfirmation($this->rdv());

        $this->assertFalse($resultat);
    }
}
```

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter 'OptionsTest|MailerTest'"
```

Attendu : ÉCHEC — classes `Options` et `Mailer` introuvables.

- [ ] **Step 3: Implémenter `Options`**

`cms/web/app/mu-plugins/prophet-core/src/Options.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore;

final class Options
{
    public static function env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    public static function phone1(): string
    {
        return self::option('rdv_phone_1') ?: self::env('PROPHET_PHONE_1');
    }

    public static function phone2(): string
    {
        return self::option('rdv_phone_2') ?: self::env('PROPHET_PHONE_2');
    }

    public static function prophetEmail(): string
    {
        return self::option('rdv_email') ?: self::env('PROPHET_EMAIL');
    }

    /** @return array<int, string> */
    public static function heures(): array
    {
        $rows = carbon_get_theme_option('rdv_heures');

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) ($row['valeur'] ?? ''),
            $rows
        )));
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function modesPaiement(): array
    {
        $rows = carbon_get_theme_option('rdv_modes_paiement');

        if (! is_array($rows)) {
            return [];
        }

        return array_map(static fn (array $row): array => [
            'value' => (string) ($row['valeur'] ?? ''),
            'label' => (string) ($row['libelle'] ?? ''),
        ], $rows);
    }

    public static function modePaiementLabel(string $value): string
    {
        foreach (self::modesPaiement() as $mode) {
            if ($mode['value'] === $value) {
                return $mode['label'];
            }
        }

        return $value;
    }

    public static function content(string $key, string $default = ''): string
    {
        return self::option($key) ?: $default;
    }

    private static function option(string $key): string
    {
        $value = carbon_get_theme_option($key);

        return is_string($value) ? $value : '';
    }
}
```

- [ ] **Step 4: Implémenter le rendu de gabarits**

`cms/web/app/mu-plugins/prophet-core/src/Services/TemplateRenderer.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Services;

interface TemplateRenderer
{
    /** @param array<string, mixed> $data */
    public function render(string $view, array $data): string;
}
```

`cms/web/app/mu-plugins/prophet-core/src/Services/BladeRenderer.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Services;

/**
 * Rend une vue Blade du thème via Acorn. Le mu-plugin ne dépend du thème
 * que par cette classe : un thème absent dégrade l'email en texte minimal
 * plutôt que de faire échouer la soumission du rendez-vous.
 */
final class BladeRenderer implements TemplateRenderer
{
    public function render(string $view, array $data): string
    {
        if (! function_exists('\\Roots\\view')) {
            error_log('[Mailer] Acorn indisponible, gabarit ' . $view . ' non rendu');

            return '';
        }

        return (string) \Roots\view($view, $data)->render();
    }
}
```

- [ ] **Step 5: Implémenter `Mailer`**

`cms/web/app/mu-plugins/prophet-core/src/Services/Mailer.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Services;

use ProphetCore\Options;
use ProphetCore\Support\Date;

final class Mailer
{
    public function __construct(private readonly TemplateRenderer $renderer)
    {
    }

    /** Port de server/utils/mailer.ts:45-146. */
    public function sendClientConfirmation(array $rdv): bool
    {
        $sujet = '✅ Confirmation de votre demande de RDV — ' . $rdv['type_consultation'];

        $corps = $this->renderer->render('emails.client', [
            'rdv' => $rdv,
            'dateFr' => Date::formatFr($rdv['date']),
            'modePaiementLabel' => Options::modePaiementLabel($rdv['mode_paiement']),
            'phone1' => Options::phone1(),
            'phone2' => Options::phone2(),
            'siteUrl' => home_url('/'),
            'annee' => (new \DateTimeImmutable('now'))->format('Y'),
        ]);

        return $this->send(
            $rdv['email'],
            $sujet,
            $corps,
            ['From: "Prophète Jeremiah Nahoum" <' . Options::env('SMTP_USER') . '>']
        );
    }

    /** Port de server/utils/mailer.ts:151-207. */
    public function sendProphetNotification(array $rdv): bool
    {
        $dateFr = Date::formatFr($rdv['date']);
        $sujet = sprintf(
            '📅 Nouveau RDV : %s %s — %s — %s',
            $rdv['prenom'],
            $rdv['nom'],
            $rdv['type_consultation'],
            $dateFr
        );

        $corps = $this->renderer->render('emails.prophet', [
            'rdv' => $rdv,
            'dateFr' => $dateFr,
            'modePaiementLabel' => Options::modePaiementLabel($rdv['mode_paiement']),
        ]);

        return $this->send(
            Options::prophetEmail(),
            $sujet,
            $corps,
            [
                'From: "Site Prophet RDV" <' . Options::env('SMTP_USER') . '>',
                'Reply-To: ' . $rdv['email'],
            ]
        );
    }

    private function send(string $to, string $sujet, string $corps, array $headers): bool
    {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        $envoye = (bool) wp_mail($to, $sujet, $corps, $headers);

        if (! $envoye) {
            error_log('[Mailer] envoi en échec vers ' . $to . ' — « ' . $sujet . ' »');
        }

        return $envoye;
    }
}
```

- [ ] **Step 6: Lancer les tests et vérifier qu'ils passent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter 'OptionsTest|MailerTest'"
```

Attendu : PASS, 8 tests.

- [ ] **Step 7: Configurer le SMTP**

`cms/web/app/mu-plugins/prophet-core/src/Services/SmtpConfigurator.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Services;

use ProphetCore\Options;

final class SmtpConfigurator
{
    public static function register(): void
    {
        add_action('phpmailer_init', static function ($phpmailer): void {
            $host = Options::env('SMTP_HOST');
            $user = Options::env('SMTP_USER');

            if ($host === '' || $user === '') {
                return; // Sans SMTP configuré, WordPress garde son envoi par défaut.
            }

            $phpmailer->isSMTP();
            $phpmailer->Host = $host;
            $phpmailer->Port = (int) Options::env('SMTP_PORT', '587');
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $user;
            $phpmailer->Password = Options::env('SMTP_PASS');
            $phpmailer->SMTPSecure = Options::env('SMTP_SECURE') === 'true' ? 'ssl' : 'tls';
            $phpmailer->CharSet = 'UTF-8';
        });
    }
}
```

Puis dans `prophet-core.php`, sous le commentaire de la tâche 3 :

```php
use ProphetCore\Services\SmtpConfigurator;

add_action('plugins_loaded', static function (): void {
    SmtpConfigurator::register();
});
```

- [ ] **Step 8: Porter les deux gabarits d'email**

Créer `cms/web/app/themes/prophet/resources/views/emails/client.blade.php` en transposant le HTML de `server/utils/mailer.ts:52-136` : styles inline inchangés, et substitutions

- `${data.prenom} ${data.nom}` → `{{ $rdv['prenom'] }} {{ $rdv['nom'] }}`
- `${data.typeConsultation}` → `{{ $rdv['type_consultation'] }}`
- `${dateFr}` → `{{ $dateFr }}`
- `${data.heure}`, `${data.pays}`, `${data.telephone}` → clés correspondantes de `$rdv`
- `${getModePaiementLabel(...)}` → `{{ $modePaiementLabel }}`
- `${config.prophetPhone1}` / `${config.prophetPhone2}` → `{{ $phone1 }}` / `{{ $phone2 }}`, le second entouré de `@if ($phone2)`
- `${new Date().getFullYear()}` → `{{ $annee }}`
- `${config.public.siteUrl}` → `{{ $siteUrl }}`

Créer `cms/web/app/themes/prophet/resources/views/emails/prophet.blade.php` en transposant `server/utils/mailer.ts:158-197` de la même façon, avec `@if ($rdv['message'])` autour du bloc `.message-box`.

- [ ] **Step 9: Vérifier l'envoi réel**

```bash
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp eval '
  $m = new \ProphetCore\Services\Mailer(new \ProphetCore\Services\BladeRenderer());
  var_dump($m->sendClientConfirmation([
    "nom" => "Test", "prenom" => "Jane", "email" => "jane@example.test",
    "telephone" => "+228", "pays" => "Togo",
    "date" => new DateTimeImmutable("2026-07-30"), "heure" => "10h00",
    "type_consultation" => "Mariage", "mode_paiement" => "mobile_money", "message" => "",
  ]));'
```

Sans SMTP configuré en développement, `wp_mail` renvoie `false` : c'est attendu. Vérifier surtout qu'aucune erreur PHP n'est levée et que le gabarit Blade est bien rendu (ajouter temporairement un `echo` du corps si nécessaire).

- [ ] **Step 10: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit \
        cms/web/app/themes/prophet/resources/views/emails
git commit -m "feat(core): réglages, rendu Blade des emails et configuration SMTP"
```

---

### Task 9: Types de contenu et champs éditoriaux

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/PostTypes/{Service,Temoignage,Photo}.php`
- Create: `cms/web/app/mu-plugins/prophet-core/src/Fields/{ServiceFields,TemoignageFields,PhotoFields,ContenuOptions,RdvOptions}.php`
- Create: `cms/tests/Unit/PostTypes/ServiceTest.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/prophet-core.php`

**Interfaces:**
- Consumes: Carbon Fields (dépendance ajoutée en tâche 3)
- Produces:
  - CPT `service`, `temoignage`, `photo` enregistrés, chacun avec `PostType::SLUG` et `PostType::register(): void`
  - Champs Carbon accessibles par `carbon_get_post_meta($id, 'service_icone')`, `'service_couleur'`, `'temoignage_initiales'`, `'temoignage_pays'`, `'temoignage_service'`, `'temoignage_etoiles'`, `'temoignage_couleur'`, `'temoignage_texte'`, `'photo_image'`, `'photo_legende'`, `'photo_format'`
  - Options de thème : `hero_*`, `about_*`, `stats` (répéteur `valeur`/`libelle`), `cta_*`, `footer_*`, `rdv_phone_1`, `rdv_phone_2`, `rdv_email`, `rdv_heures`, `rdv_modes_paiement`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/PostTypes/ServiceTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\PostTypes;

use Brain\Monkey\Functions;
use ProphetCore\PostTypes\Service;
use ProphetCore\Tests\TestCase;

final class ServiceTest extends TestCase
{
    public function test_le_type_est_enregistre_public_et_ordonnable(): void
    {
        $capture = [];

        Functions\when('register_post_type')->alias(function ($slug, $args) use (&$capture) {
            $capture = ['slug' => $slug, 'args' => $args];
        });
        Functions\when('__')->returnArg();
        Functions\when('add_action')->alias(fn ($hook, $callback) => $callback());

        Service::register();

        $this->assertSame('service', $capture['slug']);
        $this->assertTrue($capture['args']['public']);
        $this->assertContains('page-attributes', $capture['args']['supports']);
        $this->assertSame('dashicons-star-filled', $capture['args']['menu_icon']);
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter ServiceTest"
```

Attendu : ÉCHEC — classe `Service` introuvable.

- [ ] **Step 3: Implémenter les trois types de contenu**

`cms/web/app/mu-plugins/prophet-core/src/PostTypes/Service.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\PostTypes;

final class Service
{
    public const SLUG = 'service';

    public static function register(): void
    {
        add_action('init', static function (): void {
            register_post_type(self::SLUG, [
                'labels' => [
                    'name' => 'Services',
                    'singular_name' => 'Service',
                    'add_new_item' => 'Ajouter un service',
                    'edit_item' => 'Modifier le service',
                ],
                'public' => true,
                'has_archive' => false,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-star-filled',
                'supports' => ['title', 'editor', 'page-attributes'],
                'rewrite' => ['slug' => 'services'],
            ]);
        });
    }
}
```

`Temoignage.php` — identique en structure, avec :

```php
public const SLUG = 'temoignage';
// labels : « Témoignages » / « Témoignage » / « Ajouter un témoignage »
// 'public' => false, 'show_ui' => true,
// 'menu_icon' => 'dashicons-format-quote',
// 'supports' => ['title', 'page-attributes'],
```

`Photo.php` :

```php
public const SLUG = 'photo';
// labels : « Photos » / « Photo » / « Ajouter une photo »
// 'public' => false, 'show_ui' => true,
// 'menu_icon' => 'dashicons-format-gallery',
// 'supports' => ['title', 'page-attributes'],
```

- [ ] **Step 4: Lancer le test et vérifier qu'il passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter ServiceTest"
```

Attendu : PASS.

- [ ] **Step 5: Déclarer les champs des types de contenu**

`cms/web/app/mu-plugins/prophet-core/src/Fields/ServiceFields.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use ProphetCore\PostTypes\Service;

final class ServiceFields
{
    public static function register(): void
    {
        add_action('carbon_fields_register_fields', static function (): void {
            Container::make('post_meta', 'Détails du service')
                ->where('post_type', '=', Service::SLUG)
                ->add_fields([
                    Field::make('text', 'service_icone', 'Icône')
                        ->set_help_text('Nom lucide en minuscules : heart, sparkles, mic-2, landmark…')
                        ->set_required(true),
                    Field::make('color', 'service_couleur', 'Couleur')
                        ->set_default_value('#B07A14'),
                ]);
        });
    }
}
```

`TemoignageFields.php` — même forme, `where('post_type', '=', Temoignage::SLUG)`, champs :

```php
Field::make('text', 'temoignage_initiales', 'Initiales')->set_required(true),
Field::make('text', 'temoignage_pays', 'Pays')->set_required(true),
Field::make('association', 'temoignage_service', 'Service concerné')
    ->set_types([['type' => 'post', 'post_type' => Service::SLUG]])
    ->set_max(1),
Field::make('select', 'temoignage_etoiles', 'Étoiles')
    ->set_options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
    ->set_default_value(5),
Field::make('color', 'temoignage_couleur', 'Couleur')->set_default_value('#B07A14'),
Field::make('textarea', 'temoignage_texte', 'Témoignage')->set_required(true),
```

`PhotoFields.php` :

```php
Field::make('image', 'photo_image', 'Image')->set_value_type('id')->set_required(true),
Field::make('text', 'photo_legende', 'Légende'),
Field::make('select', 'photo_format', 'Format')
    ->set_options(['normal' => 'Normal', 'wide' => 'Large'])
    ->set_default_value('normal'),
```

- [ ] **Step 6: Déclarer les deux pages d'options**

`cms/web/app/mu-plugins/prophet-core/src/Fields/ContenuOptions.php` :

```php
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
```

`RdvOptions.php` :

```php
Container::make('theme_options', 'Réglages RDV')
    ->set_icon('dashicons-calendar-alt')
    ->add_fields([
        Field::make('text', 'rdv_phone_1', 'WhatsApp principal'),
        Field::make('text', 'rdv_phone_2', 'WhatsApp secondaire'),
        Field::make('text', 'rdv_email', 'Email de notification'),
        Field::make('complex', 'rdv_heures', 'Créneaux horaires')
            ->add_fields([Field::make('text', 'valeur', 'Heure')->set_help_text('Ex. 08h00')]),
        Field::make('complex', 'rdv_modes_paiement', 'Modes de paiement')
            ->add_fields([
                Field::make('text', 'valeur', 'Valeur technique')->set_help_text('Ex. mobile_money'),
                Field::make('text', 'libelle', 'Libellé affiché')->set_help_text('Ex. Mobile Money'),
            ]),
        Field::make('textarea', 'rdv_aide', 'Texte d\'aide du formulaire'),
    ]);
```

- [ ] **Step 7: Amorcer Carbon Fields et brancher les enregistrements**

Dans `prophet-core.php` :

```php
use Carbon_Fields\Carbon_Fields;
use ProphetCore\Fields\ContenuOptions;
use ProphetCore\Fields\PhotoFields;
use ProphetCore\Fields\RdvOptions;
use ProphetCore\Fields\ServiceFields;
use ProphetCore\Fields\TemoignageFields;
use ProphetCore\PostTypes\Photo;
use ProphetCore\PostTypes\Service;
use ProphetCore\PostTypes\Temoignage;

add_action('after_setup_theme', static function (): void {
    Carbon_Fields::boot();
});

Service::register();
Temoignage::register();
Photo::register();

ServiceFields::register();
TemoignageFields::register();
PhotoFields::register();
ContenuOptions::register();
RdvOptions::register();
```

- [ ] **Step 8: Vérifier dans WordPress**

```bash
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp post-type list --fields=name --format=csv
```

Attendu : la liste contient `service`, `temoignage`, `photo`.

Ouvrir ensuite `http://localhost:8080/wp/wp-admin/` et vérifier que les menus « Services », « Témoignages », « Photos », « Contenu du site » et « Réglages RDV » apparaissent, et que la création d'un service affiche les champs Icône et Couleur.

- [ ] **Step 9: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/PostTypes
git commit -m "feat(core): types de contenu services, témoignages, photos et pages d'options"
```

---

### Task 10: Type de contenu Rendez-vous

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/PostTypes/RendezVous.php`
- Create: `cms/web/app/mu-plugins/prophet-core/src/Fields/RendezVousFields.php`
- Create: `cms/tests/Unit/PostTypes/RendezVousTest.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/prophet-core.php`

**Interfaces:**
- Consumes: rien
- Produces:
  - `RendezVous::SLUG = 'rendez_vous'`, `RendezVous::register(): void`
  - Statuts `prophet_en_attente`, `prophet_confirme`, `prophet_annule` ; constantes `RendezVous::STATUT_EN_ATTENTE`, `STATUT_CONFIRME`, `STATUT_ANNULE`
  - `RendezVous::META_PREFIX = '_rdv_'` et `RendezVous::metaKey(string $champ): string`
  - Champs : `nom`, `prenom`, `email`, `telephone`, `pays`, `date` (`Y-m-d`), `heure`, `type_consultation`, `mode_paiement`, `message`, `ref`
  - Colonnes d'administration : date, nom, type, statut

**Le préfixe est imposé par Carbon Fields, pas par goût.** Carbon Fields stocke
toute méta de publication sous une clé préfixée d'un `_` (`Key_Toolset::KEY_PREFIX`,
non configurable). Un code qui écrirait `rdv_prenom` en direct produirait donc un
rendez-vous que le panneau d'administration afficherait vide, et réciproquement —
deux jeux de clés pour une même donnée. D'où une source unique : **personne
n'écrit une clé littérale**, tout passe par `RendezVous::metaKey('prenom')`. Le
préfixe `_` a par ailleurs le mérite de rendre ces données personnelles invisibles
dans l'éditeur de champs personnalisés de WordPress.

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/PostTypes/RendezVousTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\PostTypes;

use Brain\Monkey\Functions;
use ProphetCore\PostTypes\RendezVous;
use ProphetCore\Tests\TestCase;

final class RendezVousTest extends TestCase
{
    public function test_le_type_n_est_pas_public_mais_visible_en_administration(): void
    {
        $capture = [];
        $statuts = [];

        Functions\when('register_post_type')->alias(function ($slug, $args) use (&$capture) {
            $capture = ['slug' => $slug, 'args' => $args];
        });
        Functions\when('register_post_status')->alias(function ($slug) use (&$statuts) {
            $statuts[] = $slug;
        });
        Functions\when('add_action')->alias(fn ($hook, $callback) => $callback());
        Functions\when('add_filter')->justReturn(true);
        Functions\when('_n_noop')->returnArg();

        RendezVous::register();

        $this->assertSame('rendez_vous', $capture['slug']);
        $this->assertFalse($capture['args']['public']);
        $this->assertTrue($capture['args']['show_ui']);
        $this->assertFalse($capture['args']['publicly_queryable']);
        $this->assertSame(['title'], $capture['args']['supports']);
        $this->assertContains('prophet_en_attente', $statuts);
        $this->assertContains('prophet_confirme', $statuts);
        $this->assertContains('prophet_annule', $statuts);
    }

    public function test_les_colonnes_d_administration_sont_definies(): void
    {
        $colonnes = RendezVous::adminColumns([]);

        $this->assertSame(
            ['cb', 'title', 'rdv_date', 'rdv_type', 'rdv_statut'],
            array_keys($colonnes)
        );
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter RendezVousTest"
```

Attendu : ÉCHEC — classe `RendezVous` introuvable.

- [ ] **Step 3: Implémenter le type de contenu**

`cms/web/app/mu-plugins/prophet-core/src/PostTypes/RendezVous.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\PostTypes;

final class RendezVous
{
    public const SLUG = 'rendez_vous';

    public const STATUT_EN_ATTENTE = 'prophet_en_attente';
    public const STATUT_CONFIRME = 'prophet_confirme';
    public const STATUT_ANNULE = 'prophet_annule';

    /**
     * Carbon Fields préfixe toute méta de publication d'un `_`, sans possibilité
     * de le désactiver. Toute lecture ou écriture passe par metaKey() : une clé
     * littérale écrite ailleurs créerait un second jeu de données que
     * l'administration n'afficherait pas.
     */
    public const META_PREFIX = '_rdv_';

    public static function metaKey(string $champ): string
    {
        return self::META_PREFIX . $champ;
    }

    private const STATUTS = [
        self::STATUT_EN_ATTENTE => 'En attente',
        self::STATUT_CONFIRME => 'Confirmé',
        self::STATUT_ANNULE => 'Annulé',
    ];

    public static function register(): void
    {
        add_action('init', static function (): void {
            register_post_type(self::SLUG, [
                'labels' => [
                    'name' => 'Rendez-vous',
                    'singular_name' => 'Rendez-vous',
                    'edit_item' => 'Détail du rendez-vous',
                    'search_items' => 'Rechercher un rendez-vous',
                ],
                'public' => false,
                'show_ui' => true,
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'menu_icon' => 'dashicons-calendar-alt',
                'supports' => ['title'],
                'capabilities' => ['create_posts' => 'do_not_allow'],
                'map_meta_cap' => true,
            ]);

            foreach (self::STATUTS as $slug => $libelle) {
                register_post_status($slug, [
                    'label' => $libelle,
                    'public' => false,
                    'internal' => false,
                    'show_in_admin_all_list' => true,
                    'show_in_admin_status_list' => true,
                    'label_count' => _n_noop(
                        $libelle . ' (%s)',
                        $libelle . ' (%s)'
                    ),
                ]);
            }
        });

        add_filter('manage_' . self::SLUG . '_posts_columns', [self::class, 'adminColumns']);
        add_action('manage_' . self::SLUG . '_posts_custom_column', [self::class, 'renderColumn'], 10, 2);
    }

    public static function adminColumns(array $colonnes): array
    {
        return [
            'cb' => $colonnes['cb'] ?? '<input type="checkbox" />',
            'title' => 'Demandeur',
            'rdv_date' => 'Date et heure',
            'rdv_type' => 'Consultation',
            'rdv_statut' => 'Statut',
        ];
    }

    public static function renderColumn(string $colonne, int $postId): void
    {
        switch ($colonne) {
            case 'rdv_date':
                echo esc_html(
                    get_post_meta($postId, self::metaKey('date'), true) . ' — '
                    . get_post_meta($postId, self::metaKey('heure'), true)
                );
                break;
            case 'rdv_type':
                echo esc_html((string) get_post_meta($postId, self::metaKey('type_consultation'), true));
                break;
            case 'rdv_statut':
                echo esc_html(self::STATUTS[get_post_status($postId)] ?? '—');
                break;
        }
    }

    public static function statutLabel(string $statut): string
    {
        return self::STATUTS[$statut] ?? $statut;
    }
}
```

- [ ] **Step 4: Déclarer les champs en lecture seule**

`cms/web/app/mu-plugins/prophet-core/src/Fields/RendezVousFields.php` — les valeurs sont écrites par le formulaire public ; l'administration les affiche pour consultation et correction ponctuelle :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use ProphetCore\PostTypes\RendezVous;

final class RendezVousFields
{
    public static function register(): void
    {
        add_action('carbon_fields_register_fields', static function (): void {
            Container::make('post_meta', 'Demande')
                ->where('post_type', '=', RendezVous::SLUG)
                ->add_fields([
                    Field::make('text', 'rdv_prenom', 'Prénom'),
                    Field::make('text', 'rdv_nom', 'Nom'),
                    Field::make('text', 'rdv_email', 'Email'),
                    Field::make('text', 'rdv_telephone', 'Téléphone'),
                    Field::make('text', 'rdv_pays', 'Pays'),
                    Field::make('date', 'rdv_date', 'Date')->set_storage_format('Y-m-d'),
                    Field::make('text', 'rdv_heure', 'Heure'),
                    Field::make('text', 'rdv_type_consultation', 'Type de consultation'),
                    Field::make('text', 'rdv_mode_paiement', 'Mode de paiement'),
                    Field::make('textarea', 'rdv_message', 'Message'),
                    Field::make('text', 'rdv_ref', 'Référence')
                        ->set_attribute('readOnly', true),
                ]);
        });
    }
}
```

- [ ] **Step 5: Brancher dans le mu-plugin**

Ajouter à `prophet-core.php` :

```php
use ProphetCore\Fields\RendezVousFields;
use ProphetCore\PostTypes\RendezVous;

RendezVous::register();
RendezVousFields::register();
```

- [ ] **Step 6: Lancer les tests et vérifier qu'ils passent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit"
```

Attendu : toute la suite au vert.

- [ ] **Step 7: Vérifier dans WordPress**

```bash
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp post-type list --fields=name --format=csv | grep rendez_vous
```

Attendu : `rendez_vous`. Vérifier ensuite dans l'administration que le menu « Rendez-vous » existe et qu'aucun bouton « Ajouter » n'y figure.

- [ ] **Step 8: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/PostTypes
git commit -m "feat(core): type de contenu rendez-vous avec statuts et colonnes d'administration"
```

---
### Task 11: Validation d'une demande de rendez-vous

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Rdv/ValidationResult.php`
- Create: `cms/web/app/mu-plugins/prophet-core/src/Rdv/Validator.php`
- Create: `cms/tests/Unit/Rdv/ValidatorTest.php`

**Interfaces:**
- Consumes: rien
- Produces:
  - `new Validator(array $heures, array $typesAutorises, array $modesAutorises, ?DateTimeImmutable $maintenant = null)`
  - `->validate(array $input): ValidationResult`
  - `ValidationResult::isValid(): bool`, `->errors(): array<string, string>`, `->data(): array`
  - `$input` : clés brutes du formulaire — `nom`, `prenom`, `email`, `telephone`, `pays`, `date` (`Y-m-d`), `heure`, `type_consultation`, `mode_paiement`, `message`
  - `data()` renvoie les mêmes clés, `date` convertie en `DateTimeImmutable`

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Rdv/ValidatorTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Rdv;

use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\Rdv\Validator;
use ProphetCore\Tests\TestCase;

final class ValidatorTest extends TestCase
{
    private function validator(): Validator
    {
        return new Validator(
            ['08h00', '09h00', '10h00'],
            ['Mariage', 'Anges', 'Santé'],
            ['mobile_money', 'virement', 'paypal', 'crypto'],
            new DateTimeImmutable('2026-07-30 12:00:00', new DateTimeZone('UTC'))
        );
    }

    private function valide(array $surcharges = []): array
    {
        return array_merge([
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane@example.test',
            'telephone' => '+22890000000',
            'pays' => 'Togo',
            'date' => '2026-08-05',       // mercredi, dans le futur
            'heure' => '09h00',
            'type_consultation' => 'Mariage',
            'mode_paiement' => 'mobile_money',
            'message' => 'Merci.',
        ], $surcharges);
    }

    public function test_une_demande_complete_est_valide_et_la_date_est_convertie(): void
    {
        $resultat = $this->validator()->validate($this->valide());

        $this->assertTrue($resultat->isValid());
        $this->assertSame([], $resultat->errors());
        $this->assertInstanceOf(DateTimeImmutable::class, $resultat->data()['date']);
        $this->assertSame('2026-08-05', $resultat->data()['date']->format('Y-m-d'));
    }

    public function test_le_nom_et_le_prenom_exigent_deux_caracteres(): void
    {
        $resultat = $this->validator()->validate($this->valide(['nom' => 'D', 'prenom' => 'J']));

        $this->assertSame(
            'Le nom doit contenir au moins 2 caractères',
            $resultat->errors()['nom']
        );
        $this->assertSame(
            'Le prénom doit contenir au moins 2 caractères',
            $resultat->errors()['prenom']
        );
    }

    public function test_l_email_doit_etre_valide(): void
    {
        $resultat = $this->validator()->validate($this->valide(['email' => 'pas-un-email']));

        $this->assertSame('Adresse email invalide', $resultat->errors()['email']);
    }

    public function test_le_telephone_exige_huit_caracteres(): void
    {
        $resultat = $this->validator()->validate($this->valide(['telephone' => '+228']));

        $this->assertSame('Numéro de téléphone invalide', $resultat->errors()['telephone']);
    }

    public function test_le_pays_est_obligatoire(): void
    {
        $resultat = $this->validator()->validate($this->valide(['pays' => '']));

        $this->assertSame(
            'Veuillez indiquer votre pays de résidence',
            $resultat->errors()['pays']
        );
    }

    public function test_le_dimanche_est_refuse(): void
    {
        $resultat = $this->validator()->validate($this->valide(['date' => '2026-08-02']));

        $this->assertSame('Pas de rendez-vous le dimanche', $resultat->errors()['date']);
    }

    public function test_une_date_passee_est_refusee(): void
    {
        $resultat = $this->validator()->validate($this->valide(['date' => '2026-07-20']));

        $this->assertSame('La date doit être dans le futur', $resultat->errors()['date']);
    }

    public function test_le_jour_meme_est_refuse(): void
    {
        $resultat = $this->validator()->validate($this->valide(['date' => '2026-07-30']));

        $this->assertSame('La date doit être dans le futur', $resultat->errors()['date']);
    }

    public function test_une_date_illisible_est_refusee(): void
    {
        $resultat = $this->validator()->validate($this->valide(['date' => 'n\'importe quoi']));

        $this->assertSame('Veuillez sélectionner une date', $resultat->errors()['date']);
    }

    public function test_l_heure_doit_faire_partie_des_creneaux_configures(): void
    {
        $resultat = $this->validator()->validate($this->valide(['heure' => '23h00']));

        $this->assertSame('Veuillez sélectionner une heure', $resultat->errors()['heure']);
    }

    public function test_le_type_de_consultation_doit_exister(): void
    {
        $resultat = $this->validator()->validate($this->valide(['type_consultation' => 'Inconnu']));

        $this->assertSame(
            'Veuillez choisir un type de consultation',
            $resultat->errors()['type_consultation']
        );
    }

    public function test_une_inscription_a_un_evenement_est_acceptee(): void
    {
        $resultat = $this->validator()->validate(
            $this->valide(['type_consultation' => 'Inscription — Retraite Spirituelle'])
        );

        $this->assertTrue($resultat->isValid());
    }

    public function test_le_mode_de_paiement_doit_faire_partie_des_modes_configures(): void
    {
        $resultat = $this->validator()->validate($this->valide(['mode_paiement' => 'especes']));

        $this->assertSame(
            'Veuillez sélectionner un mode de paiement',
            $resultat->errors()['mode_paiement']
        );
    }

    public function test_le_message_est_facultatif_mais_plafonne_a_500_caracteres(): void
    {
        $court = $this->validator()->validate($this->valide(['message' => '']));
        $long = $this->validator()->validate($this->valide(['message' => str_repeat('a', 501)]));

        $this->assertTrue($court->isValid());
        $this->assertSame(
            'Le message ne peut pas dépasser 500 caractères',
            $long->errors()['message']
        );
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter ValidatorTest"
```

Attendu : ÉCHEC — classe `Validator` introuvable.

- [ ] **Step 3: Implémenter `ValidationResult`**

`cms/web/app/mu-plugins/prophet-core/src/Rdv/ValidationResult.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Rdv;

final class ValidationResult
{
    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $errors,
        private readonly array $data,
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return $this->data;
    }
}
```

- [ ] **Step 4: Implémenter `Validator`**

`cms/web/app/mu-plugins/prophet-core/src/Rdv/Validator.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Rdv;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/** Règles reprises de lib/validations.ts:38-63. */
final class Validator
{
    public const PREFIXE_EVENEMENT = 'Inscription — ';

    private DateTimeImmutable $maintenant;

    /**
     * @param array<int, string> $heures
     * @param array<int, string> $typesAutorises
     * @param array<int, string> $modesAutorises
     */
    public function __construct(
        private readonly array $heures,
        private readonly array $typesAutorises,
        private readonly array $modesAutorises,
        ?DateTimeImmutable $maintenant = null,
    ) {
        $this->maintenant = $maintenant ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function validate(array $input): ValidationResult
    {
        $errors = [];
        $data = [];

        $data['nom'] = trim((string) ($input['nom'] ?? ''));
        if (mb_strlen($data['nom']) < 2) {
            $errors['nom'] = 'Le nom doit contenir au moins 2 caractères';
        }

        $data['prenom'] = trim((string) ($input['prenom'] ?? ''));
        if (mb_strlen($data['prenom']) < 2) {
            $errors['prenom'] = 'Le prénom doit contenir au moins 2 caractères';
        }

        $data['email'] = trim((string) ($input['email'] ?? ''));
        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Adresse email invalide';
        }

        $data['telephone'] = trim((string) ($input['telephone'] ?? ''));
        if (mb_strlen($data['telephone']) < 8) {
            $errors['telephone'] = 'Numéro de téléphone invalide';
        }

        $data['pays'] = trim((string) ($input['pays'] ?? ''));
        if ($data['pays'] === '') {
            $errors['pays'] = 'Veuillez indiquer votre pays de résidence';
        }

        $date = $this->parseDate((string) ($input['date'] ?? ''));
        if ($date === null) {
            $errors['date'] = 'Veuillez sélectionner une date';
        } elseif ((int) $date->format('w') === 0) {
            $errors['date'] = 'Pas de rendez-vous le dimanche';
        } elseif ($date <= $this->maintenant->setTime(0, 0)) {
            $errors['date'] = 'La date doit être dans le futur';
        } else {
            $data['date'] = $date;
        }

        $data['heure'] = trim((string) ($input['heure'] ?? ''));
        if (! in_array($data['heure'], $this->heures, true)) {
            $errors['heure'] = 'Veuillez sélectionner une heure';
        }

        $data['type_consultation'] = trim((string) ($input['type_consultation'] ?? ''));
        if (! $this->typeAutorise($data['type_consultation'])) {
            $errors['type_consultation'] = 'Veuillez choisir un type de consultation';
        }

        $data['mode_paiement'] = trim((string) ($input['mode_paiement'] ?? ''));
        if (! in_array($data['mode_paiement'], $this->modesAutorises, true)) {
            $errors['mode_paiement'] = 'Veuillez sélectionner un mode de paiement';
        }

        $data['message'] = trim((string) ($input['message'] ?? ''));
        if (mb_strlen($data['message']) > 500) {
            $errors['message'] = 'Le message ne peut pas dépasser 500 caractères';
        }

        return new ValidationResult($errors, $data);
    }

    private function parseDate(string $raw): ?DateTimeImmutable
    {
        if ($raw === '') {
            return null;
        }

        try {
            $date = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $raw,
                $this->maintenant->getTimezone()
            );
        } catch (Throwable) {
            return null;
        }

        return $date === false ? null : $date;
    }

    private function typeAutorise(string $type): bool
    {
        if (in_array($type, $this->typesAutorises, true)) {
            return true;
        }

        // Préremplissage depuis un événement — voir EventsSection.vue:14-25.
        return str_starts_with($type, self::PREFIXE_EVENEMENT)
            && mb_strlen($type) > mb_strlen(self::PREFIXE_EVENEMENT);
    }
}
```

- [ ] **Step 5: Lancer les tests et vérifier qu'ils passent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter ValidatorTest"
```

Attendu : PASS, 14 tests.

- [ ] **Step 6: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core/src/Rdv cms/tests/Unit/Rdv
git commit -m "feat(core): validation d'une demande de rendez-vous"
```

---

### Task 12: Persistance des rendez-vous et stockage temporaire des erreurs

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Rdv/Repository.php`
- Create: `cms/web/app/mu-plugins/prophet-core/src/Rdv/FlashStore.php`
- Create: `cms/tests/Unit/Rdv/RepositoryTest.php`, `cms/tests/Unit/Rdv/FlashStoreTest.php`

**Interfaces:**
- Consumes: `RendezVous::SLUG` et ses statuts (tâche 10)
- Produces:
  - `Repository::slotTaken(string $dateYmd, string $heure): bool`
  - `Repository::create(array $data): string` — renvoie la référence publique
  - `Repository::findByRef(string $ref): ?array` — renvoie un `$rdv` avec `date` en `DateTimeImmutable`
  - `FlashStore::put(array $payload): string` — renvoie la clé
  - `FlashStore::pull(string $key): ?array` — lit puis supprime
  - `FlashStore::TTL = 300`

- [ ] **Step 1: Écrire les tests qui échouent**

`cms/tests/Unit/Rdv/FlashStoreTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Rdv;

use Brain\Monkey\Functions;
use ProphetCore\Rdv\FlashStore;
use ProphetCore\Tests\TestCase;

final class FlashStoreTest extends TestCase
{
    public function test_la_cle_est_aleatoire_et_le_contenu_stocke_avec_un_ttl_court(): void
    {
        $capture = [];

        Functions\when('wp_generate_password')->justReturn('CLE123');
        Functions\when('set_transient')->alias(function ($key, $value, $ttl) use (&$capture) {
            $capture = compact('key', 'value', 'ttl');

            return true;
        });

        $cle = FlashStore::put(['errors' => ['nom' => 'requis']]);

        $this->assertSame('CLE123', $cle);
        $this->assertSame('prophet_flash_CLE123', $capture['key']);
        $this->assertSame(300, $capture['ttl']);
        $this->assertSame(['errors' => ['nom' => 'requis']], $capture['value']);
    }

    public function test_la_lecture_supprime_le_transient(): void
    {
        $supprime = null;

        Functions\when('get_transient')->justReturn(['errors' => []]);
        Functions\when('delete_transient')->alias(function ($key) use (&$supprime) {
            $supprime = $key;

            return true;
        });

        $payload = FlashStore::pull('CLE123');

        $this->assertSame(['errors' => []], $payload);
        $this->assertSame('prophet_flash_CLE123', $supprime);
    }

    public function test_une_cle_inconnue_renvoie_null(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('delete_transient')->justReturn(true);

        $this->assertNull(FlashStore::pull('INCONNUE'));
    }
}
```

`cms/tests/Unit/Rdv/RepositoryTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Rdv;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\PostTypes\RendezVous;
use ProphetCore\Rdv\Repository;
use ProphetCore\Tests\TestCase;

final class RepositoryTest extends TestCase
{
    public function test_un_creneau_deja_pris_est_detecte(): void
    {
        Functions\when('get_posts')->justReturn([42]);

        $this->assertTrue((new Repository())->slotTaken('2026-08-05', '09h00'));
    }

    public function test_un_creneau_libre_n_est_pas_detecte(): void
    {
        Functions\when('get_posts')->justReturn([]);

        $this->assertFalse((new Repository())->slotTaken('2026-08-05', '09h00'));
    }

    public function test_la_recherche_exclut_les_rendez_vous_annules(): void
    {
        $capture = [];

        Functions\when('get_posts')->alias(function ($args) use (&$capture) {
            $capture = $args;

            return [];
        });

        (new Repository())->slotTaken('2026-08-05', '09h00');

        $this->assertSame(RendezVous::SLUG, $capture['post_type']);
        $this->assertSame(
            [RendezVous::STATUT_EN_ATTENTE, RendezVous::STATUT_CONFIRME],
            $capture['post_status']
        );
    }

    public function test_la_creation_enregistre_les_metadonnees_et_renvoie_une_reference(): void
    {
        $metas = [];

        Functions\when('wp_generate_password')->justReturn('REF0123456789');
        Functions\when('wp_insert_post')->justReturn(7);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('update_post_meta')->alias(function ($id, $key, $value) use (&$metas) {
            $metas[$key] = $value;

            return true;
        });

        $ref = (new Repository())->create([
            'nom' => 'Doe', 'prenom' => 'Jane', 'email' => 'jane@example.test',
            'telephone' => '+22890000000', 'pays' => 'Togo',
            'date' => new DateTimeImmutable('2026-08-05', new DateTimeZone('UTC')),
            'heure' => '09h00', 'type_consultation' => 'Mariage',
            'mode_paiement' => 'mobile_money', 'message' => 'Merci.',
        ]);

        $this->assertSame('REF0123456789', $ref);
        $this->assertSame('2026-08-05', $metas['_rdv_date']);
        $this->assertSame('09h00', $metas['_rdv_heure']);
        $this->assertSame('Mariage', $metas['_rdv_type_consultation']);
        $this->assertSame('REF0123456789', $metas['_rdv_ref']);

        // Le préfixe de Carbon Fields n'est pas cosmétique : sans lui, le
        // panneau d'administration du rendez-vous s'affiche vide.
        foreach (array_keys($metas) as $cle) {
            $this->assertStringStartsWith('_rdv_', $cle);
        }
    }

    public function test_une_reference_inconnue_renvoie_null(): void
    {
        Functions\when('get_posts')->justReturn([]);

        $this->assertNull((new Repository())->findByRef('INCONNUE'));
    }
}
```

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter 'RepositoryTest|FlashStoreTest'"
```

Attendu : ÉCHEC — classes `Repository` et `FlashStore` introuvables.

- [ ] **Step 3: Implémenter `FlashStore`**

`cms/web/app/mu-plugins/prophet-core/src/Rdv/FlashStore.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Rdv;

/**
 * WordPress n'a pas de session. Ce magasin à usage unique transporte les
 * erreurs de validation et les valeurs saisies au travers de la redirection
 * POST/Redirect/GET, sans cookie.
 */
final class FlashStore
{
    public const TTL = 300;
    private const PREFIXE = 'prophet_flash_';

    public static function put(array $payload): string
    {
        $cle = wp_generate_password(20, false);
        set_transient(self::PREFIXE . $cle, $payload, self::TTL);

        return $cle;
    }

    public static function pull(string $key): ?array
    {
        $payload = get_transient(self::PREFIXE . $key);
        delete_transient(self::PREFIXE . $key);

        return is_array($payload) ? $payload : null;
    }
}
```

- [ ] **Step 4: Implémenter `Repository`**

`cms/web/app/mu-plugins/prophet-core/src/Rdv/Repository.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Rdv;

use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\PostTypes\RendezVous;

final class Repository
{
    /** Règle métier reprise de server/api/rdv.post.ts:41-56. */
    public function slotTaken(string $dateYmd, string $heure): bool
    {
        $existants = get_posts([
            'post_type' => RendezVous::SLUG,
            'post_status' => [RendezVous::STATUT_EN_ATTENTE, RendezVous::STATUT_CONFIRME],
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [
                ['key' => RendezVous::metaKey('date'), 'value' => $dateYmd],
                ['key' => RendezVous::metaKey('heure'), 'value' => $heure],
            ],
        ]);

        return $existants !== [];
    }

    public function create(array $data): string
    {
        $ref = wp_generate_password(32, false);

        $postId = wp_insert_post([
            'post_type' => RendezVous::SLUG,
            'post_status' => RendezVous::STATUT_EN_ATTENTE,
            'post_title' => sprintf(
                '%s %s — %s %s',
                $data['prenom'],
                $data['nom'],
                $data['date']->format('d/m/Y'),
                $data['heure']
            ),
        ], true);

        if (is_wp_error($postId)) {
            throw new \RuntimeException('Création du rendez-vous impossible');
        }

        $metas = [
            'nom' => sanitize_text_field($data['nom']),
            'prenom' => sanitize_text_field($data['prenom']),
            'email' => sanitize_email($data['email']),
            'telephone' => sanitize_text_field($data['telephone']),
            'pays' => sanitize_text_field($data['pays']),
            'date' => $data['date']->format('Y-m-d'),
            'heure' => sanitize_text_field($data['heure']),
            'type_consultation' => sanitize_text_field($data['type_consultation']),
            'mode_paiement' => sanitize_text_field($data['mode_paiement']),
            'message' => sanitize_textarea_field($data['message']),
            'ref' => $ref,
        ];

        // Les clés passent par RendezVous::metaKey() : Carbon Fields impose son
        // préfixe, et une clé littérale ici produirait un rendez-vous que le
        // panneau d'administration afficherait vide.
        foreach ($metas as $champ => $valeur) {
            update_post_meta((int) $postId, RendezVous::metaKey($champ), $valeur);
        }

        return $ref;
    }

    public function findByRef(string $ref): ?array
    {
        $posts = get_posts([
            'post_type' => RendezVous::SLUG,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [['key' => RendezVous::metaKey('ref'), 'value' => $ref]],
        ]);

        if ($posts === []) {
            return null;
        }

        $postId = (int) $posts[0];

        $lire = static fn (string $champ): string => (string) get_post_meta(
            $postId,
            RendezVous::metaKey($champ),
            true
        );

        return [
            'nom' => $lire('nom'),
            'prenom' => $lire('prenom'),
            'email' => $lire('email'),
            'telephone' => $lire('telephone'),
            'pays' => $lire('pays'),
            'date' => new DateTimeImmutable($lire('date'), new DateTimeZone('UTC')),
            'heure' => $lire('heure'),
            'type_consultation' => $lire('type_consultation'),
            'mode_paiement' => $lire('mode_paiement'),
            'message' => $lire('message'),
        ];
    }
}
```

- [ ] **Step 5: Lancer les tests et vérifier qu'ils passent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter 'RepositoryTest|FlashStoreTest'"
```

Attendu : PASS, 8 tests.

- [ ] **Step 6: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core/src/Rdv cms/tests/Unit/Rdv
git commit -m "feat(core): persistance des rendez-vous et transport des erreurs de validation"
```

---

### Task 13: Traitement de la soumission

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Rdv/SubmitHandler.php`
- Create: `cms/tests/Unit/Rdv/SubmitHandlerTest.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/prophet-core.php`

**Interfaces:**
- Consumes: `Validator`, `Repository`, `FlashStore`, `Mailer`, `BladeRenderer`, `Options`, `Whatsapp`
- Produces:
  - `SubmitHandler::ACTION = 'prophet_rdv_submit'`
  - `SubmitHandler::register(): void` — branche `admin_post_` et `admin_post_nopriv_`
  - `->handle(): void` — traite `$_POST`, redirige toujours
  - `SubmitHandler::rateLimitKey(string $ip): string`
  - Les vues consomment ensuite `FlashStore::pull()` avec les clés `errors` (map champ → message), `values` (valeurs saisies) et `global` (message hors champ)

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Rdv/SubmitHandlerTest.php` :

```php
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
            SubmitHandler::rateLimitKey('203.0.113.7')
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
        Functions\when('home_url')->alias(fn ($chemin = '/') => 'https://exemple.test' . $chemin);
        Functions\when('add_query_arg')->alias(
            fn ($args, $url) => $url . '?' . http_build_query($args)
        );
        Functions\when('wp_generate_password')->justReturn('CLE');
        Functions\when('set_transient')->justReturn(true);
        Functions\when('wp_safe_redirect')->alias(function ($url) use (&$redirection) {
            $redirection = $url;
        });
        Functions\expect('wp_insert_post')->never();

        $handler = new SubmitHandler();
        $handler->process(['prophet_nonce' => 'faux'], '203.0.113.7');

        $this->assertStringContainsString('/rdv/?', (string) $redirection);
        $this->assertStringContainsString('e=CLE', (string) $redirection);
    }
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter SubmitHandlerTest"
```

Attendu : ÉCHEC — classe `SubmitHandler` introuvable.

- [ ] **Step 3: Implémenter le gestionnaire**

`cms/web/app/mu-plugins/prophet-core/src/Rdv/SubmitHandler.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Rdv;

use ProphetCore\Options;
use ProphetCore\PostTypes\Service;
use ProphetCore\Services\BladeRenderer;
use ProphetCore\Services\Mailer;
use Throwable;

/**
 * Pas `final` : l'extraction de terminer() (voir plus bas) n'a de sens que si le
 * test peut sous-classer ce gestionnaire pour neutraliser l'appel à exit.
 */
class SubmitHandler
{
    public const ACTION = 'prophet_rdv_submit';
    public const NONCE = 'prophet_rdv';
    public const HONEYPOT = 'prophet_website';
    private const DELAI_ENTRE_ENVOIS = 60;

    public static function register(): void
    {
        $handler = new self();

        add_action('admin_post_' . self::ACTION, [$handler, 'handle']);
        add_action('admin_post_nopriv_' . self::ACTION, [$handler, 'handle']);
    }

    public function handle(): void
    {
        $this->process(
            wp_unslash($_POST),
            (string) ($_SERVER['REMOTE_ADDR'] ?? '')
        );
    }

    public function process(array $post, string $ip): void
    {
        // Les robots reçoivent la page de confirmation générique : aucun signal utile.
        if (self::isBot($post)) {
            $this->redirigerVersFormulaire(['global' => 'Votre demande n\'a pas pu être traitée.'], $post);

            return;
        }

        if (! wp_verify_nonce((string) ($post['prophet_nonce'] ?? ''), self::NONCE)) {
            $this->redirigerVersFormulaire(
                ['global' => 'Votre session a expiré. Merci de renvoyer le formulaire.'],
                $post
            );

            return;
        }

        if ($ip !== '' && get_transient(self::rateLimitKey($ip)) !== false) {
            $this->redirigerVersFormulaire(
                ['global' => 'Vous venez déjà d\'envoyer une demande. Patientez une minute.'],
                $post
            );

            return;
        }

        $validator = new Validator(
            Options::heures(),
            $this->typesDeConsultation(),
            array_column(Options::modesPaiement(), 'value')
        );

        $resultat = $validator->validate($post);

        if (! $resultat->isValid()) {
            $this->redirigerVersFormulaire($resultat->errors(), $post);

            return;
        }

        $data = $resultat->data();
        $repository = new Repository();

        if ($repository->slotTaken($data['date']->format('Y-m-d'), $data['heure'])) {
            $this->redirigerVersFormulaire(
                ['heure' => 'Ce créneau est déjà réservé. Veuillez choisir une autre heure.'],
                $post
            );

            return;
        }

        try {
            $ref = $repository->create($data);
        } catch (Throwable $e) {
            error_log('[RDV] enregistrement en échec : ' . $e->getMessage());
            $this->redirigerVersFormulaire(
                ['global' => 'Une erreur interne est survenue. Veuillez réessayer.'],
                $post
            );

            return;
        }

        if ($ip !== '') {
            set_transient(self::rateLimitKey($ip), 1, self::DELAI_ENTRE_ENVOIS);
        }

        // Les emails ne doivent pas retarder la redirection ; un échec est journalisé
        // sans faire échouer la demande, comme server/api/rdv.post.ts:87-95.
        add_action('shutdown', static function () use ($data): void {
            $mailer = new Mailer(new BladeRenderer());
            $mailer->sendClientConfirmation($data);
            $mailer->sendProphetNotification($data);
        });

        wp_safe_redirect(add_query_arg(['ref' => $ref], home_url('/confirmation/')));
        exit;
    }

    public static function isBot(array $post): bool
    {
        return trim((string) ($post[self::HONEYPOT] ?? '')) !== '';
    }

    public static function rateLimitKey(string $ip): string
    {
        return 'prophet_rdv_ip_' . md5($ip);
    }

    /** @return array<int, string> */
    private function typesDeConsultation(): array
    {
        $titres = get_posts([
            'post_type' => Service::SLUG,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);

        return array_map(static fn ($post): string => (string) $post->post_title, $titres);
    }

    private function redirigerVersFormulaire(array $errors, array $values): void
    {
        unset($values['prophet_nonce'], $values[self::HONEYPOT], $values['action']);

        $global = $errors['global'] ?? '';
        unset($errors['global']);

        $cle = FlashStore::put([
            'errors' => $errors,
            'values' => $values,
            'global' => $global,
        ]);

        wp_safe_redirect(add_query_arg(['e' => $cle], home_url('/rdv/')) . '#formulaire');
        exit;
    }
}
```

`process()` appelle `exit` : dans le test, PHPUnit s'arrête sur le premier `exit`. Pour rendre la classe testable, extraire l'arrêt derrière une méthode surchargeable :

```php
    protected function terminer(): void
    {
        exit;
    }
```

et remplacer chaque `exit;` par `$this->terminer(); return;`. Dans le test, instancier une sous-classe anonyme qui redéfinit `terminer()` en no-op :

```php
$handler = new class () extends SubmitHandler {
    protected function terminer(): void
    {
    }
};
```

Adapter `SubmitHandlerTest::test_un_nonce_invalide_...` en conséquence.

- [ ] **Step 4: Brancher dans le mu-plugin**

Ajouter à `prophet-core.php` :

```php
use ProphetCore\Rdv\SubmitHandler;

add_action('init', static function (): void {
    SubmitHandler::register();
});
```

- [ ] **Step 5: Lancer toute la suite et vérifier qu'elle passe**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit"
```

Attendu : suite complète au vert.

- [ ] **Step 6: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/Rdv
git commit -m "feat(core): traitement de la soumission du formulaire de rendez-vous"
```

---
## Phase D — Rendu

### Task 14: Fondations de rendu — icônes, JavaScript, ossature de page

**Files:**
- Create: `cms/web/app/themes/prophet/resources/views/components/icon.blade.php`
- Create: `cms/web/app/themes/prophet/resources/images/icons/*.svg`
- Modify: `cms/web/app/themes/prophet/resources/js/app.js`
- Modify: `cms/web/app/themes/prophet/resources/views/layouts/app.blade.php`
- Create: `cms/web/app/themes/prophet/resources/views/partials/{navbar,footer,floating-whatsapp}.blade.php`
- Create: `cms/web/app/themes/prophet/resources/css/sections/{_navbar,_footer,_floating-whatsapp}.css`
- Create: `cms/web/app/themes/prophet/app/View/Composers/Content.php`
- Modify: `cms/web/app/themes/prophet/resources/css/app.css`

**Interfaces:**
- Consumes: `Options` (tâche 8)
- Produces:
  - `<x-icon name="heart" class="…" />` — SVG lucide inliné
  - Alpine 3 disponible globalement, `data-observe` déclenchant l'animation `fade-up`
  - Partials `partials.navbar`, `partials.footer`, `partials.floating-whatsapp` incluses par le layout
  - Composer `Content` exposant `$contenu` (tableau des options de la page « Contenu du site ») à toutes les vues

- [ ] **Step 1: Installer Alpine, flatpickr et les icônes**

```bash
cd cms/web/app/themes/prophet
npm install alpinejs flatpickr lucide-static
```

- [ ] **Step 2: Copier les icônes utilisées**

```bash
cd cms/web/app/themes/prophet
mkdir -p resources/images/icons
for i in heart sparkles mic-2 landmark briefcase flame star heart-pulse plane \
         building-2 shopping-bag calendar-days quote play youtube map-pin clock \
         users arrow-right arrow-left chevron-left chevron-right calendar \
         loader-2 send check-circle-2 message-circle home user menu x; do
  cp "node_modules/lucide-static/icons/$i.svg" "resources/images/icons/$i.svg"
done
ls resources/images/icons | wc -l
```

Attendu : 30 fichiers. Si l'un manque, vérifier son nom exact dans `node_modules/lucide-static/icons/`.

- [ ] **Step 3: Écrire le composant d'icône**

`resources/views/components/icon.blade.php` :

```blade
@props(['name', 'class' => '', 'size' => 24])

@php
    $chemin = get_theme_file_path("resources/images/icons/{$name}.svg");
    $svg = is_readable($chemin) ? file_get_contents($chemin) : '';

    if ($svg !== '') {
        $svg = str_replace(
            '<svg',
            sprintf('<svg class="%s" width="%d" height="%d" aria-hidden="true" focusable="false"',
                esc_attr($class), (int) $size, (int) $size),
            $svg
        );
    }
@endphp

{!! $svg !!}
```

Une icône absente rend une chaîne vide plutôt qu'une erreur : la page reste affichable.

- [ ] **Step 4: Écrire le JavaScript**

`resources/js/app.js` :

```js
import Alpine from 'alpinejs'

window.Alpine = Alpine
Alpine.start()

// Reproduit l'animation fade-up déclenchée à l'entrée dans le viewport.
const observer = new IntersectionObserver(
  (entries) => {
    for (const entry of entries) {
      if (!entry.isIntersecting) continue
      entry.target.classList.add('is-visible')
      observer.unobserve(entry.target)
    }
  },
  { threshold: 0.15 }
)

document.querySelectorAll('[data-observe]').forEach((el) => observer.observe(el))
```

- [ ] **Step 5: Écrire le View Composer du contenu global**

`app/View/Composers/Content.php` :

```php
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
                'footer_description' => Options::content('footer_description'),
                'footer_youtube' => Options::content('footer_youtube'),
                'footer_facebook' => Options::content('footer_facebook'),
                'footer_mentions' => Options::content('footer_mentions'),
            ],
            'phone1' => Options::phone1(),
            'phone2' => Options::phone2(),
        ];
    }
}
```

- [ ] **Step 6: Porter la barre de navigation**

Appliquer la **Procédure de port d'une section** avec :

- source : `components/TheNavbar.vue`
- CSS : `resources/css/sections/_navbar.css`, scope `.sec-navbar`
- vue : `resources/views/partials/navbar.blade.php`
- données : `$contenu`, plus les ancres de la page d'accueil (`#services`, `#about`, `#evenements`, `#temoignages`)
- interactivité : l'ouverture du menu mobile et l'état « scrollé » passent en

```blade
<header class="sec-navbar"
        x-data="{ ouvert: false, scrolle: false }"
        x-on:scroll.window="scrolle = window.scrollY > 40"
        x-bind:class="{ 'is-scrolled': scrolle }">
```

et le panneau mobile en `x-show="ouvert"` avec `x-on:click="ouvert = !ouvert"` sur le bouton.

- [ ] **Step 7: Porter le pied de page et le bouton WhatsApp flottant**

Même procédure :

| Source | CSS / scope | Vue |
|---|---|---|
| `components/AppFooter.vue` | `_footer.css` / `.sec-footer` | `partials/footer.blade.php` |
| `components/FloatingWhatsApp.vue` | `_floating-whatsapp.css` / `.sec-floating-whatsapp` | `partials/floating-whatsapp.blade.php` |

Le lien WhatsApp est construit côté serveur :

```blade
@php($waUrl = \ProphetCore\Services\Whatsapp::url($phone1, 'Bonjour Prophète Jeremiah Nahoum,'))
<a href="{{ esc_url($waUrl) }}" target="_blank" rel="noopener">
```

- [ ] **Step 8: Assembler le layout**

`resources/views/layouts/app.blade.php` :

```blade
<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="{{ get_bloginfo('charset') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php(wp_head())
  </head>
  <body @php(body_class())>
    @include('partials.navbar')

    <main id="main">
      @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.floating-whatsapp')

    @php(wp_footer())
  </body>
</html>
```

- [ ] **Step 9: Déclarer les imports CSS**

`resources/css/app.css` :

```css
@import "./tokens.css";
@import "./sections/_navbar.css";
@import "./sections/_footer.css";
@import "./sections/_floating-whatsapp.css";
```

- [ ] **Step 10: Vérifier**

```bash
cd cms/web/app/themes/prophet && npm run build && cd -
curl -sS http://localhost:8080/ | grep -c 'sec-navbar'
curl -sS http://localhost:8080/ | grep -c 'sec-footer'
```

Attendu : `1` et `1`. Ouvrir ensuite le site à 375 px et 1440 px et comparer la barre de navigation et le pied de page au Nuxt lancé en parallèle (`npm run dev`, port 3000).

- [ ] **Step 11: Commit**

```bash
git add cms/web/app/themes/prophet
git commit -m "feat(theme): icônes, Alpine, layout, barre de navigation et pied de page"
```

---

### Task 15: Sections Hero et Chiffres clés

**Files:**
- Create: `cms/web/app/themes/prophet/resources/views/front-page.blade.php`
- Create: `cms/web/app/themes/prophet/resources/views/sections/{hero,stats}.blade.php`
- Create: `cms/web/app/themes/prophet/resources/css/sections/{_hero,_stats}.css`
- Create: `cms/web/app/themes/prophet/app/View/Composers/Stats.php`
- Modify: `cms/web/app/themes/prophet/resources/css/app.css`

**Interfaces:**
- Consumes: `$contenu`, `$phone1` (tâche 14)
- Produces: `front-page.blade.php` incluant les sections au fur et à mesure des tâches 15 à 18 ; `$stats` — tableau de `['valeur' => string, 'libelle' => string]`

- [ ] **Step 1: Créer la page d'accueil**

`resources/views/front-page.blade.php` :

```blade
@extends('layouts.app')

@section('content')
  @include('sections.hero')
  @include('sections.stats')
@endsection
```

Les tâches 16 à 18 ajoutent leurs `@include` à la suite, dans l'ordre de `pages/index.vue`.

Porter aussi le bloc `<style>` **non scopé** de `pages/index.vue` (la classe `.site`)
vers `resources/css/app.css`, sans enveloppe de section : ces règles sont globales à
la page d'accueil et le scoping par section les casserait.

- [ ] **Step 2: Écrire le composer des chiffres**

`app/View/Composers/Stats.php` :

```php
<?php

declare(strict_types=1);

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Stats extends Composer
{
    protected static $views = ['sections.stats'];

    public function with(): array
    {
        $rows = carbon_get_theme_option('stats');

        return [
            'stats' => is_array($rows) ? $rows : [],
        ];
    }
}
```

- [ ] **Step 3: Porter la section Hero**

Appliquer la **Procédure de port d'une section** avec :

- source : `components/HeroSection.vue` (517 lignes, dont 408 de CSS — la plus grosse section)
- CSS : `resources/css/sections/_hero.css`, scope `.sec-hero`
- vue : `resources/views/sections/hero.blade.php`
- données : `$contenu['hero_*']`, image via `wp_get_attachment_image_url(carbon_get_theme_option('hero_image'), 'full')` avec repli sur `@asset('resources/images/prophet-main.jpg')`
- les boutons pointent vers `{{ home_url('/rdv') }}` et l'ancre `#services`

- [ ] **Step 4: Porter la section Chiffres clés**

- source : `components/StatsBar.vue`
- CSS : `_stats.css`, scope `.sec-stats`
- vue : `sections/stats.blade.php`, boucle `@foreach ($stats as $stat)` sur `$stat['valeur']` et `$stat['libelle']`

- [ ] **Step 5: Déclarer les imports CSS**

Ajouter à `resources/css/app.css` :

```css
@import "./sections/_hero.css";
@import "./sections/_stats.css";
```

- [ ] **Step 6: Alimenter les options pour pouvoir comparer**

```bash
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp eval '
  carbon_set_theme_option("hero_surtitre", "Le Conseiller des Rois");
  carbon_set_theme_option("hero_titre", "Prophète Jeremiah Nahoum");
  carbon_set_theme_option("stats", [
    ["valeur" => "15+", "libelle" => "Ans de ministère"],
    ["valeur" => "40+", "libelle" => "Nations touchées"],
    ["valeur" => "10K+", "libelle" => "Consultations"],
    ["valeur" => "100%", "libelle" => "Parole accomplie"],
  ]);'
```

Les valeurs définitives sont posées par la commande de seed (tâche 21).

- [ ] **Step 7: Vérifier**

```bash
cd cms/web/app/themes/prophet && npm run build && cd -
curl -sS http://localhost:8080/ | grep -c 'sec-hero'
```

Attendu : `1`. Comparer ensuite visuellement les deux sections contre le Nuxt à 375, 768 et 1440 px.

- [ ] **Step 8: Commit**

```bash
git add cms/web/app/themes/prophet
git commit -m "feat(theme): sections hero et chiffres clés"
```

---

### Task 16: Sections À propos et Services

**Files:**
- Create: `cms/web/app/themes/prophet/resources/views/sections/{about,services}.blade.php`
- Create: `cms/web/app/themes/prophet/resources/css/sections/{_about,_services}.css`
- Create: `cms/web/app/themes/prophet/app/View/Composers/Services.php`
- Modify: `cms/web/app/themes/prophet/resources/views/front-page.blade.php`
- Modify: `cms/web/app/themes/prophet/resources/css/app.css`

**Interfaces:**
- Consumes: CPT `service` et ses champs (tâche 9)
- Produces: `$services` — tableau de `['titre' => string, 'description' => string, 'icone' => string, 'couleur' => string]`, réutilisé par la tâche 19 pour la liste des types de consultation

- [ ] **Step 1: Écrire le composer des services**

`app/View/Composers/Services.php` :

```php
<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\PostTypes\Service;
use Roots\Acorn\View\Composer;

class Services extends Composer
{
    protected static $views = ['sections.services', 'partials.rdv-form'];

    public function with(): array
    {
        return ['services' => self::all()];
    }

    /** @return array<int, array{titre: string, description: string, icone: string, couleur: string}> */
    public static function all(): array
    {
        $posts = get_posts([
            'post_type' => Service::SLUG,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);

        return array_map(static fn ($post): array => [
            'titre' => (string) $post->post_title,
            'description' => (string) $post->post_content,
            'icone' => (string) carbon_get_post_meta($post->ID, 'service_icone'),
            'couleur' => (string) carbon_get_post_meta($post->ID, 'service_couleur'),
        ], $posts);
    }
}
```

- [ ] **Step 2: Porter la section Services**

Appliquer la **Procédure de port d'une section** :

- source : `components/ServicesSection.vue`
- CSS : `_services.css`, scope `.sec-services`
- vue : `sections/services.blade.php`
- boucle : `@foreach ($services as $service)`, l'icône devient `<x-icon :name="$service['icone']" />` et la couleur `style="--service-color: {{ esc_attr($service['couleur']) }}"`
- si le CSS d'origine liait la couleur par une classe, la remplacer par cette variable CSS locale et adapter la règle correspondante dans `_services.css` — c'est la seule modification de CSS autorisée dans cette tâche, à documenter en commentaire

- [ ] **Step 3: Porter la section À propos**

- source : `components/AboutSection.vue`
- CSS : `_about.css`, scope `.sec-about`
- vue : `sections/about.blade.php`
- données : `$contenu['about_titre']`, `$contenu['about_texte']` rendu par `{!! $contenu['about_texte'] !!}` (champ `rich_text`, déjà assaini par WordPress), image via `wp_get_attachment_image_url(carbon_get_theme_option('about_image'), 'full')`

- [ ] **Step 4: Brancher dans la page d'accueil et le CSS**

`front-page.blade.php` :

```blade
  @include('sections.hero')
  @include('sections.stats')
  @include('sections.about')
  @include('sections.services')
```

`app.css` : ajouter `@import "./sections/_about.css";` et `@import "./sections/_services.css";`.

- [ ] **Step 5: Créer des services de test**

```bash
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp eval '
  $id = wp_insert_post(["post_type" => "service", "post_status" => "publish",
    "post_title" => "Mariage", "menu_order" => 1,
    "post_content" => "Guidance prophétique pour trouver votre partenaire de vie selon la volonté de Dieu."]);
  carbon_set_post_meta($id, "service_icone", "heart");
  carbon_set_post_meta($id, "service_couleur", "#c2185b");'
```

- [ ] **Step 6: Vérifier**

```bash
cd cms/web/app/themes/prophet && npm run build && cd -
curl -sS http://localhost:8080/ | grep -c 'sec-services'
curl -sS http://localhost:8080/ | grep -c 'Mariage'
```

Attendu : `1` et ≥ `1`. Comparer visuellement les deux sections au Nuxt.

- [ ] **Step 7: Commit**

```bash
git add cms/web/app/themes/prophet
git commit -m "feat(theme): sections à propos et services"
```

---

### Task 17: Sections Événements et Vidéos

**Files:**
- Create: `cms/web/app/themes/prophet/resources/views/sections/{events,videos}.blade.php`
- Create: `cms/web/app/themes/prophet/resources/css/sections/{_events,_videos}.css`
- Create: `cms/web/app/themes/prophet/app/View/Composers/{Events,Videos}.php`
- Modify: `cms/web/app/themes/prophet/resources/views/front-page.blade.php`
- Modify: `cms/web/app/themes/prophet/resources/css/app.css`

**Interfaces:**
- Consumes: `GoogleCalendar` (tâche 6), `Youtube` (tâche 7), `Options::env()` (tâche 8)
- Produces: `$events` (forme de la tâche 6, plus une clé `lien`), `$videos` (forme de la tâche 7), `$channelUrl`

- [ ] **Step 1: Écrire le composer des événements**

`app/View/Composers/Events.php` :

```php
<?php

declare(strict_types=1);

namespace App\View\Composers;

use DateTimeImmutable;
use DateTimeZone;
use ProphetCore\Options;
use ProphetCore\Rdv\Validator;
use ProphetCore\Services\GoogleCalendar;
use ProphetCore\Support\Date;
use Roots\Acorn\View\Composer;

class Events extends Composer
{
    protected static $views = ['sections.events'];

    public function with(): array
    {
        $service = new GoogleCalendar(
            Options::env('GOOGLE_API_KEY'),
            Options::env('GOOGLE_CALENDAR_ID')
        );

        $events = array_map(
            static fn (array $event): array => $event + ['lien' => self::lien($event)],
            $service->events()
        );

        return [
            'events' => $events,
            'pageSize' => 4,
        ];
    }

    /** Reproduit EventsSection.vue:14-25. */
    private static function lien(array $event): string
    {
        if ($event['places'] !== 'Sur inscription') {
            return home_url('/rdv');
        }

        $mois = array_search($event['month'], array_map(
            static fn (int $i): string => Date::monthAbbrFr($i),
            range(0, 11)
        ), true);

        $date = new DateTimeImmutable(
            sprintf('%s-%02d-%02d', $event['year'], (int) $mois + 1, (int) $event['day']),
            new DateTimeZone('UTC')
        );

        return add_query_arg([
            'event' => $event['title'],
            'eventDate' => $date->format('Y-m-d'),
            'eventLieu' => $event['lieu'],
            'eventHeure' => $event['heure'],
        ], home_url('/rdv'));
    }
}
```

Le paramètre `eventDate` passe désormais au format `Y-m-d` — celui qu'attend le validateur (tâche 11) et flatpickr (tâche 19), au lieu de l'ISO complet du Nuxt.

- [ ] **Step 2: Écrire le composer des vidéos**

`app/View/Composers/Videos.php` :

```php
<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\Options;
use ProphetCore\Services\Youtube;
use Roots\Acorn\View\Composer;

class Videos extends Composer
{
    protected static $views = ['sections.videos'];

    public function with(): array
    {
        return [
            'videos' => (new Youtube(
                Options::env('YOUTUBE_API_KEY'),
                Options::env('YOUTUBE_CHANNEL_ID')
            ))->videos(),
            'channelUrl' => 'https://www.youtube.com/@ProphetJeremiahNahoum',
        ];
    }
}
```

- [ ] **Step 3: Porter la section Événements**

Appliquer la **Procédure de port d'une section** :

- source : `components/EventsSection.vue`
- CSS : `_events.css`, scope `.sec-events`
- vue : `sections/events.blade.php`
- la pagination (4 par page, `EventsSection.vue:44-54`) devient un état Alpine sur la liste complète rendue côté serveur :

```blade
<section class="sec-events" id="evenements"
         x-data="{ page: 1, taille: {{ $pageSize }}, total: {{ (int) ceil(count($events) / $pageSize) }} }">
  @foreach ($events as $i => $event)
    <article x-show="Math.floor({{ $i }} / taille) + 1 === page" …>
  @endforeach
  <button x-on:click="page = Math.max(1, page - 1)" x-bind:disabled="page === 1">…</button>
  <button x-on:click="page = Math.min(total, page + 1)" x-bind:disabled="page === total">…</button>
</section>
```

- la couleur par type (`typeColor` dans `EventsSection.vue:37-41`) devient une variable CSS posée dans `_events.css` sur `[data-type="Croisade"]`, `[data-type="Conférence"]`, `[data-type="Retraite"]`

- [ ] **Step 4: Porter la section Vidéos**

- source : `components/VideoSection.vue`
- CSS : `_videos.css`, scope `.sec-videos`
- vue : `sections/videos.blade.php`
- le `useFetch` disparaît : `$videos` est déjà résolu côté serveur, donc le squelette de chargement disparaît aussi
- les miniatures pointent vers `{{ esc_url($video['thumbnail']) }}` et le lien vers `https://www.youtube.com/watch?v={{ $video['id'] }}`

- [ ] **Step 5: Brancher dans la page d'accueil et le CSS**

`front-page.blade.php` : ajouter `@include('sections.events')` puis `@include('sections.videos')` après `sections.services`.
`app.css` : ajouter les deux `@import` correspondants.

- [ ] **Step 6: Vérifier**

```bash
cd cms/web/app/themes/prophet && npm run build && cd -
curl -sS http://localhost:8080/ | grep -c 'MOCK'
```

Attendu : ≥ `1` sans clés d'API configurées — le repli sur les données de démonstration fonctionne. Vérifier ensuite la pagination des événements dans le navigateur (4 par page, boutons désactivés aux extrémités) et la comparaison visuelle avec le Nuxt.

- [ ] **Step 7: Commit**

```bash
git add cms/web/app/themes/prophet
git commit -m "feat(theme): sections événements et vidéos alimentées côté serveur"
```

---

### Task 18: Sections Galerie, Témoignages et Appel à l'action

**Files:**
- Create: `cms/web/app/themes/prophet/resources/views/sections/{gallery,testimonials,cta}.blade.php`
- Create: `cms/web/app/themes/prophet/resources/views/partials/testimonial-card.blade.php`
- Create: `cms/web/app/themes/prophet/resources/css/sections/{_gallery,_testimonials,_cta}.css`
- Create: `cms/web/app/themes/prophet/app/View/Composers/{Gallery,Testimonials}.php`
- Modify: `cms/web/app/themes/prophet/resources/views/front-page.blade.php`
- Modify: `cms/web/app/themes/prophet/resources/css/app.css`

**Interfaces:**
- Consumes: CPT `photo` et `temoignage` (tâche 9)
- Produces: `$photos` — `['url' => string, 'legende' => string, 'format' => string]` ; `$temoignages` — `['nom','initiales','pays','consultation','couleur','etoiles','texte']`

- [ ] **Step 1: Écrire les deux composers**

`app/View/Composers/Gallery.php` :

```php
<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\PostTypes\Photo;
use Roots\Acorn\View\Composer;

class Gallery extends Composer
{
    protected static $views = ['sections.gallery'];

    public function with(): array
    {
        $posts = get_posts([
            'post_type' => Photo::SLUG,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);

        return [
            'photos' => array_map(static fn ($post): array => [
                'url' => (string) wp_get_attachment_image_url(
                    (int) carbon_get_post_meta($post->ID, 'photo_image'),
                    'large'
                ),
                'legende' => (string) carbon_get_post_meta($post->ID, 'photo_legende'),
                'format' => (string) carbon_get_post_meta($post->ID, 'photo_format') ?: 'normal',
            ], $posts),
        ];
    }
}
```

`app/View/Composers/Testimonials.php` — même structure sur le CPT `temoignage`, avec :

```php
'temoignages' => array_map(static fn ($post): array => [
    'nom' => (string) $post->post_title,
    'initiales' => (string) carbon_get_post_meta($post->ID, 'temoignage_initiales'),
    'pays' => (string) carbon_get_post_meta($post->ID, 'temoignage_pays'),
    'consultation' => self::serviceLie($post->ID),
    'couleur' => (string) carbon_get_post_meta($post->ID, 'temoignage_couleur'),
    'etoiles' => (int) carbon_get_post_meta($post->ID, 'temoignage_etoiles'),
    'texte' => (string) carbon_get_post_meta($post->ID, 'temoignage_texte'),
], $posts),
```

et une méthode privée :

```php
private static function serviceLie(int $postId): string
{
    $association = carbon_get_post_meta($postId, 'temoignage_service');

    if (! is_array($association) || $association === []) {
        return '';
    }

    return (string) get_the_title((int) $association[0]['id']);
}
```

- [ ] **Step 2: Porter les trois sections**

Appliquer la **Procédure de port d'une section** pour chacune :

| Source | CSS / scope | Vue | Données |
|---|---|---|---|
| `components/GallerySection.vue` | `_gallery.css` / `.sec-gallery` | `sections/gallery.blade.php` | `$photos`, `format` posé en `data-format="{{ $photo['format'] }}"` |
| `components/TestimonialsSection.vue` + `TestimonialCard.vue` | `_testimonials.css` / `.sec-testimonials` | `sections/testimonials.blade.php` + `partials/testimonial-card.blade.php` | `$temoignages` |
| `components/CtaSection.vue` | `_cta.css` / `.sec-cta` | `sections/cta.blade.php` | `$contenu['cta_*']` |

Le CSS de `TestimonialCard.vue` va dans `_testimonials.css` : la carte n'est utilisée que par cette section, un fichier séparé n'apporterait rien.

**Pas de carrousel de témoignages.** Une version antérieure de ce plan en décrivait
un, à tort : `TestimonialsSection.vue` n'a ni `ref`, ni index, ni logique de
défilement — c'est une grille CSS statique en 3, 2 puis 1 colonnes qui affiche tous
les témoignages à la fois. Porter la grille telle quelle, sans Alpine.

- [ ] **Step 3: Brancher dans la page d'accueil et le CSS**

`front-page.blade.php` — ordre final, relevé sur `pages/index.vue:18-26` :

```blade
@extends('layouts.app')

@section('content')
  @include('sections.hero')
  @include('sections.stats')
  @include('sections.about')
  @include('sections.services')
  @include('sections.videos')
  @include('sections.events')
  @include('sections.testimonials')
  @include('sections.gallery')
  @include('sections.cta')
@endsection
```

Cet ordre a été corrigé après coup : une première version du plan intervertissait
vidéos/événements et témoignages/galerie. `pages/index.vue` fait foi, et le préfixe
`Lazy` de certains composants ne change rien à leur position.

`app.css` : ajouter les trois `@import` correspondants.

- [ ] **Step 4: Vérifier**

```bash
cd cms/web/app/themes/prophet && npm run build && cd -
for s in hero stats about services events videos gallery testimonials cta; do
  printf '%s: ' "$s"
  curl -sS http://localhost:8080/ | grep -c "sec-$s"
done
```

Attendu : `1` pour chaque section. Puis comparaison visuelle page entière contre le Nuxt à 375, 768 et 1440 px, en faisant défiler pour vérifier les animations `fade-up`.

- [ ] **Step 5: Commit**

```bash
git add cms/web/app/themes/prophet
git commit -m "feat(theme): sections galerie, témoignages et appel à l'action"
```

---
### Task 19: Page de prise de rendez-vous

**Files:**
- Create: `cms/web/app/themes/prophet/resources/views/template-rdv.blade.php`
- Create: `cms/web/app/themes/prophet/resources/views/partials/{hero-left,rdv-form}.blade.php`
- Create: `cms/web/app/themes/prophet/resources/css/sections/{_hero-left,_rdv-form}.css`
- Create: `cms/web/app/themes/prophet/app/View/Composers/Rdv.php`
- Modify: `cms/web/app/themes/prophet/resources/js/app.js`
- Modify: `cms/web/app/themes/prophet/resources/css/app.css`

**Interfaces:**
- Consumes: `Services::all()` (tâche 16), `Options::heures()` / `modesPaiement()` (tâche 8), `FlashStore::pull()` (tâche 12), `SubmitHandler::ACTION` / `NONCE` / `HONEYPOT` (tâche 13)
- Produces: page `/rdv` fonctionnelle de bout en bout ; `$erreurs`, `$valeurs`, `$erreurGlobale`, `$heures`, `$modes`, `$services`, `$evenement`

- [ ] **Step 1: Écrire le composer de la page**

`app/View/Composers/Rdv.php` :

```php
<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\Options;
use ProphetCore\Rdv\FlashStore;
use ProphetCore\Rdv\Validator;
use Roots\Acorn\View\Composer;

class Rdv extends Composer
{
    protected static $views = ['template-rdv', 'partials.rdv-form'];

    /**
     * FlashStore::pull() supprime le transient à la lecture, et ce composer est
     * enregistré pour deux vues : sans mémorisation, le premier rendu consomme les
     * erreurs et le formulaire s'affiche muet — le visiteur est renvoyé vers un
     * formulaire sans la moindre explication. On ne lit donc qu'une fois par
     * requête.
     */
    private static ?array $flashMemorise = null;

    private static bool $flashDejaLu = false;

    /** @return array{errors?: array, values?: array, global?: string}|null */
    private static function flash(): ?array
    {
        if (! self::$flashDejaLu) {
            self::$flashDejaLu = true;
            self::$flashMemorise = isset($_GET['e'])
                ? FlashStore::pull(sanitize_text_field(wp_unslash($_GET['e'])))
                : null;
        }

        return self::$flashMemorise;
    }

    public function with(): array
    {
        $flash = self::flash();

        return [
            'erreurs' => $flash['errors'] ?? [],
            'valeurs' => $flash['values'] ?? [],
            'erreurGlobale' => $flash['global'] ?? '',
            'heures' => Options::heures(),
            'modes' => Options::modesPaiement(),
            'aide' => Options::content('rdv_aide'),
            'evenement' => $this->evenement(),
        ];
    }

    /** Préremplissage depuis EventsSection — voir EventsSection.vue:14-25. */
    private function evenement(): ?array
    {
        if (empty($_GET['event'])) {
            return null;
        }

        $titre = sanitize_text_field(wp_unslash($_GET['event']));
        $lieu = sanitize_text_field(wp_unslash($_GET['eventLieu'] ?? ''));

        return [
            'titre' => $titre,
            'lieu' => $lieu,
            'heure' => sanitize_text_field(wp_unslash($_GET['eventHeure'] ?? '')),
            'date' => sanitize_text_field(wp_unslash($_GET['eventDate'] ?? '')),
            'type' => Validator::PREFIXE_EVENEMENT . $titre,
            'message' => 'Inscription pour : ' . trim($titre . ' — ' . $lieu, ' —'),
        ];
    }
}
```

- [ ] **Step 2: Porter la colonne de gauche**

Appliquer la **Procédure de port d'une section** :

- source : `components/HeroLeft.vue`
- CSS : `_hero-left.css`, scope `.sec-hero-left`
- vue : `partials/hero-left.blade.php`

- [ ] **Step 3: Porter le formulaire**

- source : `components/RdvForm.vue` (template et CSS) et `components/ConsultationPicker.vue`
- CSS : `_rdv-form.css`, scope `.sec-rdv-form` ; y intégrer le CSS de `ConsultationPicker.vue`, qui n'est utilisé que par ce formulaire
- vue : `partials/rdv-form.blade.php`

Ossature :

```blade
<form class="sec-rdv-form" id="formulaire" method="post"
      action="{{ esc_url(admin_url('admin-post.php')) }}"
      x-data="rdvForm({
        etape: 1,
        type: @js($valeurs['type_consultation'] ?? ($evenement['type'] ?? '')),
        heure: @js($valeurs['heure'] ?? ''),
        paiement: @js($valeurs['mode_paiement'] ?? ''),
        message: @js($valeurs['message'] ?? ($evenement['message'] ?? '')),
      })">

  <input type="hidden" name="action" value="{{ \ProphetCore\Rdv\SubmitHandler::ACTION }}">
  @php(wp_nonce_field(\ProphetCore\Rdv\SubmitHandler::NONCE, 'prophet_nonce'))

  {{-- Pot de miel : masqué visuellement, jamais rempli par un humain. --}}
  <div class="pot-de-miel" aria-hidden="true">
    <label>Site web
      <input type="text" name="{{ \ProphetCore\Rdv\SubmitHandler::HONEYPOT }}" tabindex="-1" autocomplete="off">
    </label>
  </div>

  @if ($erreurGlobale)
    <p class="message-global" role="alert">{{ $erreurGlobale }}</p>
  @endif

  {{-- Un champ, répété pour chacun : nom, prenom, email, telephone, pays --}}
  <label for="nom">Nom</label>
  <input id="nom" type="text" name="nom" value="{{ $valeurs['nom'] ?? '' }}"
         required minlength="2"
         @if (isset($erreurs['nom'])) aria-invalid="true" aria-describedby="erreur-nom" @endif>
  @if (isset($erreurs['nom']))
    <p class="erreur" id="erreur-nom">{{ $erreurs['nom'] }}</p>
  @endif

  {{-- Date : flatpickr se greffe sur cet input --}}
  <input id="date" type="text" name="date" data-flatpickr
         value="{{ $valeurs['date'] ?? ($evenement['date'] ?? '') }}" required>

  {{-- Heures --}}
  @foreach ($heures as $h)
    <label>
      <input type="radio" name="heure" value="{{ $h }}" x-model="heure" required>
      {{ $h }}
    </label>
  @endforeach

  {{-- Types de consultation, repris du CPT service --}}
  @foreach ($services as $service)
    <label>
      <input type="radio" name="type_consultation" value="{{ $service['titre'] }}" x-model="type" required>
      <x-icon :name="$service['icone']" />
      {{ $service['titre'] }}
    </label>
  @endforeach

  @if ($evenement)
    <input type="hidden" name="type_consultation" value="{{ $evenement['type'] }}">
  @endif

  {{-- Modes de paiement --}}
  @foreach ($modes as $mode)
    <label>
      <input type="radio" name="mode_paiement" value="{{ $mode['value'] }}" x-model="paiement" required>
      {{ $mode['label'] }}
    </label>
  @endforeach

  <textarea name="message" maxlength="500" x-model="message"></textarea>
  <span x-text="`${message.length}/500`"></span>

  <button type="submit" x-bind:disabled="envoi" x-on:click="envoi = true">
    <span x-show="!envoi">Envoyer ma demande</span>
    <span x-show="envoi"><x-icon name="loader-2" class="tourne" /> Envoi…</span>
  </button>
</form>
```

Points de vigilance :

- lorsque `$evenement` est présent, la liste des types est masquée et remplacée par le champ caché, comme `RdvForm.vue:35-42` ;
- les erreurs sont affichées sous leur champ, jamais en toast : elles proviennent désormais du serveur ;
- chaque `name` correspond exactement à une clé attendue par `Validator::validate()` (tâche 11).

- [ ] **Step 4: Écrire le composant Alpine et brancher flatpickr**

Ajouter à `resources/js/app.js`, avant `Alpine.start()` :

```js
import flatpickr from 'flatpickr'
import { French } from 'flatpickr/dist/l10n/fr.js'
import 'flatpickr/dist/flatpickr.css'

Alpine.data('rdvForm', (initial) => ({
  ...initial,
  envoi: false,
}))

document.addEventListener('DOMContentLoaded', () => {
  const input = document.querySelector('[data-flatpickr]')
  if (!input) return

  flatpickr(input, {
    locale: French,
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'l j F Y',
    minDate: new Date().fp_incr(1),
    // Pas de rendez-vous le dimanche — même règle que le validateur serveur.
    disable: [(date) => date.getDay() === 0],
  })
})
```

`minDate` à demain reproduit le refus du jour même côté serveur.

- [ ] **Step 5: Écrire le gabarit de page**

`resources/views/template-rdv.blade.php` :

```blade
{{-- Template Name: Rendez-vous --}}

@extends('layouts.app')

@section('content')
  <div class="page-rdv">
    @include('partials.hero-left')
    @include('partials.rdv-form')
  </div>
@endsection
```

Reprendre la mise en page à deux colonnes de `pages/rdv.vue` dans `_rdv-form.css` sous `.page-rdv`.

Le bloc `<style>` **non scopé** de `pages/rdv.vue` (`.rdv-page`, `.rdv-back`,
`.rdv-layout*`) est global à la page : le porter dans un fichier
`resources/css/sections/_page-rdv.css` importé **sans** enveloppe `.sec-*`, puisque
le scoping par section l'empêcherait d'atteindre ses cibles.

- [ ] **Step 6: Créer la page dans WordPress**

```bash
docker compose -f docker-compose.cms.dev.yml exec -T wpcli \
  wp post create --post_type=page --post_title="Prendre rendez-vous" \
    --post_name=rdv --post_status=publish \
    --page_template=template-rdv.blade.php --porcelain
```

- [ ] **Step 7: Ajouter les imports CSS**

```css
@import "./sections/_hero-left.css";
@import "./sections/_rdv-form.css";
```

- [ ] **Step 8: Vérifier le parcours complet**

```bash
cd cms/web/app/themes/prophet && npm run build && cd -
curl -sS http://localhost:8080/rdv/ | grep -c 'admin-post.php'
```

Attendu : `1`.

Puis dans le navigateur, en enchaînant :

1. envoyer le formulaire vide → les messages d'erreur apparaissent sous les champs, les valeurs saisies sont conservées ;
2. choisir un dimanche dans flatpickr → impossible ;
3. envoyer une demande valide → redirection vers `/confirmation?ref=…` (page blanche tant que la tâche 20 n'est pas faite, c'est attendu) ;
4. renvoyer la même date et la même heure → « Ce créneau est déjà réservé. » ;
5. vérifier dans l'administration que le rendez-vous apparaît avec le statut « En attente » ;
6. renvoyer une demande dans la minute → « Vous venez déjà d'envoyer une demande. » ;
7. depuis un événement « Sur inscription » de la page d'accueil, vérifier le préremplissage de la date, du type et du message.

- [ ] **Step 9: Commit**

```bash
git add cms/web/app/themes/prophet
git commit -m "feat(theme): page de prise de rendez-vous avec validation serveur"
```

---

### Task 20: Page de confirmation

**Files:**
- Create: `cms/web/app/themes/prophet/resources/views/template-confirmation.blade.php`
- Create: `cms/web/app/themes/prophet/resources/css/sections/_confirmation.css`
- Create: `cms/web/app/themes/prophet/app/View/Composers/Confirmation.php`
- Modify: `cms/web/app/themes/prophet/resources/css/app.css`

**Interfaces:**
- Consumes: `Repository::findByRef()` (tâche 12), `Whatsapp::confirmationUrl()` (tâche 5), `Date::formatFr()` (tâche 5)
- Produces: page `/confirmation` ; `$rdv`, `$dateFr`, `$waUrl`, `$introuvable`

- [ ] **Step 1: Écrire le composer**

`app/View/Composers/Confirmation.php` :

```php
<?php

declare(strict_types=1);

namespace App\View\Composers;

use ProphetCore\Options;
use ProphetCore\Rdv\Repository;
use ProphetCore\Services\Whatsapp;
use ProphetCore\Support\Date;
use Roots\Acorn\View\Composer;

class Confirmation extends Composer
{
    protected static $views = ['template-confirmation'];

    public function with(): array
    {
        $ref = isset($_GET['ref'])
            ? sanitize_text_field(wp_unslash($_GET['ref']))
            : '';

        $rdv = $ref === '' ? null : (new Repository())->findByRef($ref);

        if ($rdv === null) {
            return ['rdv' => null, 'dateFr' => '', 'waUrl' => '', 'introuvable' => true];
        }

        return [
            'rdv' => $rdv,
            'dateFr' => Date::formatFr($rdv['date']),
            'waUrl' => Whatsapp::confirmationUrl(Options::phone1(), $rdv),
            'introuvable' => false,
        ];
    }
}
```

La référence est un jeton aléatoire de 32 caractères : elle n'est pas devinable et ne révèle rien sur le nombre de rendez-vous, contrairement à un identifiant de post.

- [ ] **Step 2: Porter la page**

Appliquer la **Procédure de port d'une section** :

- source : `pages/confirmation.vue`
- CSS : `_confirmation.css`, scope `.sec-confirmation`
- vue : `resources/views/template-confirmation.blade.php`

```blade
{{-- Template Name: Confirmation --}}

@extends('layouts.app')

@section('content')
  <section class="sec-confirmation">
    @if ($introuvable)
      <h1>Demande introuvable</h1>
      <p>Ce lien de confirmation n'est plus valable.</p>
      <a href="{{ home_url('/rdv') }}">Reprendre une demande</a>
    @else
      {{-- Transposition du template de pages/confirmation.vue --}}
      <h1>Demande reçue</h1>
      <p>{{ $rdv['prenom'] }} {{ $rdv['nom'] }}</p>
      <p>{{ $rdv['type_consultation'] }}</p>
      <p>{{ $dateFr }} — {{ $rdv['heure'] }}</p>
      <a href="{{ esc_url($waUrl) }}" target="_blank" rel="noopener">
        <x-icon name="message-circle" /> Confirmer sur WhatsApp
      </a>
      <a href="{{ home_url('/') }}"><x-icon name="home" /> Retour à l'accueil</a>
    @endif
  </section>
@endsection
```

Le balisage définitif reprend celui de `pages/confirmation.vue` ; le squelette ci-dessus ne fixe que les données et les deux états.

Le Nuxt ouvrait WhatsApp automatiquement 800 ms après l'envoi (`RdvForm.vue`). Ce comportement disparaît : un `window.open` automatique est bloqué par les navigateurs hors interaction directe. Le bouton reste, mis en avant visuellement.

- [ ] **Step 3: Créer la page et importer le CSS**

```bash
docker compose -f docker-compose.cms.dev.yml exec -T wpcli \
  wp post create --post_type=page --post_title="Confirmation" \
    --post_name=confirmation --post_status=publish \
    --page_template=template-confirmation.blade.php --porcelain
```

```css
@import "./sections/_confirmation.css";
```

- [ ] **Step 4: Interdire l'indexation**

Ajouter dans `app/setup.php` :

```php
add_action('wp_head', static function (): void {
    if (is_page('confirmation')) {
        echo '<meta name="robots" content="noindex, nofollow">' . "\n";
    }
}, 1);
```

- [ ] **Step 5: Vérifier**

```bash
cd cms/web/app/themes/prophet && npm run build && cd -
curl -sS 'http://localhost:8080/confirmation/' | grep -c 'Demande introuvable'
```

Attendu : `1` sans paramètre `ref`.

Puis, dans le navigateur : envoyer une demande depuis `/rdv`, vérifier que la page de confirmation affiche le bon récapitulatif et que le bouton WhatsApp ouvre `wa.me` avec le message prérempli. Comparer visuellement avec `pages/confirmation.vue` du Nuxt.

- [ ] **Step 6: Commit**

```bash
git add cms/web/app/themes/prophet
git commit -m "feat(theme): page de confirmation adressée par jeton"
```

---

## Phase E — Contenu, production et bascule

### Task 21: Commande de seed du contenu

**Files:**
- Create: `cms/web/app/mu-plugins/prophet-core/src/Cli/SeedCommand.php`
- Create: `cms/tests/Unit/Cli/SeedCommandTest.php`
- Modify: `cms/web/app/mu-plugins/prophet-core/prophet-core.php`

**Interfaces:**
- Consumes: CPT et options (tâches 9 et 10)
- Produces: `wp prophet seed` — idempotente ; `SeedCommand::services(): array`, `::temoignages(): array`, `::photos(): array`, `::stats(): array` (données pures, testables)

- [ ] **Step 1: Écrire le test qui échoue**

`cms/tests/Unit/Cli/SeedCommandTest.php` :

```php
<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Cli;

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
}
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter SeedCommandTest"
```

Attendu : ÉCHEC — classe `SeedCommand` introuvable.

- [ ] **Step 3: Implémenter la commande**

`cms/web/app/mu-plugins/prophet-core/src/Cli/SeedCommand.php` — les données sont recopiées depuis les composants Vue, sans reformulation :

```php
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

    /** Reprise de lib/validations.ts:20-36. */
    public static function heures(): array
    {
        return array_map(
            static fn (string $h): array => ['valeur' => $h],
            ['08h00', '09h00', '10h00', '11h00', '14h00', '15h00', '16h00', '17h00']
        );
    }

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
        foreach (self::services() as $ordre => $service) {
            $id = $this->upsert(Service::SLUG, $service['titre'], [
                'post_content' => $service['description'],
                'menu_order' => $ordre + 1,
            ]);
            carbon_set_post_meta($id, 'service_icone', $service['icone']);
            carbon_set_post_meta($id, 'service_couleur', $service['couleur']);
        }

        foreach (self::temoignages() as $ordre => $t) {
            $id = $this->upsert(Temoignage::SLUG, $t['nom'], ['menu_order' => $ordre + 1]);
            carbon_set_post_meta($id, 'temoignage_initiales', $t['initiales']);
            carbon_set_post_meta($id, 'temoignage_pays', $t['pays']);
            carbon_set_post_meta($id, 'temoignage_couleur', $t['couleur']);
            carbon_set_post_meta($id, 'temoignage_etoiles', $t['etoiles']);
            carbon_set_post_meta($id, 'temoignage_texte', $t['texte']);
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
```

Les images ne sont pas importées automatiquement : la médiathèque WordPress est le lieu de cette décision, et un import silencieux créerait des doublons à chaque exécution. La commande journalise le fichier attendu pour chaque photo.

- [ ] **Step 4: Enregistrer la commande**

Ajouter à `prophet-core.php` :

```php
use ProphetCore\Cli\SeedCommand;

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('prophet seed', SeedCommand::class);
}
```

- [ ] **Step 5: Lancer les tests et vérifier qu'ils passent**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit --filter SeedCommandTest"
```

Attendu : PASS, 4 tests.

- [ ] **Step 6: Exécuter le seed et vérifier l'idempotence**

```bash
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp prophet seed
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp prophet seed
docker compose -f docker-compose.cms.dev.yml exec -T wpcli \
  wp post list --post_type=service --format=count
docker compose -f docker-compose.cms.dev.yml exec -T wpcli \
  wp post list --post_type=temoignage --format=count
```

Attendu : `12` et `6` après **deux** exécutions — aucun doublon.

- [ ] **Step 7: Importer les images de la galerie**

```bash
for f in prophet-main.jpg prophet-main-2.jpeg prophet-main-3.jpeg; do
  docker compose -f docker-compose.cms.dev.yml exec -T wpcli \
    wp media import "/srv/../public/images/$f" --porcelain
done
```

Puis, dans l'administration, rattacher chaque image à sa photo. Si le chemin relatif ne résout pas, copier d'abord les fichiers dans `cms/web/app/uploads/import/`.

- [ ] **Step 8: Commit**

```bash
git add cms/web/app/mu-plugins/prophet-core cms/tests/Unit/Cli
git commit -m "feat(core): commande wp prophet seed pour installer le contenu du site"
```

---

### Task 22: Production

**Files:**
- Create: `docker/Caddyfile.cms`
- Create: `docker-compose.cms.yml`
- Create: `docker/php/Dockerfile.prod`
- Modify: `cms/.env.example`
- Modify: `README.md`

**Interfaces:**
- Consumes: tout ce qui précède
- Produces: pile de production démarrable ; le service `api-bridge` et MongoDB ne sont plus référencés

- [ ] **Step 1: Écrire l'image de production**

`docker/php/Dockerfile.prod` :

```dockerfile
FROM php:8.2-fpm-alpine AS base

RUN apk add --no-cache \
      icu-dev icu-data-full libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev \
      $PHPIZE_DEPS \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" mysqli intl gd zip opcache \
 && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM node:20-alpine AS assets
WORKDIR /theme
COPY cms/web/app/themes/prophet/package*.json ./
RUN npm ci
COPY cms/web/app/themes/prophet ./
RUN npm run build

FROM base AS final
WORKDIR /srv
COPY cms/ /srv/
RUN composer install --no-dev --optimize-autoloader --no-interaction \
 && rm -rf /srv/web/app/themes/prophet/node_modules
COPY --from=assets /theme/public /srv/web/app/themes/prophet/public

RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.max_accelerated_files=10000'; \
    } > /usr/local/etc/php/conf.d/opcache.ini
```

- [ ] **Step 2: Écrire le Caddyfile de production**

`docker/Caddyfile.cms` — reprend les en-têtes et le cache du `Caddyfile` actuel, la racine passant sur `web/` :

```
{
	email {$ACME_EMAIL}
	admin off
}

{$DOMAIN} {
	root * /srv/web
	encode zstd gzip

	header {
		X-Frame-Options SAMEORIGIN
		X-Content-Type-Options nosniff
		Referrer-Policy strict-origin-when-cross-origin
		Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
		-Server
	}

	@static path_regexp \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?|ttf|eot|webp)$
	header @static Cache-Control "public, max-age=31536000, immutable"

	# Carbon Fields sert son JS et son CSS depuis le répertoire vendor de
	# Composer, qui est hors de la racine web dans Bedrock. Sans cette route,
	# tous ses assets répondent 404 et aucun champ ne s'affiche dans
	# l'administration — la déclaration PHP a beau être correcte. Le préfixe est
	# celui que prophet-core.php impose à Carbon_Fields\URL avant boot().
	handle_path /cf-vendor/carbon-fields/* {
		root * /srv/vendor/htmlburger/carbon-fields
		file_server
	}

	# Les fichiers déposés dans uploads ne doivent jamais être exécutés.
	@uploads_php path_regexp ^/app/uploads/.*\.php$
	respond @uploads_php 403

	php_fastcgi app:9000
	file_server
}
```

- [ ] **Step 3: Écrire le compose de production**

`docker-compose.cms.yml` :

Le `name:` en tête est obligatoire. Sans lui, Compose dérive le nom de projet du
répertoire et cette pile partage son identité avec le `docker-compose.yml` du Nuxt,
dont les services s'appellent aussi `caddy` et `app` : un `docker compose up` recrée
alors les conteneurs de l'autre pile. Le cas s'est produit en tâche 1.

```yaml
name: prophet_cms

services:
  caddy:
    image: caddy:2-alpine
    container_name: prophet_cms_caddy
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
      - "443:443/udp"
    environment:
      DOMAIN: ${DOMAIN}
      ACME_EMAIL: ${ACME_EMAIL}
    volumes:
      - ./docker/Caddyfile.cms:/etc/caddy/Caddyfile:ro
      - caddy_data:/data
      - caddy_config:/config
      - cms_uploads:/srv/web/app/uploads:ro
    depends_on: [app]
    networks: [cms_net]

  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile.prod
    container_name: prophet_cms_app
    restart: unless-stopped
    env_file: ./cms/.env
    volumes:
      - cms_uploads:/srv/web/app/uploads
    depends_on:
      db:
        condition: service_healthy
    networks: [cms_net]
    logging:
      driver: json-file
      options: { max-size: "10m", max-file: "3" }

  db:
    image: mysql:8
    container_name: prophet_cms_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    volumes:
      - cms_mysql:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-uroot", "-p${DB_ROOT_PASSWORD}"]
      interval: 10s
      timeout: 5s
      retries: 10
    networks: [cms_net]

volumes:
  cms_mysql:
  cms_uploads:
  caddy_data:      # certificats Let's Encrypt : ne jamais supprimer en production
  caddy_config:

networks:
  cms_net:
    driver: bridge
```

Le volume `caddy_data` porte les certificats : le supprimer force une réémission et peut heurter les limites de Let's Encrypt.

- [ ] **Step 4: Compléter les variables d'environnement**

Ajouter à `cms/.env.example` :

```
DB_ROOT_PASSWORD=
DOMAIN=votre-domaine.com
ACME_EMAIL=vous@exemple.com
```

En production, `WP_ENV=production` et `WP_HOME=https://${DOMAIN}`.

- [ ] **Step 5: Vérifier la construction**

```bash
docker compose -f docker-compose.cms.yml build
```

Attendu : construction réussie des trois étages. Vérifier que les assets sont bien présents dans l'image :

```bash
docker compose -f docker-compose.cms.yml run --rm app ls web/app/themes/prophet/public/build
```

Attendu : un `manifest.json` et les fichiers hachés.

Vérifier aussi la locale française dans l'image de production — la présence de
l'extension `intl` ne suffit pas, il faut les données ICU complètes :

```bash
docker compose -f docker-compose.cms.yml run --rm app php -r \
  'echo (new IntlDateFormatter("fr_FR", 0, 0, "UTC", 1, "EEEE d MMMM y"))
     ->format(new DateTimeImmutable("2026-07-30")), PHP_EOL;'
```

Attendu : `jeudi 30 juillet 2026`. Une sortie en anglais signale qu'`icu-data-full`
manque, et toutes les dates du site seraient servies en anglais sans la moindre
erreur.

Vérifier enfin que les assets de Carbon Fields sont bien servis en production —
sans eux, l'administration perd tous ses champs sans afficher la moindre erreur :

```bash
curl -sS -o /dev/null -w '%{http_code}\n' \
  "https://${DOMAIN}/cf-vendor/carbon-fields/build/classic/core.js"
```

Attendu : `200`. Un `404` signale que la route `handle_path` manque du Caddyfile ou
que le préfixe ne correspond plus à celui imposé à `Carbon_Fields\URL`.

- [ ] **Step 6: Mettre à jour le README**

Remplacer le tableau « Stack Technique » et la section « Démarrage rapide » par la pile Bedrock + Sage, en documentant :
`docker compose -f docker-compose.cms.dev.yml up -d`, `./cms/scripts/install-wp.sh`, `wp prophet seed`, `npm run dev` dans le thème, et `docker compose -f docker-compose.cms.yml up -d` pour la production.

- [ ] **Step 7: Commit**

```bash
git add docker docker-compose.cms.yml cms/.env.example README.md
git commit -m "feat(infra): pile de production PHP-FPM, Caddy et MySQL"
```

---

### Task 23: Recette et retrait du Nuxt

**Files:**
- Create: `docs/superpowers/notes/2026-07-30-recette-migration.md`
- Delete: `pages/`, `components/`, `server/`, `api-bridge/`, `lib/`, `assets/`, `app.vue`, `nuxt.config.ts`, `tailwind.config.ts`, `tsconfig.json`, `package.json`, `package-lock.json`, `Dockerfile`, `docker-compose.yml`, `docker-compose.dev.yml`, `Caddyfile`, `Caddyfile.dev`, `vercel.json`, `deploy.sh`, `Makefile`, `nginx/`
- Modify: `.gitignore`, `README.md`

**Interfaces:**
- Consumes: tout ce qui précède
- Produces: dépôt ne contenant plus que la pile PHP

- [ ] **Step 1: Dérouler la recette**

Créer `docs/superpowers/notes/2026-07-30-recette-migration.md` et y consigner le résultat de chaque point :

| # | Vérification | Attendu |
|---|---|---|
| 1 | Page d'accueil à 375, 768 et 1440 px | identique au Nuxt, section par section |
| 2 | Animations `fade-up` au défilement | déclenchées à l'entrée dans le viewport |
| 3 | Menu mobile | ouverture et fermeture |
| 4 | Carrousel de témoignages | avance et recule en boucle |
| 5 | Pagination des événements | 4 par page, boutons désactivés aux extrémités |
| 6 | Vidéos | 3 miniatures, liens vers YouTube |
| 7 | Formulaire vide | une erreur par champ, valeurs conservées |
| 8 | Dimanche dans flatpickr | non sélectionnable |
| 9 | Jour même | refusé côté serveur |
| 10 | Demande valide | redirection `/confirmation?ref=…`, récapitulatif correct |
| 11 | Même créneau renvoyé | « Ce créneau est déjà réservé. » |
| 12 | Deux envois en moins d'une minute | second refusé |
| 13 | Pot de miel rempli | demande non enregistrée |
| 14 | Emails | reçus par le client et par le prophète, `Reply-To` correct |
| 15 | Bouton WhatsApp | ouvre `wa.me` avec le message prérempli |
| 16 | `/confirmation` sans `ref` | « Demande introuvable », `noindex` |
| 17 | Administration | 12 services, 6 témoignages, 6 photos, options renseignées |
| 18 | Rendez-vous en administration | listés avec date, heure, type, statut |
| 19 | Modification d'un texte en administration | visible sur le site sans déploiement |
| 20 | Suite PHPUnit | intégralement au vert |

Tout point en échec est corrigé avant l'étape suivante.

- [ ] **Step 2: Lancer la suite complète une dernière fois**

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit"
```

Attendu : `OK`, aucun test ignoré.

- [ ] **Step 3: Vérifier qu'aucune référence au Nuxt ne subsiste dans le CMS**

```bash
grep -rIl --exclude-dir=vendor --exclude-dir=node_modules \
  -e 'api-bridge' -e 'MONGODB_URI' -e 'useFetch' cms/ || echo 'aucune référence'
```

Attendu : `aucune référence`.

- [ ] **Step 4: Supprimer le Nuxt**

**Cette étape est destructrice et porte sur l'intégralité de l'application actuelle.** Ne l'exécuter qu'une fois la recette de l'étape 1 entièrement au vert, et sur une branche dédiée, jamais directement sur `main`. La suppression reste réversible par `git revert` tant que le commit n'est pas poussé et fusionné.

```bash
git rm -r pages components server api-bridge lib assets nginx
git rm app.vue nuxt.config.ts tailwind.config.ts tsconfig.json \
       package.json package-lock.json Dockerfile docker-compose.yml \
       docker-compose.dev.yml Caddyfile Caddyfile.dev vercel.json \
       deploy.sh Makefile
```

Nettoyer ensuite `.gitignore` de ses entrées Node (`node_modules/`, `.nuxt/`, `.output/`) et retirer du `README.md` toute mention restante de Nuxt, MongoDB ou de l'api-bridge.

- [ ] **Step 5: Vérifier que le site fonctionne toujours**

```bash
docker compose -f docker-compose.cms.dev.yml up -d
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8080/
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8080/rdv/
```

Attendu : `200` et `200`.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "chore: retrait de l'application Nuxt remplacée par Bedrock et Sage"
```

---

## Notes de relecture

Points relevés en repassant la spec sur le plan, à garder en tête à l'exécution :

1. **Le CSS est le poste risqué, pas le PHP.** Les tâches 14 à 20 ne contiennent volontairement pas le CSS en clair : il est copié depuis les composants Vue, qui font foi. La procédure de port en tête de document est le contrat ; s'en écarter, c'est réécrire le design.
2. **`SubmitHandler::process()` appelle `exit`.** L'étape 3 de la tâche 13 impose l'extraction d'une méthode `terminer()` pour rendre la classe testable — ne pas la sauter, sinon le test tue le processus PHPUnit.
3. **Ordre des sections de la page d'accueil.** `pages/index.vue` fait foi ; la tâche 18 le rappelle explicitement.
4. **`eventDate` change de format** (ISO → `Y-m-d`) entre le Nuxt et la nouvelle version. C'est délibéré et cohérent entre `Events::lien()` (tâche 17), flatpickr (tâche 19) et `Validator::parseDate()` (tâche 11).
5. **Deux comportements du Nuxt disparaissent volontairement** : l'ouverture automatique de WhatsApp après envoi (bloquée par les navigateurs hors interaction) et les toasts (les erreurs viennent désormais du serveur). Tous deux sont signalés dans les tâches 19 et 20.
6. **Les images de la galerie ne sont pas importées par le seed** : elles passent par la médiathèque, décision d'administrateur.

