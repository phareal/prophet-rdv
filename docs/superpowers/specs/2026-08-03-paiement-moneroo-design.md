# Paiement en ligne des consultations — Moneroo

**Date** : 2026-08-03
**Statut** : spec à valider
**Dépend de** : la migration Bedrock + Sage (`2026-07-30-migration-bedrock-sage-design.md`), terminée

## Objectif

Permettre au visiteur de régler sa consultation en ligne au moment de la demande de
rendez-vous, via [Moneroo](https://moneroo.io) — un agrégateur qui expose en une
seule intégration le mobile money ouest-africain, les cartes et les virements.

Le paiement en ligne était explicitement **hors périmètre** de la migration : les
modes de paiement y sont déclaratifs, le visiteur choisit « Mobile Money » ou
« PayPal » et le règlement se fait hors du site. Cette spec lève cette limite.

## Principe directeur

**Le rendez-vous est enregistré avant toute redirection vers Moneroo, dans tous les
cas.** Un paiement abandonné, échoué ou expiré laisse une demande exploitable que le
prophète voit dans son administration et peut relancer. Jamais un trou.

Ce choix est structurant : il interdit le schéma « on encaisse d'abord, on enregistre
ensuite », qui perd la demande de tout visiteur dont le paiement échoue — soit, sur
du mobile money, une proportion loin d'être négligeable.

## Ce que le site fait aujourd'hui

| Élément | État |
|---|---|
| Formulaire de rendez-vous | `mode_paiement` parmi 4 valeurs déclaratives (`rdv_modes_paiement`, page d'options) |
| Enregistrement | CPT `rendez_vous`, statuts `prophet_en_attente` / `prophet_confirme` / `prophet_annule` |
| Prix | **n'existe nulle part** — ni dans le code, ni en base |
| Confirmation | `/confirmation/?ref=<jeton>` affiche le récapitulatif et un bouton WhatsApp |
| Notification | email au client et au prophète, envoyés après enregistrement |

## Décisions de conception

### 1. Les paramètres commerciaux vivent dans l'administration, pas dans le code

Rien de ce qui relève du commerce n'est écrit en dur :

| Paramètre | Où | Défaut |
|---|---|---|
| Prix | champ `service_prix` sur chaque CPT `service` | vide = gratuit, pas de paiement proposé |
| Devise | option `moneroo_devise` | `XOF` |
| Moment du paiement | option `moneroo_moment` | `facultatif` |
| Activation | option `moneroo_actif` | `false` |

Un mariage et une prophétie d'année n'ont aucune raison de coûter le même prix, et
le prophète doit pouvoir ajuster ses tarifs sans développeur — c'est la promesse
qu'a tenue la migration, elle vaut aussi ici.

### 2. Deux moments possibles, réglables, le plus sûr par défaut

**`facultatif` (défaut)** — le parcours actuel est inchangé. La demande est
enregistrée, les emails partent, et la page de confirmation propose en plus
« Régler maintenant » avec le montant. Le visiteur qui ne paie pas reste un
prospect que le prophète rappelle.

**`exige`** — après enregistrement, le visiteur est redirigé directement vers la
page de paiement Moneroo. Le rendez-vous porte le statut `prophet_en_attente` et
un paiement `en_attente`. S'il abandonne, il reçoit tout de même son email de
confirmation de demande, contenant un lien de reprise du paiement.

Le réglage est un choix de tunnel de conversion, pas une décision technique : il
appartient au prophète.

### 3. Le montant est calculé par le serveur, jamais transmis par le formulaire

Le prix vient du CPT `service` correspondant au `type_consultation` retenu, lu au
moment d'initialiser le paiement. Un montant qui transiterait par le navigateur
serait modifiable par le visiteur.

Cas particuliers :
- **Service sans prix** → aucun paiement n'est proposé ; le rendez-vous se comporte
  comme aujourd'hui, avec un statut de paiement `non_requis`.
- **Inscription à un événement** (`type_consultation` préfixé `Inscription — `) →
  aucun service ne correspond, donc `non_requis`. Les événements ont leurs propres
  modalités.

### 4. Le statut de paiement fait autorité côté serveur

Moneroo renvoie le visiteur sur notre `return_url` avec `paymentId` et
`paymentStatus` en paramètres d'URL. **Ces paramètres ne sont jamais crus** : ils
déclenchent une vérification `GET /v1/payments/{id}` dont la réponse seule met à
jour le rendez-vous. Un visiteur qui réécrit `paymentStatus=success` dans sa barre
d'adresse ne change rien.

Le webhook signé sert de filet : il rattrape les paiements confirmés après que le
visiteur a fermé son navigateur.

## Modèle de données

### Champs ajoutés

`service_prix` — champ `text` numérique sur le CPT `service`, en unités majeures de
la devise (pour XOF, des francs entiers ; Moneroo attend des unités majeures et
XOF a zéro décimale). Vide = pas de paiement pour ce service.

Page d'options « Paiement » :

| Champ | Type | Rôle |
|---|---|---|
| `moneroo_actif` | checkbox | coupe-circuit global |
| `moneroo_devise` | select | `XOF`, `XAF`, `NGN`, `GHS`, `EUR`, `USD` |
| `moneroo_moment` | select | `facultatif` / `exige` |
| `moneroo_texte_bouton` | text | libellé du bouton de règlement |
| `moneroo_delai_abandon` | text | minutes avant abandon automatique (défaut 30) |

Les clés d'API vivent dans `.env`, jamais en base : `MONEROO_SECRET_KEY`,
`MONEROO_WEBHOOK_SECRET`.

### Métadonnées du rendez-vous

Toutes préfixées par `RendezVous::metaKey()`, comme le reste :

| Champ | Contenu |
|---|---|
| `paiement_statut` | `non_requis`, `en_attente`, `paye`, `echoue`, `annule` |
| `paiement_id` | identifiant de transaction Moneroo |
| `paiement_montant` | montant en unités majeures, figé à l'initialisation |
| `paiement_devise` | devise, figée à l'initialisation |
| `paiement_methode` | moyen effectivement utilisé, renvoyé par Moneroo |
| `paiement_verifie_le` | horodatage de la dernière vérification serveur |

Le montant et la devise sont **figés au moment de l'initialisation**. Un changement
de tarif ne doit pas réécrire l'historique d'une transaction déjà engagée.

### Correspondance des statuts

| Moneroo | Notre statut |
|---|---|
| `initiated`, `pending` | `en_attente` |
| `success` | `paye` |
| `failed` | `echoue` |
| `cancelled` | `annule` |

## Parcours

### Mode facultatif

1. Le visiteur soumet le formulaire. Rien ne change à `SubmitHandler` : validation,
   contrôle de créneau, enregistrement, emails.
2. Si le service a un prix et que Moneroo est actif, le rendez-vous naît avec
   `paiement_statut = en_attente` ; sinon `non_requis`.
3. La page `/confirmation/?ref=…` affiche le récapitulatif et, le cas échéant, le
   montant et un bouton « Régler maintenant ».
4. Le bouton poste vers `admin-post.php` (`prophet_paiement_init`), qui initialise
   la transaction et redirige vers la `checkout_url`.
5. Retour sur `/confirmation/?ref=…&paymentId=…` → vérification serveur → mise à
   jour du statut → la page affiche « Paiement reçu » ou une invitation à réessayer.

### Mode exigé

Étapes 1 et 2 identiques. Puis `SubmitHandler` initialise le paiement et redirige
directement vers Moneroo au lieu de la page de confirmation. Le retour est le même.

Un rendez-vous resté `en_attente` de paiement au-delà de `moneroo_delai_abandon` est
basculé en `prophet_annule` par une tâche planifiée, ce qui **libère son créneau** —
sans quoi un abandon bloquerait une heure indéfiniment. Le client en est informé par
email, avec un lien pour reprendre.

### Webhook

Route REST `POST /wp-json/prophet/v1/moneroo/webhook`, publique et non authentifiée
au sens WordPress, mais protégée par la signature.

1. Lecture du corps **brut** (`php://input`) avant tout décodage.
2. `hash_hmac('sha256', $corps, MONEROO_WEBHOOK_SECRET)` comparé à l'en-tête
   `X-Moneroo-Signature` avec `hash_equals` — comparaison à temps constant.
3. Signature invalide → `403`, rien n'est traité.
4. Signature valide → le rendez-vous est retrouvé par `paiement_id`, le statut mis à
   jour, et la réponse `200` renvoyée **immédiatement** : Moneroo attend sous 3
   secondes et réessaie 3 fois à 10 minutes d'intervalle.
5. **Traitement idempotent** : recevoir deux fois `payment.success` ne doit produire
   qu'un seul effet. Un événement déjà appliqué est acquitté sans rien refaire.

## Sécurité

- Les clés vivent dans `.env`, jamais en base, jamais commitées, jamais journalisées.
- Le montant vient du serveur, jamais du client.
- Le `paymentStatus` de l'URL de retour n'est jamais cru.
- La signature du webhook est vérifiée avec `hash_equals`.
- La journalisation se fait par référence de rendez-vous et identifiant de
  transaction — **jamais** de nom, d'email, de téléphone ni de montant nominatif.
  La règle est déjà en vigueur dans `Mailer`.
- L'initialisation de paiement est soumise à la même limitation de débit par IP que
  la soumission de formulaire.
- Une transaction ne peut être initialisée que pour un rendez-vous dont la référence
  est fournie ; la référence est un jeton aléatoire de 32 caractères, non énumérable.
- Un rendez-vous déjà `paye` refuse une seconde initialisation.

## Administration

- Colonne « Paiement » dans la liste des rendez-vous, à côté du statut de la demande.
- Le panneau « Demande » affiche montant, devise, moyen et identifiant de transaction,
  en lecture seule.
- Filtre par statut de paiement.
- Le prophète peut marquer un rendez-vous comme réglé hors ligne — le mobile money
  de la main à la main reste courant, et une donnée qui ne reflète pas la réalité ne
  sert personne.

## Tests

Sur le métier, avec les appels HTTP simulés par `pre_http_request` :

- construction de la charge utile d'initialisation, dont le montant lu depuis le
  service et non depuis l'entrée utilisateur ;
- correspondance des cinq statuts Moneroo vers les nôtres ;
- vérification de signature : signature valide, invalide, absente, corps modifié ;
- idempotence : le même événement appliqué deux fois ne produit qu'un effet ;
- refus d'initialiser pour un rendez-vous déjà réglé ;
- service sans prix et inscription à un événement → `non_requis` ;
- l'abandon au-delà du délai annule le rendez-vous et libère le créneau.

## Hors périmètre

- Remboursements — se font depuis le tableau de bord Moneroo.
- Paiements partiels, acomptes, échéanciers.
- Facturation et documents comptables.
- Versements du prophète (`payouts` Moneroo).
- Devises multiples simultanées : une seule devise à la fois pour tout le site.

## Risques

| Risque | Traitement |
|---|---|
| Un abandon en mode exigé bloque un créneau | Tâche planifiée d'annulation après délai réglable, qui libère le créneau |
| Webhook reçu avant le retour navigateur, ou l'inverse | Traitement idempotent des deux côtés ; le premier arrivé fait foi, le second est sans effet |
| Moneroo injoignable à l'initialisation | Le rendez-vous est déjà enregistré ; message d'erreur et proposition de réessayer |
| Changement de tarif en cours de transaction | Montant et devise figés à l'initialisation |
| Devise mal supportée | Vérifier la liste réelle des devises Moneroo avant de figer la liste du select |
| Clé d'API en base par mégarde | Les clés ne sont lues que depuis `.env`, aucun champ d'administration ne les expose |
