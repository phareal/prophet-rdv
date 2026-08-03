# Prophète Jeremiah Nahoum — Application de Prise de Rendez-vous

Site et prise de rendez-vous en ligne pour le **Prophète Jeremiah Nahoum**,
« Le Conseiller des Rois ». Le contenu est éditable depuis l'administration
WordPress ; aucune modification de texte ne demande de déploiement.

## Stack technique

| Couche | Technologie |
|--------|-------------|
| CMS | WordPress sur Bedrock 1.28 (PHP 8.2) |
| Thème | Sage 11 (Blade, Vite) — `cms/web/app/themes/prophet` |
| Métier | mu-plugin `prophet-core`, namespace `ProphetCore\` |
| Champs personnalisés | Carbon Fields |
| Base de données | MySQL 8 |
| Interactivité | Alpine.js + flatpickr |
| Email | SMTP via `wp_mail` |
| WhatsApp | Lien `wa.me` construit côté serveur |
| Conteneurs | Docker multi-étages (PHP-FPM) + Docker Compose |
| Reverse proxy | Caddy 2 (HTTPS automatique) |

---

## Démarrage rapide

### Prérequis

- Docker et Docker Compose
- Node.js 20+ et npm 10+ (pour construire le thème)

### Développement local

```bash
# 1. Variables d'environnement
cp cms/.env.example cms/.env
# Éditer cms/.env : secrets WordPress, SMTP, clés Google, PROPHET_EMAIL

# 2. Pile de développement (Caddy, PHP-FPM, MySQL, WP-CLI)
docker compose -f docker-compose.cms.dev.yml up -d

# 3. Installation de WordPress (une seule fois)
./cms/scripts/install-wp.sh

# 4. Contenu réel du site (idempotent, rejouable)
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp prophet seed

# 5. Thème : construction, ou serveur de développement avec rechargement à chaud
cd cms/web/app/themes/prophet && npm install && npm run build
```

Site sur `http://localhost:8080`, administration sur `http://localhost:8080/wp/wp-admin`.

### Tests

```bash
docker compose -f docker-compose.cms.dev.yml run --rm app \
  sh -c "cd /srv && vendor/bin/phpunit"
```

### Production

```bash
# 1. Variables lues par Docker Compose lui-même (${DOMAIN}, ${DB_NAME}...)
cp .env.example .env
# Éditer .env : DOMAIN, ACME_EMAIL, DB_NAME, DB_USER, DB_PASSWORD, DB_ROOT_PASSWORD

# 2. Variables applicatives de WordPress, comme en développement
cp cms/.env.example cms/.env
# Éditer cms/.env : WP_ENV=production, WP_HOME=https://votre-domaine.com,
# secrets réels, DB_* (mêmes valeurs que dans .env), SMTP, clés Google/YouTube

# 3. Construction et démarrage
docker compose -f docker-compose.cms.yml up -d --build
```

Caddy obtient et renouvelle les certificats HTTPS via Let's Encrypt. Le volume
`caddy_data` porte ces certificats : ne jamais le supprimer en production.

La pile de production embarque désormais aussi un service `wpcli` (image
officielle `wordpress:cli-php8.2`, comme en développement) : wp-cli n'est pas
une dépendance Composer de l'application, donc l'image `app` ne le fournit
pas. Ce service lit le code depuis le même volume que Caddy — jamais un bind
mount — il voit donc exactement ce qui est déployé.

**À vérifier après le premier déploiement :**

```bash
# Le site doit être indexable — ce réglage vit en base et suit une copie de base
docker compose -f docker-compose.cms.yml exec -T wpcli wp option get blog_public   # attendu : 1

# Les assets de Carbon Fields, servis hors racine web
curl -sS -o /dev/null -w '%{http_code}\n' \
  "https://${DOMAIN}/cf-vendor/carbon-fields/build/classic/core.js"                 # attendu : 200

# La locale française, qui dépend des données ICU complètes
docker compose -f docker-compose.cms.yml run --rm app php -r \
  'echo (new IntlDateFormatter("fr_FR",0,0,"UTC",1,"EEEE d MMMM y"))
     ->format(new DateTimeImmutable("2026-07-30")), PHP_EOL;'                       # attendu : jeudi 30 juillet 2026
```

---

## Configuration (`cms/.env`)

Ces variables alimentent l'environnement des conteneurs PHP via `env_file:`.

| Variable | Description |
|----------|-------------|
| `WP_ENV`, `WP_HOME`, `WP_SITEURL` | Environnement et URLs |
| `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST` | Base MySQL |
| `AUTH_KEY` … `NONCE_SALT` | Secrets WordPress |
| `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, `SMTP_PASS` | Envoi d'emails |
| `PROPHET_EMAIL` | Destinataire des notifications de rendez-vous |
| `PROPHET_PHONE_1`, `PROPHET_PHONE_2` | Numéros WhatsApp |
| `GOOGLE_API_KEY`, `GOOGLE_CALENDAR_ID` | Agenda des événements |
| `YOUTUBE_API_KEY`, `YOUTUBE_CHANNEL_ID` | Dernières vidéos |

Sans clés Google ou YouTube, les sections Événements et Vidéos affichent des
données de démonstration plutôt que de tomber en erreur.

**Note Gmail :** activer l'authentification à deux facteurs, puis créer un
« App Password » sur [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords).

## Configuration (`.env`, racine du dépôt)

Ces variables ne sont lues que par Docker Compose, pour interpoler
`${VARIABLE}` dans `docker-compose.cms.yml` — jamais transmises aux
conteneurs. `DB_NAME`/`DB_USER`/`DB_PASSWORD` doivent avoir les mêmes valeurs
que dans `cms/.env`, puisque MySQL est provisionné à partir d'ici alors que
WordPress s'y connecte à partir de là.

| Variable | Description |
|----------|-------------|
| `DOMAIN` | Nom de domaine servi par Caddy en production |
| `ACME_EMAIL` | Contact Let's Encrypt |
| `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Provisionnement MySQL |
| `DB_ROOT_PASSWORD` | Mot de passe root MySQL |

---

## Administration du contenu

Tout le contenu éditorial se gère depuis `wp-admin`, sans déploiement :

| Menu | Contenu |
|------|---------|
| Services | Les 12 types de consultation — titre, description, icône, couleur. Alimente aussi le formulaire de rendez-vous. |
| Témoignages | Nom, initiales, pays, service concerné, étoiles, texte |
| Photos | Galerie — image, légende, format (normal ou large) |
| Rendez-vous | Demandes reçues, en lecture avec changement de statut |
| Contenu du site | Textes du hero, à propos, chiffres clés, appel à l'action, pied de page |
| Réglages RDV | Numéros WhatsApp, email de notification, créneaux horaires, modes de paiement |

Les événements viennent de Google Calendar et les vidéos de YouTube : ils ne
s'éditent pas ici.

---

## Structure

```
prophet-rdv/
├── cms/                                    # Bedrock
│   ├── tests/                              # PHPUnit
│   └── web/app/
│       ├── mu-plugins/prophet-core/        # tout le métier
│       │   └── src/{PostTypes,Fields,Services,Rdv,Support,Cli}
│       └── themes/prophet/                 # Sage — présentation seule
│           ├── app/View/Composers/
│           └── resources/{views,css,js,images}
├── docker/                                 # Dockerfiles et Caddyfiles
├── docs/superpowers/                       # spécification, plan, notes de recette
├── .env.example                            # variables interpolées par Docker Compose
├── docker-compose.cms.dev.yml
└── docker-compose.cms.yml
```

La séparation est volontaire : le mu-plugin porte les données et les règles, le
thème ne fait que du rendu. Changer de thème ne fait perdre ni le contenu ni les
rendez-vous.

---

## Historique

Ce site était une application Nuxt 3 avec MongoDB et un service Node séparé pour
Google Calendar et YouTube. La migration vers WordPress a conservé le design à
l'identique et rendu le contenu éditable. La spécification, le plan de migration
et la recette sont dans `docs/superpowers/`.
