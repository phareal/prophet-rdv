# Recette de migration Bedrock/Sage — Tâche 23 (Étapes 1 à 3 uniquement)

Date d'exécution : 2026-08-03
Périmètre : Step 1 (recette des 20 points), Step 2 (suite PHPUnit), Step 3 (grep de références Nuxt résiduelles).
Hors périmètre (volontairement non exécuté) : Steps 4 à 6 (suppression de l'application Nuxt, redémarrage prod, commit de suppression).

Environnement : stack `docker-compose.cms.dev.yml` (projet `prophet_cms_dev`), site `http://localhost:8080`, admin `admin`/`admin`. Les conteneurs `atlas_blue-*` et `prophet_rdv_*` n'ont pas été touchés (aucun `docker compose up/down/restart` exécuté dessus).

Convention des verdicts : **conforme** / **défaut** / **non vérifiable**.

## Table de recette

| # | Vérification | Attendu | Verdict | Preuve résumée |
|---|---|---|---|---|
| 1 | Page d'accueil à 375, 768 et 1440 px | identique au Nuxt, section par section | **conforme (avec réserve)** | Ordre des sections vérifié identique à `pages/index.vue` (Hero → Stats → About → Services → Videos → Events → Testimonials → Gallery → CTA), via `front-page.blade.php`. Capture réelle à 768 px (largeur exacte obtenue). Capture à ~1512 px pour le palier desktop (l'outil de contrôle navigateur n'a pas permis d'imposer exactement 1440 px dans cette session — voir note technique ci-dessous). Capture à ~373-375 px obtenue via une iframe de contournement (largeur CSS réelle, cf. note technique) montrant le bon empilement mobile (hero, CTA, stats, portrait). Pas de contre-vérification pixel-à-pixel automatisée contre une instance Nuxt réellement rendue (le conteneur `prophet_rdv_caddy` n'expose aucun port hôte, cf. contrainte de ne pas y toucher) : la comparaison s'appuie sur le CSS copié verbatim des composants Vue (source de vérité selon les notes de relecture du brief) plus les rendus obtenus. |
| 2 | Animations `fade-up` au défilement | déclenchées à l'entrée dans le viewport | **DÉFAUT** | `resources/js/app.js` définit un `IntersectionObserver` qui cible `document.querySelectorAll('[data-observe]')` et ajoute la classe `is-visible`. Or **aucun template Blade ne pose l'attribut `data-observe`** (`grep` vide sur `resources/views`) et **aucune règle CSS `.is-visible` n'existe** (ni dans les sources, ni dans le CSS compilé `public/build/assets/*.css`). Vérifié également en direct dans le navigateur : `document.querySelectorAll('[data-observe]').length === 0` sur la page d'accueil chargée. Résultat : l'observer ne s'active jamais, aucune animation d'apparition au scroll ne se produit (les sections sont juste visibles immédiatement, sans effet de fondu). |
| 3 | Menu mobile | ouverture et fermeture | **conforme** | Testé en direct à 768 px (sous le seuil `@media (max-width: 840px)` de `_navbar.css`) : clic sur le bouton hamburger → panneau mobile affiché avec les 5 liens + CTA, icône passée en croix ; second clic → panneau refermé, icône revenue en hamburger. |
| 4 | Carrousel de témoignages | avance et recule en boucle | **conforme (avec clarification)** | Le composant `TestimonialsSection.vue` (grille homepage, tâche 18) **n'a jamais contenu de carrousel** dans la source Nuxt (pas de `ref`, pas d'index) — c'est une grille CSS statique, et le port (`sections/testimonials.blade.php`) le reproduit fidèlement, comme documenté dans le commentaire en tête du fichier. Le véritable carrousel de témoignages provient de `HeroLeft.vue` (bandeau latéral sombre de la page `/rdv/`) : `current` avancé toutes les 4s modulo la longueur du tableau (boucle avant automatique), plus des points cliquables permettant de sauter à un témoignage antérieur. Reproduit dans `hero-left.blade.php` avec un composant Alpine équivalent (`x-data`, `setInterval`, `goTo(i)`, `x-transition`). Vérifié en direct : le point actif change automatiquement entre deux captures successives, le texte affiché change (transition croisée visible), et le clic sur `.floating-wa__btn`/dots fonctionne. |
| 5 | Pagination des événements | 4 par page, boutons désactivés aux extrémités | **conforme** | Les données de démonstration ne contiennent que 4 événements (`GoogleCalendar::MOCK_EVENTS`), donc la pagination n'apparaît pas nativement (`$totalPages > 1` faux). Un 5ᵉ événement a été injecté temporairement via `set_transient(GoogleCalendar::TRANSIENT, ...)` (aucune modification de code) pour forcer 2 pages. Vérifié en direct : page 1 affiche les 4 événements d'origine, bouton « Page précédente » désactivé, « Page suivante » actif ; clic sur suivant → page 2 affiche uniquement le 5ᵉ événement, bouton suivant désactivé, précédent actif. Transient supprimé après le test (`wp transient delete prophet_calendar_events`). |
| 6 | Vidéos | 3 miniatures, liens vers YouTube | **conforme** | 3 `.vcard` sur la page d'accueil, chacune avec `.vcard__img` et un lien `https://www.youtube.com/watch?v=...`. |
| 7 | Formulaire vide | une erreur par champ, valeurs conservées | **conforme** | Envoi vide via `admin-post.php` (nonce scrappé sur `/rdv/`) → redirection `/rdv/?e=...#formulaire` → 9 erreurs affichées (une par champ obligatoire : type_consultation, nom, prénom, email, téléphone, pays, date, heure, mode_paiement). Second envoi avec des valeurs partielles invalides (nom, prénom, email invalide, téléphone, pays, message) → les valeurs sont retrouvées dans les attributs `value=` des champs texte et dans l'état Alpine initial (`x-data="rdvForm({...message: '...'})"` pour le textarea, car celui-ci est piloté par `x-model` et non par le contenu HTML brut). |
| 8 | Dimanche dans flatpickr | non sélectionnable | **conforme** | `resources/js/app.js` : `disable: [(date) => date.getDay() === 0]`. Vérifié en direct sur `/rdv/` : calendrier flatpickr ouvert, les dimanches du mois (ex. jour 9, un dimanche d'août 2026) portent la classe `flatpickr-disabled`. |
| 9 | Jour même | refusé côté serveur | **conforme** | Envoi avec `date` = date du jour (2026-08-03) → erreur retournée : « La date doit être dans le futur » (règle `Validator::validate()` : `$date <= $this->maintenant->setTime(0,0)`). |
| 10 | Demande valide | redirection `/confirmation?ref=…`, récapitulatif correct | **conforme** | Envoi complet et valide (Mariage, 2026-08-04, 08h00, mobile_money) → redirection `Location: http://localhost:8080/confirmation/?ref=...`. Page de confirmation : nom/prénom, type de consultation, date en français (« mardi 4 août 2026 »), heure, tous corrects. Lien WhatsApp prérempli présent (voir point 15). |
| 11 | Même créneau renvoyé | « Ce créneau est déjà réservé. » | **conforme** | Renvoi du même couple date/heure (2026-08-04 / 08h00, après expiration de la limite de fréquence) → erreur exacte : « Ce créneau est déjà réservé. Veuillez choisir une autre heure. » |
| 12 | Deux envois en moins d'une minute | second refusé | **conforme** | Deux envois valides consécutifs (créneaux différents) sans délai → premier accepté (redirection confirmation), second refusé avec : « Vous venez déjà d'envoyer une demande. Patientez une minute. » |
| 13 | Pot de miel rempli | demande non enregistrée | **conforme** | Champ caché `prophet_website` rempli → redirection vers `/rdv/` avec erreur générique « Votre demande n'a pas pu être traitée. », **aucun** enregistrement `rendez_vous` créé (compte inchangé avant/après : 2). |
| 14 | Emails | reçus par le client et par le prophète, `Reply-To` correct | **partiellement vérifiable** — voir détail | Pas de serveur SMTP en dev : `wp_mail` ne peut pas aboutir, aucune réception réelle ne peut être constatée (conforme aux attentes du brief). Vérifié via `wp eval` + filtre `pre_wp_mail` (court-circuite l'envoi réel, capture les paramètres) avec `Mailer`/`BladeRenderer` : les deux gabarits se rendent sans erreur PHP (corps HTML de 4550 et 1777 caractères), sujets en français corrects (« ✅ Confirmation de votre demande de RDV — Mariage » et « 📅 Nouveau RDV : Jean Dupont — Mariage — [date] »), `Reply-To` de l'email prophète correctement positionné sur l'email du client, phrases-clés attendues présentes dans le bon gabarit (« Votre demande de rendez-vous a bien été reçue » côté client, « Nouvelle demande de RDV » côté prophète). **Deux défauts trouvés à cette occasion, voir section Défauts.** |
| 15 | Bouton WhatsApp | ouvre `wa.me` avec le message prérempli | **conforme** | Deux emplacements vérifiés : (a) page de confirmation après envoi — lien `https://wa.me/+22897169090?text=...` avec récapitulatif complet encodé (nom, consultation, date, heure, pays, contact) ; (b) bouton flottant (`floating-wa__btn` → popup → `floating-wa__popup-btn`) — lien `https://wa.me/+22897169090?text=Bonjour%20Proph%C3%A8te...` avec message par défaut. |
| 16 | `/confirmation/` sans `ref` | « Demande introuvable », `noindex` | **conforme** | `curl -L http://localhost:8080/confirmation/` (sans paramètre) → texte « Demande introuvable » présent, balise `<meta name="robots" content="noindex, nofollow">` présente. |
| 16b | `wp option get blog_public` | `1` — sinon tout le site est en `noindex` | **DÉFAUT (attendu en dev, à vérifier impérativement en production)** | Valeur actuelle : **`0`**. Conséquence vérifiée : **toutes** les pages du site (pas seulement `/confirmation/`) renvoient actuellement `<meta name='robots' content='noindex, nofollow' />`, y compris la page d'accueil. C'est le comportement WordPress standard pour un environnement de développement (le tableau de bord admin affiche d'ailleurs « Bedrock: Search engine indexing has been discouraged because the current environment is `development` »). Cette valeur vit en base de données : **elle suivra une copie de base vers la production si elle n'est pas explicitement corrigée**, rendant le site invisible aux moteurs de recherche sans aucun signe visible. **Action requise avant mise en production : vérifier/forcer `blog_public=1`.** |
| 17 | Administration | 12 services, 6 témoignages, 6 photos, options renseignées | **conforme, avec une réserve** | Comptages `wp post list --format=count` : services = 12, témoignages = 6, photos = 6 (tous `post_status=publish`). Route Carbon Fields dédiée (`/cf-vendor/carbon-fields/*` dans `docker/Caddyfile.cms.dev`) : JS/CSS renvoient `200` (`core.min.js`, `metaboxes.min.css`). Vérifié en direct, connecté en admin : écran d'édition Service (« Mariage ») affiche la métabox « Détails du service » avec icône et sélecteur de couleur fonctionnels ; écran Témoignage affiche tous les champs dont le champ de relation vers Services ; écran Photo affiche le sélecteur d'image (vide — normal, cf. note 6 du brief : les images passent par la médiathèque, décision d'administrateur) ; les deux pages d'options « Contenu du site » et « Réglages RDV » se chargent et affichent des valeurs renseignées pour la plupart des champs (Hero, À propos, Chiffres, CTA, Pied de page, créneaux horaires 08h00→...). **Réserve : les champs « WhatsApp principal », « WhatsApp secondaire » et « Email de notification » de « Réglages RDV » sont vides** — voir Défauts. |
| 18 | Rendez-vous en administration | listés avec date, heure, type, statut | **conforme** | Un enregistrement de test temporaire créé via `Repository::create()` (Admin Verif, 2026-08-05, 09h00, Mariage) : la liste `Rendez-vous` de l'admin affiche les colonnes « Demandeur » (Admin Verif — 05/08/2026 09h00), « Date et heure » (2026-08-05 — 09h00), « Consultation » (Mariage), « Statut » (En attente). Enregistrement supprimé après vérification. |
| 19 | Modification d'un texte en administration | visible sur le site sans déploiement | **conforme** | Option Carbon Fields `hero_sous_titre` modifiée via `wp eval` (« Le Conseiller des Rois » → « TEST RECETTE - texte modifie ») : la nouvelle valeur apparaît immédiatement sur `curl http://localhost:8080/`, sans rebuild ni redémarrage. Valeur restaurée après test et vérifiée restaurée. |
| 20 | Suite PHPUnit | intégralement au vert | **conforme** | `OK (69 tests, 184 assertions)`. Aucun test ignoré ou risqué. Voir sortie complète plus bas. |

## Trois vérifications complémentaires demandées (au-delà du tableau)

- **`blog_public`** : voir ligne 16b ci-dessus.
- **Route des assets Carbon Fields** : voir ligne 17 ci-dessus — assets servis en `200` par la route Caddy dédiée, aucune erreur constatée, les 5 zones d'administration à champs personnalisés (Services, Témoignages, Photos, Contenu du site, Réglages RDV) rendent correctement.
- **Pagination des événements avec plus de 4 événements** : voir ligne 5 ci-dessus — mockée avec succès via un transient temporaire (aucune modification de code), comportement conforme, transient supprimé après test.

## Note technique — limitation de l'outil navigateur pour les tests de largeur exacte

Dans cette session, l'outil `resize_window` du navigateur n'a **pas modifié `window.innerWidth`** pour les onglets testés (valeur restée bloquée, ex. `768` malgré une demande de resize à `1440` puis `375`, ou `2560` sur un onglet neuf malgré une demande à `375`). Ce comportement a été vérifié plusieurs fois (nouvel onglet, onglet existant, avant/après navigation) et documenté au lieu d'être ignoré ou présenté comme un succès :
- Un onglet préexistant s'est avéré réellement figé à **768 px** (vraisemblablement laissé ainsi par une session antérieure) : utilisé tel quel pour le palier tablette, avec confirmation via `window.innerWidth`.
- Pour le palier desktop, l'onglet `/rdv/` préexistant était à **1512 px** réels (proche de 1440 sans l'être exactement) : utilisé en l'état pour la capture desktop.
- Pour le palier mobile (375 px), un contournement a été utilisé : une **iframe** de largeur CSS `375px` injectée dans une page vierge, dont le contenu (`http://localhost:8080/`) se rend dans son propre contexte de navigation avec ses propres media queries. `iframe.contentWindow.innerWidth` a confirmé **373 px** réels (perte de ~2 px due à la scrollbar), donnant un rendu mobile authentique (constaté : empilement vertical correct du hero, CTA pleine largeur, portrait sous le texte).

Cette limitation n'a affecté que le point 1 (rendu visuel aux trois largeurs) ; tous les autres points reposent sur des vérifications `curl`/`wp-cli`/DOM qui n'en dépendent pas.

## Défauts constatés

1. **Animation `fade-up` au défilement non fonctionnelle** (point 2). `resources/js/app.js` observe `[data-observe]` et bascule la classe `is-visible`, mais aucun gabarit Blade ne pose cet attribut et aucune règle CSS `.is-visible` n'existe (source et build). Effet : les sections s'affichent immédiatement au chargement/scroll, sans le fondu-montée d'origine. Vérifié également en runtime navigateur (0 cible, 0 élément avec la classe). À corriger : ajouter `data-observe` aux conteneurs de section concernés + une règle CSS (ex. opacité/translation de départ + transition sur `.is-visible`), par analogie avec l'existant `@keyframes fade-up` de `tokens.css` (actuellement utilisé pour d'autres animations ponctuelles, pas celle-ci).

2. **Aucune adresse de destination configurée pour l'email « nouveau RDV » au prophète** (points 14 et 17). `ProphetCore\Options::prophetEmail()` retourne une chaîne vide : ni le champ Carbon Fields « Email de notification » (Réglages RDV) ni la variable `.env` `PROPHET_EMAIL` ne sont renseignés dans cet environnement (`.env` contient `PROPHET_EMAIL=` vide). Conséquence vérifiée par `wp eval` : `Mailer::sendProphetNotification()` appelle `wp_mail('', ...)` — destinataire vide. Sans SMTP configuré ce défaut est masqué en dev, mais il subsistera telle quelle si `.env`/l'option ne sont pas complétés avant mise en production : le prophète ne recevrait jamais la notification de nouveau rendez-vous, même avec un SMTP fonctionnel. Les champs « WhatsApp principal » et « WhatsApp secondaire » du même écran sont eux aussi vides, mais sans conséquence visible car `Options::phone1()`/`phone2()` retombent sur `PROPHET_PHONE_1`/`PROPHET_PHONE_2` qui, eux, sont bien renseignés dans `.env`.

3. **Références résiduelles au Nuxt/api-bridge dans `cms/` (grep de l'étape 3)** — voir section suivante, défaut mineur (commentaires uniquement, aucun code fonctionnel).

## Étape 2 — Suite PHPUnit complète

```
docker compose -f docker-compose.cms.dev.yml run --rm app sh -c "cd /srv && vendor/bin/phpunit"
```

Résultat :
```
PHPUnit 10.5.64 by Sebastian Bergmann and contributors.
Runtime:       PHP 8.2.33
Configuration: /srv/phpunit.xml

................................................................. 65 / 69 ( 94%)
....                                                              69 / 69 (100%)

Time: 00:00.215, Memory: 16.00 MB

OK (69 tests, 184 assertions)
```
**Conforme** : `OK`, aucun test ignoré, correspond exactement à l'attendu (69 tests / 184 assertions).

## Étape 3 — Grep des références Nuxt résiduelles

```
grep -rIl --exclude-dir=vendor --exclude-dir=node_modules \
  -e 'api-bridge' -e 'MONGODB_URI' -e 'useFetch' cms/
```

Résultat : **4 fichiers correspondent** (attendu : `aucune référence`). Détail des occurrences :

| Fichier | Ligne | Contenu |
|---|---|---|
| `cms/web/app/themes/prophet/resources/css/sections/_videos.css` | 4 | commentaire : « ... il correspondait à l'état `pending` de `useFetch`, qui n'existe plus — ... » |
| `cms/web/app/mu-plugins/prophet-core/src/Support/Date.php` | 12 | commentaire PHPDoc : « Table reprise de `api-bridge/src/services/calendar.ts:6-9`. » |
| `cms/web/app/mu-plugins/prophet-core/src/Services/GoogleCalendar.php` | 108 | commentaire PHPDoc : « Port de `api-bridge/src/services/calendar.ts:66-97`. » |
| `cms/web/app/mu-plugins/prophet-core/src/Services/Youtube.php` | 105 | commentaire PHPDoc : « Port de `api-bridge/src/services/youtube.ts:52-68`. » |

**Verdict : défaut mineur.** Dans les quatre cas, il s'agit de **commentaires de traçabilité de provenance** (référence au fichier source d'origine dont le code a été porté), jamais de code fonctionnel, de configuration, ni de dépendance active. Aucune fonctionnalité ne dépend de `api-bridge`, `MONGODB_URI` ou `useFetch`. Ce résultat ne bloque pas la suite de la migration mais s'écarte littéralement de l'attendu « aucune référence » du brief ; à considérer avant la suppression du dossier `api-bridge/` (ces commentaires perdront leur sens une fois la source Nuxt supprimée — à charge de qui exécutera les étapes 4+ de nettoyer ces commentaires ou de les laisser tels quels en connaissance de cause).

## Données de test créées puis supprimées

- 3 enregistrements `rendez_vous` créés pendant les tests du formulaire (posts 49, 50 via `admin-post.php`, 51 via `Repository::create()` direct) — **tous supprimés** (`wp post delete --force`), compte `rendez_vous` revenu à 0 après recette.
- Transient `prophet_calendar_events` mocké à 5 entrées pour le test de pagination — **supprimé** (`wp transient delete`).
- Option Carbon Fields `hero_sous_titre` modifiée temporairement pour le test point 19 — **restaurée** à sa valeur d'origine (« Le Conseiller des Rois »), vérifiée restaurée.
- Aucune valeur de champ personnelle réelle (utilisateur final) n'a été journalisée : tous les noms/emails/téléphones utilisés dans les tests sont des personas fictifs créés pour la recette (« Jean Dupont », « Alice Martin », « Ama Kodjo », « Admin Verif »).

## Synthèse

- **Conforme** : 17 points (1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16, 18, 19, 20) — dont deux avec réserve documentée (1 et 17).
- **Défaut** : 2 points du tableau (2, 16b — 16b attendu en dev mais à corriger avant prod) + 1 constat hors-tableau (grep étape 3) + 1 défaut supplémentaire découvert en creusant le point 14/17 (email prophète sans destinataire).
- **Non vérifiable** : 0 point strictement — le point 14 (emails) est qualifié de « partiellement vérifiable » car la réception réelle est structurellement impossible à constater en dev (pas de SMTP), ce qui est le comportement attendu et documenté comme tel plutôt que présenté comme un échec.

**Recommandation avant l'étape 4 (suppression du Nuxt) :** aucun des défauts trouvés ne remet en cause l'équivalence fonctionnelle du port ni ne nécessite de conserver le code Nuxt pour investigation — ce sont des ajustements de configuration/CSS-JS localisés (email de notification, animation fade-up, `blog_public` en production, nettoyage optionnel de commentaires). La décision de poursuivre vers l'étape 4 revient à l'opérateur du projet.
