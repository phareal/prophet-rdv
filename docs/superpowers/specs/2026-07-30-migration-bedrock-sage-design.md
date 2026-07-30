# Migration prophet-rdv : Nuxt 3 → Bedrock + Sage

**Date** : 2026-07-30
**Statut** : spec validée, plan d'implémentation à écrire

## Objectif

Remplacer l'application Nuxt 3 actuelle par un site WordPress construit sur
[Bedrock](https://roots.io/bedrock/) et [Sage](https://roots.io/sage/), en conservant
le design au pixel près et en rendant l'intégralité du contenu éditable depuis
l'administration WordPress.

Le site n'est pas encore en production et **aucun rendez-vous n'existe en base
MongoDB** : aucune reprise de données n'est nécessaire.

## Existant

| Couche | Aujourd'hui |
|---|---|
| Framework | Nuxt 3 SSR, TypeScript strict |
| Pages | `/` (`pages/index.vue`), `/rdv`, `/confirmation` |
| Sections | 16 composants Vue dans `components/` |
| Design | ~3 200 lignes de `<style scoped>` réparties dans 17 fichiers, sur les variables CSS de `assets/css/main.css` |
| Tailwind | limité à `components/ui/**` (shadcn-vue) — voir `tailwind.config.ts:8` |
| Base de données | MongoDB via Mongoose (`server/models/Appointment.ts`) |
| Validation | zod + vee-validate (`lib/validations.ts`) |
| Emails | Nodemailer SMTP (`server/utils/mailer.ts`) |
| Événements / Vidéos | Google Calendar & YouTube via un service Node `api-bridge/` |
| Prod | Docker : Caddy + Nuxt + api-bridge + MongoDB |

Le contenu (12 services, 6 témoignages, 6 photos, 4 stats, textes hero/about/footer)
est écrit en dur dans les composants Vue.

## Cible

| Couche | Après migration |
|---|---|
| Socle | Bedrock (WordPress par Composer, `.env`, webroot `web/`) |
| Thème | Sage 11 (Blade via Acorn, Vite) — présentation seule |
| Métier | mu-plugin `prophet-core` — CPT, champs, RDV, services API, emails |
| Champs | Carbon Fields (gratuit, définis en PHP, versionnés) |
| Base de données | MySQL 8 |
| Interactivité | Alpine.js + flatpickr |
| Prod | Docker : Caddy + PHP-FPM 8.2 + MySQL 8 |

### Arborescence

```
prophet-rdv/
├── (Nuxt actuel — conservé intact jusqu'à la bascule finale)
└── cms/
    ├── composer.json          # roots/bedrock, roots/wordpress, roots/acorn,
    │                          # htmlburger/carbon-fields
    ├── .env                   # DB, SMTP, clés Google, WP salts
    ├── config/
    │   ├── application.php
    │   └── environments/{development,production}.php
    └── web/
        ├── wp/                        # cœur WordPress (Composer)
        └── app/
            ├── mu-plugins/prophet-core/
            │   ├── prophet-core.php
            │   └── src/
            │       ├── PostTypes/     # Service, Temoignage, Photo, RendezVous
            │       ├── Fields/        # définitions Carbon Fields
            │       ├── Rdv/           # Validator, Repository, SubmitHandler
            │       ├── Services/      # GoogleCalendar, Youtube, Whatsapp, Mail
            │       └── Support/       # Date::formatFr(), helpers
            ├── themes/prophet/        # Sage 11
            │   ├── app/
            │   │   ├── setup.php
            │   │   └── View/Composers/
            │   ├── resources/
            │   │   ├── views/
            │   │   ├── css/
            │   │   ├── js/
            │   │   └── images/
            │   └── vite.config.js
            └── uploads/
```

**Pourquoi un mu-plugin et pas tout dans le thème** : le contenu et les rendez-vous
doivent survivre à un changement de thème. Le thème ne contient que du rendu.

## 1. Port du design

C'est le poste le plus lourd et celui où le risque de régression est le plus élevé.
Le CSS est **copié**, pas réécrit.

### Règles

1. `assets/css/main.css` → `resources/css/tokens.css`, **verbatim** : variables,
   reset, typographie, `.section-ornament`, keyframes, scrollbar, `.sr-only`.
   L'override `[data-radix-popper-content-wrapper]` disparaît avec Radix.
2. Chaque `<style scoped>` de composant → un fichier
   `resources/css/sections/_<section>.css`.
3. **Blade n'a pas de scoping.** Chaque fichier de section est enveloppé dans un
   sélecteur racine (`.sec-hero { … }`) via le nesting PostCSS, et la partial Blade
   correspondante porte cette classe sur son élément racine. Cela reproduit
   l'isolation qu'assurait `scoped` sans renommer une seule classe.
4. **Audit de collisions obligatoire avant tout port CSS** : relever les noms de
   classes utilisés par plusieurs composants (`.card`, `.title`, `.badge`…). Le
   scoping par section les neutralise, mais l'audit vérifie qu'aucune règle ne
   s'appuyait sur une cascade inter-composants.
5. Tailwind est **retiré** du thème. Il ne servait qu'aux composants shadcn-vue ;
   les widgets de formulaire concernés (input, textarea, radio, bouton, toast)
   passent en CSS écrit à la main, dans le style du reste du site.
   Conserver Tailwind pour une configuration inutilisée n'apporterait que du bruit.
6. Polices : mêmes familles Google (Cinzel, Cormorant Garamond, JetBrains Mono,
   Inter), même chargement non bloquant (`media="print"` + `onload`) que
   `nuxt.config.ts:57-66`, enregistré depuis `app/setup.php`.
7. Icônes : SVG `lucide-static` inlinés par un composant Blade
   `<x-icon name="heart" />`. Le nom d'icône reste une chaîne stockée en champ,
   ce qui permet de changer l'icône d'un service depuis l'admin.
8. Images de `public/images/` et `assets/` recopiées dans le thème ; celles qui
   deviennent éditables (galerie, portrait) passent dans la médiathèque.

### Correspondance composants → vues

| Composant Vue | Vue Blade |
|---|---|
| `app.vue` + layout | `views/layouts/app.blade.php` |
| `pages/index.vue` | `views/front-page.blade.php` |
| `TheNavbar.vue` | `views/partials/navbar.blade.php` |
| `HeroSection.vue` | `views/sections/hero.blade.php` |
| `StatsBar.vue` | `views/sections/stats.blade.php` |
| `AboutSection.vue` | `views/sections/about.blade.php` |
| `ServicesSection.vue` | `views/sections/services.blade.php` |
| `EventsSection.vue` | `views/sections/events.blade.php` |
| `VideoSection.vue` | `views/sections/videos.blade.php` |
| `GallerySection.vue` | `views/sections/gallery.blade.php` |
| `TestimonialsSection.vue` + `TestimonialCard.vue` | `views/sections/testimonials.blade.php` + `views/partials/testimonial-card.blade.php` |
| `CtaSection.vue` | `views/sections/cta.blade.php` |
| `AppFooter.vue` | `views/partials/footer.blade.php` |
| `FloatingWhatsApp.vue` | `views/partials/floating-whatsapp.blade.php` |
| `HeroLeft.vue` | `views/partials/hero-left.blade.php` |
| `RdvForm.vue` + `ConsultationPicker.vue` | `views/template-rdv.blade.php` + `views/partials/rdv-form.blade.php` |
| `pages/rdv.vue` | `views/template-rdv.blade.php` |
| `pages/confirmation.vue` | `views/template-confirmation.blade.php` |

Chaque section reçoit ses données d'un View Composer
(`app/View/Composers/ServicesComposer.php`, …) : la vue ne fait aucune requête.

## 2. Modèle de contenu

Défini en PHP avec Carbon Fields, dans `prophet-core`. Aucune configuration
cliquée : tout est versionné.

### Custom Post Types

**`service`** — 12 entrées, source de `ServicesSection.vue:6-19`
| Champ | Type | Note |
|---|---|---|
| titre | post title | |
| description | textarea | |
| icone | text | nom lucide (`heart`, `sparkles`…) |
| couleur | color | ex. `#c2185b` |
| ordre | menu_order | tri de la grille |

Ce CPT alimente aussi la liste des types de consultation du formulaire, aujourd'hui
dupliquée entre `ServicesSection.vue:6`, `ConsultationPicker.vue:11` et
`lib/validations.ts:3`. **Une seule source de vérité après migration.**

**`temoignage`** — 6 entrées, source de `TestimonialsSection.vue:4`
nom, initiales, pays, service lié (association), étoiles (1-5), couleur, texte.

**`photo`** — galerie, source de `GallerySection.vue:2`
image (médiathèque), légende, format (`normal` | `wide`).

**`rendez_vous`** — voir section 3.

### Pages d'options

**« Contenu du site »** : hero (sur-titre, titre, sous-titre, libellés des CTA,
image), about (titre, paragraphes, image, points clés), 4 paires stat
(`StatsBar.vue:2`), bloc CTA, footer (liens, réseaux, mentions).

**« Réglages RDV »** : téléphones WhatsApp 1 et 2, email de notification,
répéteur des créneaux horaires (`lib/validations.ts:20-29`), répéteur des modes de
paiement (`lib/validations.ts:31-36`), texte d'aide du formulaire.

## 3. Rendez-vous

### Stockage

CPT privé `rendez_vous` (`public => false`, `show_ui => true`). Le choix d'un CPT
plutôt que d'une table dédiée donne gratuitement la liste d'administration, la
recherche, les filtres et l'export ; le volume attendu ne justifie pas une table.

Champs meta, calqués sur `server/models/Appointment.ts:4-16` : nom, prenom, email,
telephone, pays, date, heure, type_consultation, mode_paiement, message, ref.

Statuts personnalisés : `en_attente` (défaut), `confirme`, `annule`.

Colonnes d'admin personnalisées : date + heure, nom, type, statut ; tri par date.

### Parcours

1. `/rdv` — page WordPress avec le template Blade `template-rdv.blade.php`.
2. Soumission `POST` vers `admin-post.php`, actions `prophet_rdv_submit` et
   `prophet_rdv_submit_nopriv`.
3. Validation serveur. En cas d'erreur : valeurs saisies et messages d'erreur
   stockés dans un transient de 5 minutes sous une clé aléatoire, redirection vers
   `/rdv?e=<clé>`, puis réaffichage du formulaire avec les erreurs sous les champs
   concernés et suppression immédiate du transient. WordPress n'a pas de session ;
   ce jeton à usage unique en tient lieu sans dépendre d'un cookie.
4. En cas de succès : création du post, génération d'un `ref` aléatoire
   (`wp_generate_password(32, false)`), envoi des emails, redirection
   POST/Redirect/GET vers `/confirmation?ref=<ref>`.
5. `/confirmation` — charge le rendez-vous **par son `ref`**, pas par son ID.
   Le Nuxt actuel passe toutes les données du rendez-vous en clair dans l'URL
   (`RdvForm.vue`, redirection vers `/confirmation`) ; le `ref` aléatoire évite à la
   fois cette fuite et l'énumération des rendez-vous d'autrui.
   La page affiche le récapitulatif et le bouton WhatsApp.

### Validation

Règles reprises de `lib/validations.ts:38-63` :

| Champ | Règle |
|---|---|
| nom, prenom | requis, ≥ 2 caractères |
| email | requis, email valide |
| telephone | requis, ≥ 8 caractères |
| pays | requis |
| date | requise, dans le futur, **dimanche refusé** |
| heure | requise, valeur présente dans les créneaux configurés |
| type_consultation | requis, valeur existante parmi les services (ou préfixé `Inscription — ` pour un événement) |
| mode_paiement | requis, parmi les modes configurés |
| message | facultatif, ≤ 500 caractères |

Plus la règle métier de `server/api/rdv.post.ts:41-56` : aucun `rendez_vous` non
annulé ne doit exister sur le même couple (date, heure). Sinon message
« Ce créneau est déjà réservé. Veuillez choisir une autre heure. »

### Sécurité

- Nonce WordPress sur le formulaire, vérifié côté serveur.
- Honeypot (champ masqué) — absent du Nuxt actuel.
- Limitation : une soumission par IP toutes les 60 secondes (transient).
- Toutes les valeurs assainies à l'écriture, échappées à l'affichage.

### Préremplissage depuis un événement

`EventsSection.vue:14-25` construit un lien `/rdv?event=…&eventDate=…&eventLieu=…&eventHeure=…`
pour les événements « Sur inscription ». Comportement conservé : le template `/rdv`
lit ces paramètres, préremplit la date, force le type de consultation à
`Inscription — <titre>` et pré-remplit le message, comme `RdvForm.vue:35-42`.

## 4. Intégrations

### Google Calendar

Service PHP portant `api-bridge/src/services/calendar.ts` : `wp_remote_get` sur
l'API Calendar v3 (`timeMin` = maintenant, `orderBy=startTime`, `singleEvents=true`,
`maxResults=10`), puis mise en forme identique — jour sur 2 chiffres, mois français
abrégé (`Jan`…`Déc`), année, titre, `lieu` / `places` / `type` extraits de la
description par la même convention `clé: valeur`, plage horaire `HHhMM – HHhMM`,
`featured` sur le premier élément.

Cache : Transient WordPress, **10 minutes** (TTL actuel du bridge).

### YouTube

Port de `api-bridge/src/services/youtube.ts` : `playlistItems.list` sur la playlist
`UU…` dérivée de l'ID de chaîne (1 unité de quota au lieu de 100 pour `search.list`),
`maxResults=3`, miniature `high` puis `medium` puis repli sur `img.youtube.com`.

Cache : Transient, **30 minutes**.

### Repli sans clés d'API

`server/api/calendar/events.get.ts` et `server/api/youtube/latest.get.ts` renvoient
des données de démonstration quand la clé manque ou que l'appel échoue. Ce
comportement est conservé : sans clé configurée, ou en cas d'erreur, les services
renvoient les mêmes jeux `[MOCK]` afin que le site reste affichable en développement.
Les échecs sont journalisés via `error_log`.

### Emails

`wp_mail` avec SMTP configuré sur le hook `phpmailer_init` depuis les variables
`.env` — aucun plugin SMTP. Les deux emails de `server/utils/mailer.ts` deviennent
des vues Blade :

- `emails/client.blade.php` — accusé de réception, sujet
  `✅ Confirmation de votre demande de RDV — <type>`.
- `emails/prophet.blade.php` — notification, sujet
  `📅 Nouveau RDV : <prénom> <nom> — <type> — <date>`, `Reply-To` = email du client.

HTML et styles inline repris tels quels. Envoi déclenché sur `shutdown` pour ne pas
retarder la redirection ; un échec d'envoi est journalisé et ne fait pas échouer la
soumission, comme aujourd'hui (`server/api/rdv.post.ts:87-95`).

### WhatsApp

Helper PHP reproduisant `lib/utils.ts:23-26` et `server/utils/whatsapp.ts:20-38` :
numéro nettoyé (`[^+\d]`), message identique, `https://wa.me/<numéro>?text=<message encodé>`.

`Date::formatFr()` reproduit `formatDateFr` (`lib/utils.ts:13-20`) : jour de la
semaine, jour, mois, année en français. Implémenté avec `IntlDateFormatter`
(extension `intl` requise), pas avec `date_i18n`, pour garantir le même rendu que
`Intl.DateTimeFormat('fr-FR')`.

## 5. Front dynamique

Alpine.js, bundlé par Vite avec le CSS.

| Comportement | Source Vue | Implémentation |
|---|---|---|
| Menu mobile, navbar au scroll | `TheNavbar.vue` | `x-data` local |
| Carrousel témoignages | `TestimonialsSection.vue` | `x-data` + index |
| Pagination événements (4 par page) | `EventsSection.vue:44-54` | `x-data`, données rendues côté serveur |
| Bouton WhatsApp flottant | `FloatingWhatsApp.vue` | `x-data` (ouverture/fermeture) |
| Formulaire multi-étapes | `RdvForm.vue` | `x-data` (étape courante, compteur de caractères, sélection type/heure/paiement) |
| Choix de date | `Calendar` shadcn | **flatpickr**, locale `fr`, `disable` sur les dimanches, `minDate: today` |
| Toasts | `components/ui/toast/*` | remplacés par un bandeau de message serveur (les erreurs viennent désormais du serveur) |
| Animations `fade-up` | keyframes CSS | `IntersectionObserver` de ~15 lignes |

Aucune dépendance Vue, Radix ou vee-validate ne subsiste.

## 6. Infrastructure

### Développement

`docker-compose.dev.yml` : php-fpm 8.2 + Caddy + MySQL 8 + `vite dev` pour le HMR
du thème. Le Nuxt reste lançable en parallèle sur son port pour la comparaison
visuelle section par section.

### Production

`docker-compose.yml` révisé :

- **caddy** — inchangé dans son principe (HTTPS Let's Encrypt, en-têtes de sécurité,
  cache immuable des assets), racine `cms/web`, `php_fastcgi app:9000`.
- **app** — image PHP 8.2-FPM (extensions `mysqli`, `intl`, `gd`, `zip`, `opcache`),
  code applicatif construit par Composer, assets du thème construits par Vite.
- **db** — MySQL 8, volume `mysql_data`, healthcheck.
- Volume `uploads` pour `web/app/uploads`.

Les services `api-bridge` et `mongo` sont supprimés.

### Variables d'environnement

Reprises de `.env.example`, plus celles de Bedrock :

```
WP_ENV, WP_HOME, WP_SITEURL
DB_NAME, DB_USER, DB_PASSWORD, DB_HOST
AUTH_KEY … NONCE_SALT            # salts WordPress
SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_USER, SMTP_PASS
PROPHET_EMAIL, PROPHET_PHONE_1, PROPHET_PHONE_2
GOOGLE_API_KEY, GOOGLE_CALENDAR_ID
YOUTUBE_API_KEY, YOUTUBE_CHANNEL_ID
DOMAIN, ACME_EMAIL
```

`MONGODB_URI` et `API_BRIDGE_URL` disparaissent.

## 7. Tests

PHPUnit sur le métier de `prophet-core`, là où il y a une vraie logique :

- règles de validation, chaque cas d'échec y compris le dimanche et la date passée ;
- détection de créneau déjà réservé ;
- parsing d'une réponse Calendar (extraction `lieu`/`places`/`type`, plage horaire,
  mois français, événement sur plusieurs jours sans `dateTime`) ;
- parsing d'une réponse YouTube (dérivation `UC` → `UU`, repli de miniature) ;
- repli sur les données de démonstration quand la clé est absente ou l'appel en échec ;
- construction de l'URL WhatsApp (nettoyage du numéro, encodage) ;
- `Date::formatFr()`.

Appels HTTP simulés via le filtre `pre_http_request`. Pas de test sur le rendu Blade :
la fidélité visuelle se vérifie par comparaison directe avec le Nuxt qui tourne à côté.

## 8. Ordre de réalisation

1. Socle Bedrock + Sage + Docker qui démarre, WordPress installé.
2. Audit des collisions de classes CSS, port des tokens, mise en place de la
   convention de scoping par section.
3. mu-plugin `prophet-core` : CPT et champs Carbon Fields, pages d'options.
4. Services PHP (Calendar, YouTube, WhatsApp, Mail, Date) + tests PHPUnit.
5. Layout, navbar, footer, WhatsApp flottant.
6. Sections de la page d'accueil, une par une, chacune comparée visuellement au Nuxt.
7. Page `/rdv` : template, formulaire, validation, stockage, emails, page de
   confirmation.
8. Seed du contenu réel (12 services, 6 témoignages, photos, textes, stats) via une
   commande WP-CLI idempotente, pour ne pas dépendre d'une saisie manuelle.
9. Recette complète : parcours de bout en bout, emails reçus, admin utilisable.
10. Suppression du Nuxt (`pages/`, `components/`, `server/`, `api-bridge/`,
    `nuxt.config.ts`, `package.json`, `Dockerfile`) en un commit dédié.

## Hors périmètre

- Multilingue.
- Paiement en ligne (les modes de paiement restent déclaratifs, comme aujourd'hui).
- Espace client ou authentification des visiteurs.
- Refonte du design : l'objectif est l'identique.

## Risques

| Risque | Traitement |
|---|---|
| Régression visuelle sur une section | Comparaison côte à côte avec le Nuxt, section par section, avant de passer à la suivante |
| Collisions de classes CSS entre sections | Audit préalable + scoping par section (étape 2) |
| Effets liés à Vue non reproductibles à l'identique (`<Transition>`, transitions de liste) | Signalés explicitement plutôt qu'improvisés ; décision au cas par cas |
| Quota API YouTube / Calendar | `playlistItems.list` conservé (1 unité), caches Transient 10 / 30 min, repli sur données de démonstration |
| Extension `intl` absente de l'image PHP | Installée explicitement dans le Dockerfile ; testée par le test de `Date::formatFr()` |
