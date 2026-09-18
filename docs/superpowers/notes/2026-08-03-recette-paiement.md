# Recette — Paiement en ligne Moneroo

Branche `feat/migration-bedrock-sage`. Recette menée le 2026-08-04 sur
l'environnement Docker local (`docker-compose.cms.dev.yml`, site
`http://localhost:8080`), après la tâche 12 (147 tests / 342 assertions
verts au départ, confirmé de nouveau à la fin — voir point 17).

Aucune clé Moneroo réelle n'a été utilisée à aucun moment. Deux valeurs
jetables ont servi le temps des tests :

- `MONEROO_SECRET_KEY=cle_test_invalide_recette` — volontairement invalide,
  pour observer le comportement de repli sans jamais réussir un paiement réel.
- `MONEROO_WEBHOOK_SECRET=secret_test_recette_webhook` — nécessaire pour
  signer des charges utiles de test.

Les deux ont été ajoutées à `cms/.env` (fichier non versionné) pour la durée
de la recette, puis **retirées** ; `cms/.env` est revenu bit à bit identique
à son état de départ (vérifié par diff).

## Tableau de recette

| # | Vérification | Verdict | Preuve |
|---|---|---|---|
| 1 | Paiement désactivé | **conforme** | `moneroo_actif=false`, RDV créé sur le service « Mariage » (prix 35000 XOF) → confirmation sans aucun bloc paiement. Post #60, `_rdv_paiement_statut=non_requis`. |
| 2 | Activé, service sans prix | **conforme** | `moneroo_actif=true` (restauré), RDV sur « Anges » (aucun prix) → aucun bloc paiement. Post #61, `_rdv_paiement_statut=non_requis`. |
| 3 | Activé, prix posé, mode facultatif | **conforme** | RDV sur « Mariage » (35000 XOF), mode `facultatif` → confirmation affiche « Montant à régler : 35000 XOF » + bouton « Régler ma consultation ». Post #62. |
| 4 | Clic sur le bouton → redirection `checkout.moneroo.io` | **non vérifiable** (succès) | Le bouton poste bien vers `admin-post.php?action=prophet_paiement_init&ref=...` (formulaire GET vérifié dans le DOM). En cliquant, `InitHandler` a réellement contacté `https://api.moneroo.io/v1/payments/initialize` (le conteneur a un accès sortant confirmé par `curl` direct → `401`) : avec la clé jetable invalide, Moneroo a répondu `401 Unauthorized`, journalisé sans donnée personnelle, et le visiteur est retombé sur `/confirmation/?ref=...&paiement=erreur` avec le montant toujours affiché. La redirection réelle vers `checkout.moneroo.io` exige un compte Moneroo valide, explicitement exclu de cette recette (« no real API key anywhere »). Le chemin de succès est couvert par `InitHandlerTest` (6 tests, mockés, verts dans la suite). |
| 5 | Retour avec `paymentId`, statut vérifié côté serveur, affichage cohérent | **non vérifiable** (succès) ; voie d'échec **conforme** | `RetourHandler::verifier()` a bien été invoqué avec le `paymentId` de l'URL (post #62, `paiement_id=tx_test_recette_point6`) ; l'appel réel à Moneroo a échoué (`401`, clé jetable), le statut est resté `en_attente` et la page a continué à s'afficher normalement (montant + bouton), sans incohérence ni erreur visible. Le mappage de succès (`initiated/pending/success/failed/cancelled`) est couvert par `RetourHandlerTest`, vert. La confirmation d'un vrai paiement réussi nécessiterait un compte Moneroo réel. |
| 6 | Retour avec `paymentStatus=success` falsifié | **conforme** | Même post #62 (`paiement_id=tx_test_recette_point6`, statut `en_attente`) : appel de `/confirmation/?ref=...&paymentId=tx_test_recette_point6&paymentStatus=success`. Statut **avant** : `en_attente`. Statut **après** : `en_attente`, inchangé. Log : `[Paiement] vérification impossible (transaction tx_test_recette_point6) : Moneroo a répondu 401 : Unauthorized : Invalid API Key` — la vérification a bien été tentée côté serveur, `paymentStatus` n'a jamais été lu (grep du code : `RetourHandler::verifier(int, string)` ne prend pas ce paramètre). Reproduit ce qu'une vérification précédente (tâche 12) avait déjà montré, cette fois avec un vrai aller-retour réseau raté plutôt qu'une absence de clé. |
| 7 | Webhook sans signature | **conforme** | `curl -X POST .../moneroo/webhook` sans en-tête de signature → `403`. Statut du post cible inchangé. |
| 8 | Webhook signé, `payment.success` | **conforme** | Post #60 réinitialisé (`paiement_id=tx_webhook_test_1`, statut `en_attente`), charge `{"event":"payment.success","data":{"id":"tx_webhook_test_1","status":"success","payment_method":"mtn_bj"}}` signée en HMAC-SHA256 avec `secret_test_recette_webhook` → `200`. Après : `{"statut":"paye","id":"tx_webhook_test_1","montant":35000,"devise":"XOF","methode":"mtn_bj"}`. |
| 9 | Même webhook rejoué | **conforme** | `_rdv_paiement_verifie_le` relevé avant (`2026-08-04 10:55:07`) et après un second envoi strictement identique (même corps, même signature) → `200` renvoyé de nouveau, mais **timestamp et statut inchangés** (`2026-08-04 10:55:07`, `paye`) : aucun second effet. |
| 10 | Webhook pour une transaction inconnue | **conforme** | Charge signée avec `data.id=tx_inconnu_recette_999` (n'existe sur aucun rendez-vous) → `200`. Log : `[Paiement] webhook pour une transaction inconnue : tx_inconnu_recette_999`. `PaiementRepository::trouverParPaiementId()` renvoie `NULL` après coup : rien n'a été écrit. |
| 11 | Rendez-vous déjà réglé, nouvelle initialisation | **conforme** | Post #62 marqué `paye` (`appliquerStatut`), puis `GET admin-post.php?action=prophet_paiement_init&ref=...` → refus immédiat, **sans aucun appel réseau** (log : `[Paiement] initialisation impossible (réf. ...) : Rendez-vous déjà réglé`, contrairement aux échecs `401` des autres points qui, eux, portent la trace d'un appel réseau réel). Page renvoyée : `/confirmation/?ref=...&paiement=erreur`, affichant tout de même « Paiement reçu — 35000 XOF » (l'état réel prévaut). |
| 12 | Mode exigé, demande valide → redirection directe vers Moneroo | **non vérifiable** (succès) | `moneroo_moment=exige` activé, RDV soumis sur « Mariage ». Le code a immédiatement tenté `InitHandler::demarrer()` après l'enregistrement (avant tout retour au visiteur), confirmé par le log horodaté à la même seconde que la création : `[RDV] paiement non initialisable (réf. qf2i1yfF027uH0J6TKHR0zxPZpAbCPmH) : Moneroo a répondu 401 : Unauthorized : Invalid API Key`. La redirection réelle vers Moneroo (chemin de succès) exige un compte valide — hors périmètre. Couvert par `SubmitHandlerTest::test_en_mode_exige_le_paiement_redirige_vers_moneroo` (mocké, vert). |
| 13 | Mode exigé, Moneroo injoignable | **conforme** | Suite du même envoi (point 12) : malgré l'échec Moneroo (`401`, simulé via clé invalide comme suggéré par le brief), le rendez-vous a bien été enregistré — post #63 créé, `post_status=prophet_en_attente` — et le visiteur a atterri sur `/confirmation/?ref=qf2i1yfF027uH0J6TKHR0zxPZpAbCPmH` (pas de page d'erreur, pas de blocage). Aucune demande perdue. |
| 14 | Cron d'abandon en mode exigé | **conforme** | Post #63 (mode exigé, `paiement_statut=en_attente`) daté artificiellement à `2026-08-04 08:00:00` (`delai_abandon=45` min, largement dépassé). `wp eval '(new ProphetCore\Paiement\Abandon())->passer()'` → `1`. Après : `post_status=prophet_annule`, `_rdv_paiement_statut=annule`. Créneau vérifié libéré : `Repository::slotTaken('2026-08-17','08h00')` → `false` (le statut annulé n'est plus compté). Log : `[Paiement] 1 rendez-vous annulés faute de règlement` — aucune donnée personnelle. |
| 15 | Cron d'abandon en mode facultatif | **conforme** | `moneroo_moment` remis à `facultatif`, nouveau post #64 (`paiement_statut=en_attente`) daté également à `2026-08-04 08:00:00`. `passer()` → `0`. Après : post #64 inchangé (`post_status=prophet_en_attente`, `paiement_statut=en_attente`). |
| 16 | Journaux | **conforme** | Voir section dédiée ci-dessous. |
| 17 | Suite PHPUnit | **conforme** | `147 tests, 342 assertions`, OK — avant et après la recette (aucune modification de code métier). |

## Point 16 — détail des journaux

Journal applicatif : `cms/web/app/debug.log` (conteneur `app`). Toutes les
lignes produites pendant la recette (04-Aug-2026, 10:30 UTC → 10:56 UTC) :

```
[04-Aug-2026 10:33:40 UTC] [Mailer] envoi en échec pour le rendez-vous (réf. Zhq5rX8Cdc9e86eFFVXVgTgfgqYxV3w6)
[04-Aug-2026 10:33:40 UTC] [Mailer] destinataire vide pour le rendez-vous (réf. Zhq5rX8Cdc9e86eFFVXVgTgfgqYxV3w6) — renseigner le champ « Email de notification » ...
[04-Aug-2026 10:35:23 UTC] [Mailer] envoi en échec pour le rendez-vous (réf. nbndQA5nMBBQuikB5VqlqMCtOH4clVTS)
[04-Aug-2026 10:35:23 UTC] [Mailer] destinataire vide pour le rendez-vous (réf. nbndQA5nMBBQuikB5VqlqMCtOH4clVTS) — ...
[04-Aug-2026 10:36:44 UTC] [Mailer] envoi en échec pour le rendez-vous (réf. VDb5hss5IV3LDUzeaINsqNN8Yre8Z2fs)
[04-Aug-2026 10:36:44 UTC] [Mailer] destinataire vide pour le rendez-vous (réf. VDb5hss5IV3LDUzeaINsqNN8Yre8Z2fs) — ...
[04-Aug-2026 10:37:28 UTC] [Paiement] initialisation impossible (réf. VDb5hss5IV3LDUzeaINsqNN8Yre8Z2fs) : Moneroo a répondu 401 : Unauthorized : Invalid API Key
[04-Aug-2026 10:38:10 UTC] [Paiement] vérification impossible (transaction tx_test_recette_point6) : Moneroo a répondu 401 : Unauthorized : Invalid API Key
[04-Aug-2026 10:38:39 UTC] [Paiement] initialisation impossible (réf. VDb5hss5IV3LDUzeaINsqNN8Yre8Z2fs) : Rendez-vous déjà réglé
[04-Aug-2026 10:40:15 UTC] [RDV] paiement non initialisable (réf. qf2i1yfF027uH0J6TKHR0zxPZpAbCPmH) : Moneroo a répondu 401 : Unauthorized : Invalid API Key
[04-Aug-2026 10:40:15 UTC] [Mailer] envoi en échec pour le rendez-vous (réf. qf2i1yfF027uH0J6TKHR0zxPZpAbCPmH)
[04-Aug-2026 10:40:15 UTC] [Mailer] destinataire vide pour le rendez-vous (réf. qf2i1yfF027uH0J6TKHR0zxPZpAbCPmH) — ...
[04-Aug-2026 10:41:19 UTC] [Paiement] 1 rendez-vous annulés faute de règlement
[04-Aug-2026 10:42:46 UTC] [Mailer] envoi en échec pour le rendez-vous (réf. DKWH9to3LhpfHFlJ5gLrxCDGIps7qtBZ)
[04-Aug-2026 10:42:46 UTC] [Mailer] destinataire vide pour le rendez-vous (réf. DKWH9to3LhpfHFlJ5gLrxCDGIps7qtBZ) — ...
[04-Aug-2026 10:43:56 UTC] [Paiement] webhook à signature invalide rejeté
[04-Aug-2026 10:47:03 UTC] [Paiement] webhook à signature invalide rejeté
[04-Aug-2026 10:47:16 UTC] [Paiement] initialisation impossible (réf. DKWH9to3LhpfHFlJ5gLrxCDGIps7qtBZ) : Moneroo a répondu 401 : Unauthorized : Invalid API Key
[04-Aug-2026 10:55:42 UTC] [Paiement] webhook pour une transaction inconnue : tx_inconnu_recette_999
```

(Deux lignes « signature invalide » : la première correspond au point 7 ; la
seconde à une erreur de manipulation de ma part — un premier essai de
signature s'est retrouvé vide à cause d'un `awk` mal calé côté shell, ce qui
a produit une seconde signature invalide avant que je corrige et obtienne le
`200` du point 8. Aucun impact sur le verdict, mentionné pour la traçabilité.)

Recherche systématique sur ces lignes (et sur l'ensemble du fichier, par
prudence) :

```
grep -inE "TestRecette|Point[0-9]"                    → aucune occurrence de nom de test
grep -inE "@example\.test|recette\.point"              → aucune occurrence d'email de test
grep -inE "\+2289000000"                                → aucune occurrence de téléphone de test
grep -in  "35000"                                       → aucune occurrence de montant
grep -inE "cle_test_invalide_recette|secret_test_..."   → aucune occurrence des clés jetables
```

**Aucune ligne produite pendant la recette ne contient de nom, d'email, de
téléphone, de montant ou de clé d'API.** Toutes les lignes journalisent par
référence de rendez-vous (jeton de 32 caractères) ou par identifiant de
transaction Moneroo (`tx_...`, choisi par moi pour ces tests) — conforme à
la règle de la spec.

**Résidu historique hors périmètre** (signalé pour complétude, pas un défaut
de cette recette) : le fichier `debug.log` contient aussi deux lignes plus
anciennes, du 03-Aug-2026 09:58:01 UTC, antérieures à cette session :

```
[03-Aug-2026 09:58:01 UTC] [Mailer] envoi en échec vers jean@example.test — « ✅ Confirmation de votre demande de RDV — Mariage »
[03-Aug-2026 09:58:01 UTC] [Mailer] envoi en échec vers  — « 📅 Nouveau RDV : Jean Testeur — Mariage — mardi 11 août 2026 »
```

Ces deux lignes contiennent un email et un nom en clair — mais elles ne
viennent pas du code actuel : `Services/Mailer.php:97` (code présent dans
la branche testée ici) ne journalise plus que la référence
(`error_log('[Mailer] envoi en échec pour le rendez-vous (' . $reference . ')');`),
comme le confirment toutes les lignes `[Mailer]` produites pendant cette
recette (voir liste ci-dessus, aucune ne contient de donnée personnelle).
Ces deux lignes sont un résidu d'une version antérieure du fichier, restée
dans le log persistant du conteneur ; elles ne reflètent pas le comportement
du code présent sur cette branche et n'appellent aucune action dans le cadre
de cette tâche.

## Ce qui a été modifié pour les tests puis restauré

| Réglage | Valeur d'origine | Valeur pendant les tests | Restauré |
|---|---|---|---|
| `moneroo_actif` | `true` | `false` (point 1), puis `true` | oui — `true` |
| `moneroo_moment` | `facultatif` | `exige` (points 12–14), puis `facultatif` | oui — `facultatif` |
| `moneroo_devise` | `XOF` | inchangé | — |
| `moneroo_delai_abandon` | `45` | inchangé (utilisé tel quel) | — |
| `cms/.env` — `MONEROO_SECRET_KEY` | absent | `cle_test_invalide_recette` (jetable) | oui — retiré |
| `cms/.env` — `MONEROO_WEBHOOK_SECRET` | absent | `secret_test_recette_webhook` (jetable) | oui — retiré |
| Prix des services (« Mariage » 35000 XOF, « Année 2026 » 25000 XOF) | — | non modifiés, seulement lus | — (rien à restaurer) |

## Rendez-vous de test créés et supprimés

Postes `rendez_vous` #60 à #64 (« Point1 » à « Point4 », « Point15 »),
créés via le vrai formulaire `/rdv/` (mode d'obtention identique à un
visiteur réel) ou par script pour les cas de webhook. **Tous supprimés**
(`wp post delete --force`) en fin de recette ; `wp post list
--post_type=rendez_vous --post_status=any` renvoie une liste vide après
nettoyage.

## Concerns

- Les points 4, 5 (voie de succès) et 12 nécessitent un compte Moneroo réel
  pour être vérifiés de bout en bout ; c'est structurel à la contrainte « no
  real API key anywhere » de cette tâche, pas une lacune du code. Le chemin
  de succès de chacun est couvert par des tests unitaires verts
  (`InitHandlerTest`, `RetourHandlerTest`, `SubmitHandlerTest`), et le
  chemin d'échec a été vérifié en direct pour les trois.
- Le résidu de log du 03-Aug-2026 (email et nom en clair) mérite d'être
  purgé du conteneur de développement si ce fichier sert un jour de preuve
  d'audit — sans action de code nécessaire, le code actuel ne les
  reproduit plus.
