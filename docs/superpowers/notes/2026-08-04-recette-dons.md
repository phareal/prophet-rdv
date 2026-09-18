# Recette — Motifs de paiement et dons

**Date** : 2026-08-05
**Environnement** : `http://localhost:8080`, compose `prophet_cms_dev`, branche
`feat/migration-bedrock-sage`.
**Suite de tests avant recette** : 182 tests / 418 assertions, verte.
**Suite de tests après recette** : 182 tests / 418 assertions, verte (inchangée).

**Limite connue de l'environnement** : `MONEROO_SECRET_KEY` n'est renseignée
nulle part (`cms/.env` ne contient aucune variable `MONEROO_*` d'API). Le
chemin de paiement réussi (redirection effective vers l'interface de paiement
Moneroo et retour `success`) est donc **inatteignable ici**. Chaque point qui
en dépend le signale explicitement plutôt que d'être noté « conforme » à
tort. En revanche, l'absence de clé produit exactement le même comportement
que « Moneroo injoignable » (`MonerooException` levée avant tout appel
réseau), ce qui a permis de vérifier fidèlement les chemins d'échec.

## Matériel de test

- Motif réel préexistant : **Dîmes** (post 65, `/don/dimes/`), régime
  **libre**, bornes 500 / 5 000 000 XOF — inchangé par la recette.
- Deux motifs créés pour couvrir les régimes manquants, supprimés en fin de
  recette :
  - **Offrandes** (post 78, `/don/offrandes/`), régime **suggéré**, paliers
    1 000 / 5 000 / 10 000 XOF.
  - **Alliance** (post 79, `/don/alliance/`), régime **fixe**, 25 000 XOF.
- `MONEROO_WEBHOOK_SECRET=recette_test_webhook_secret` ajoutée temporairement
  à `cms/.env` pour signer les requêtes de webhook des points 14 et 15 (valeur
  de test explicitement nommée, aucune clé réelle) — retirée après usage.
- `zbarimg` (zbar) installé sur l'hôte via Homebrew pour décoder les QR
  produits, comme l'exige le point 17.

## Tableau de recette

| # | Vérification | Attendu | Verdict | Preuve |
|---|---|---|---|---|
| 1 | `/don/` sans motif actif | message clair, pas d'erreur | **Conforme** | Les 3 motifs désactivés temporairement ; `GET /don/` → HTTP 200, corps : « Aucun motif de don n'est disponible pour le moment. Merci de repasser plus tard. » Motifs réactivés ensuite. |
| 2 | Trois motifs actifs | tous listés dans le sélecteur | **Conforme** | `GET /don/` contient les 3 `<option>` (65 Dîmes, 78 Offrandes, 79 Alliance) dans le `<select name="motif">`. |
| 3 | Lien profond `/don/dimes/` | motif présélectionné | **Conforme** | `GET /don/dimes/` → `<option value="65" selected>` rendu côté serveur. |
| 4 | Lien profond d'un motif désactivé | message, liste des motifs disponibles | **Conforme** | Alliance désactivée puis `GET /don/alliance/` → HTTP 200, « Le motif « Alliance » n'est plus disponible. Choisissez parmi les motifs proposés ci-dessous. », sélecteur ne proposant plus qu'Offrandes et Dîmes. Alliance réactivée ensuite. |
| 5 | Régime libre | saisie acceptée dans les bornes | **Conforme** | Soumission motif 65 (libre), `montant=2500` → don créé (« Jean Test — Dîmes 2500 XOF »), pas d'erreur de validation. |
| 6 | Montant sous le minimum | refusé, message serveur | **Conforme** | `montant=100` (< 500) sur motif libre → redirection `/don/?e=…#don`, message sous le champ : « Le montant minimum est de 500 XOF ». |
| 7 | Montant au-dessus du maximum | refusé | **Conforme** | `montant=9999999` (> 5 000 000) → « Le montant maximum est de 5000000 XOF ». |
| 8 | Montant non entier, négatif, `1e5` | refusés | **Conforme** | `-500`, `1e5`, `2500.50` refusés séparément, chacun avec « Veuillez saisir un montant en chiffres entiers » sous le champ. |
| 9 | Régime fixe, montant falsifié dans la requête | montant du motif retenu | **Conforme** | Motif Alliance (fixe, 25 000 XOF) soumis avec `montant=1` → don créé titré « Falsif Teur — Alliance **25000** XOF » : le montant falsifié est ignoré, celui du motif fait foi. |
| 10 | Régime suggéré, valeur hors palier dans les bornes | acceptée | **Conforme** | Motif Offrandes (paliers 1000/5000/10000), soumis `montant=3000` → don créé « Hors Palier — Offrandes 3000 XOF », accepté bien que hors paliers. |
| 11 | Don valide | enregistré puis redirection Moneroo | **Partiellement vérifiable** | « Enregistré » vérifié à de nombreuses reprises (points 5, 9, 10, 18, 19 : chaque don valide crée bien un article `don`). La redirection **effective** vers l'interface Moneroo est **non vérifiable ici** : sans `MONEROO_SECRET_KEY`, `InitHandler::demarrer()` lève systématiquement une `MonerooException` avant tout appel réseau, et le visiteur est renvoyé vers `/don/merci/?...&paiement=erreur` — comportement identique à celui vérifié au point 12. |
| 12 | Moneroo injoignable | don enregistré, page de remerciement avec message | **Conforme** | Chaque soumission valide (points 5, 9, 10…) provoque cet échec d'initialisation (absence de clé = injoignable du point de vue du code) ; la page `/don/merci/` affiche : « Le paiement n'a pas pu être lancé. Votre don est bien enregistré — vous pouvez réessayer ci-dessous. » et le don est bien présent en base. |
| 13 | Retour avec `paymentStatus` falsifié | statut inchangé | **Conforme** | Don en attente (réf. `ldypfMKli50…`, aucune méta `_don_paiement_statut`) appelé via `GET /don/merci/?ref=…&paymentStatus=success&paymentId=tx_forge_123` → page affiche toujours « En attente de paiement », **aucune** méta de paiement écrite avant/après (vérifié par `wp post meta list`). `RetourHandler::verifier()` ne lit jamais `paymentStatus` ; il a tenté d'interroger Moneroo pour `paymentId`, l'appel a échoué (clé absente) et le statut existant a été renvoyé tel quel. |
| 14 | Webhook signé sur un don | don marqué reçu | **Conforme** (avec réserve, voir défaut D1) | Don de test avec `_don_paiement_id=tx_recette_test_1`. Webhook HMAC-SHA256 valide (`x-moneroo-signature`) avec `status: "success"` → HTTP 200, méta `_don_paiement_statut` passée de `en_attente` à `paye` (« Réglé », colonne Statut de la liste Dons). **Réserve** : le `post_status` natif WordPress du don reste `don_en_attente` — voir défaut D1 ci-dessous. |
| 15 | Webhook rejoué | aucun second effet | **Conforme** | Même webhook rejoué 2 s plus tard → HTTP 200, mais `_don_paiement_statut` (`paye`) et `_don_paiement_verifie_le` (`2026-08-05 14:34:59`) strictement identiques avant/après : `PaiementRepository::appliquerStatut()` refuse d'écraser un statut déjà final. |
| 16 | QR scanné au téléphone | ouvre le bon motif | **Conforme** (méthode adaptée) | Aucun téléphone physique disponible dans cet environnement. Substitué par un décodage réel : `zbarimg` sur `motif-78.png` → contenu exact `http://localhost:8080/don/offrandes/` ; `curl` de cette URL confirme `<option value="78" selected>` (motif Offrandes bien présélectionné). |
| 17 | Identifiant du motif modifié | QR régénéré vers la nouvelle URL | **Conforme** | Slug d'Offrandes changé `offrandes` → `offrandes-nouveau` : `motif-78.png/.svg` supprimés immédiatement (hook `post_updated` sur `MotifPaiementFields`). Rappel de `QrCode::pour(78,'png')` → fichier régénéré, **décodé** avec `zbarimg` : contenu exact `http://localhost:8080/don/offrandes-nouveau/` (pas seulement visuellement inspecté). `curl` de cette URL → HTTP 200. Slug restauré à `offrandes` ensuite, QR régénéré une dernière fois vers l'URL d'origine. |
| 18 | Deux dons de deux emails depuis la même IP | tous deux acceptés | **Conforme** | Deux soumissions consécutives (même `curl`, donc même IP), motif Dîmes, `recette-p18-a@example.test` puis immédiatement `recette-p18-b@example.test` → **les deux** dons créés (« Alice Assemblee — Dîmes 1500 XOF » et « Bernard Assemblee — Dîmes 1800 XOF »), aucun blocage. |
| 19 | Deux dons du même email en rafale | second refusé | **Conforme** | Même email (`recette-p19@example.test`) soumis deux fois immédiatement : le premier crée le don, le second est refusé avec « Vous venez déjà d'envoyer un don. Patientez un instant. » — un seul don créé. |
| 20 | Export CSV | accents corrects dans un tableur | **Conforme** | Export téléchargé via `admin-post.php?action=prophet_export_dons` (session admin authentifiée, nonce du bouton d'export) : `Content-Type: text/csv; charset=utf-8`, fichier identifié `Unicode text, UTF-8 (with BOM)` (`EF BB BF` en tête), en-tête et lignes affichant correctement « Dîmes », « Prénom », « Téléphone », « Référence », « Réglé ». |
| 21 | Journaux | aucune donnée personnelle, aucune clé | **Conforme pour le module Don** (voir note hors périmètre ci-dessous) | Voir section « Journaux » ci-dessous : grep exhaustif de toutes les lignes produites par cette recette (`[Don]`, `[Paiement]`) — uniquement références et identifiants de transaction, jamais de nom, email, téléphone, montant ou clé. |
| 22 | Suite PHPUnit | verte | **Conforme** | `182 tests, 418 assertions` avant et après la recette, aucun changement de code. |

## QR décodé (point 17, détail)

```
Avant changement de slug   : http://localhost:8080/don/offrandes/
Après changement de slug   : http://localhost:8080/don/offrandes-nouveau/   (décodé par zbarimg, fichier régénéré)
Après restauration du slug : http://localhost:8080/don/offrandes/           (décodé par zbarimg, fichier régénéré)
```

Chaque URL décodée a été vérifiée fonctionnelle avec `curl` (HTTP 200, motif
correctement résolu).

## Journaux (point 21, détail)

Fichier inspecté : `/srv/web/app/debug.log` dans le conteneur `app` (seule
destination des `error_log()` de l'application ; le journal d'accès FastCGI ne
contient que méthode/chemin/code, jamais les corps de requête ni les chaînes
de requête).

**Lignes produites par cette recette** (module `Don`/`Paiement`, du
2026-08-05 14:19 au 2026-08-05 17:25) :

```
[Don] paiement non initialisable (réf. ldypfMKli50ahklN1oIVPZ09yU9ra5rf) : MONEROO_SECRET_KEY absente de la configuration
[Don] paiement non initialisable (réf. XFT8Orrk0NwxoYquQw7VZiFw1pg313QZ) : MONEROO_SECRET_KEY absente de la configuration
[Don] paiement non initialisable (réf. ET0XFBYfV9AvDJTQNXgKGNJylvRc6VaY) : MONEROO_SECRET_KEY absente de la configuration
[Paiement] vérification impossible (transaction tx_forge_123) : MONEROO_SECRET_KEY absente de la configuration
[Don] paiement non initialisable (réf. QU507DZ2q9txRKey2SIUZkwUi2imVqTb) : MONEROO_SECRET_KEY absente de la configuration
[Don] paiement non initialisable (réf. cQUYTPbSNDx9phwqPugZNzFjKJBB8ylz) : MONEROO_SECRET_KEY absente de la configuration
[Don] paiement non initialisable (réf. 306FyMJGaPoniSRKgz9IBwld5bZPhN3q) : MONEROO_SECRET_KEY absente de la configuration
```

Grep ciblé sur ces lignes pour un motif d'email (`[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}`),
un motif de téléphone (`\+?[0-9]{8,}`), une clé (`sk_`, `secret_key=`, `Bearer `)
ou un montant : **aucune correspondance**. Seuls la référence du don (un jeton
aléatoire non énumérable) et l'identifiant de transaction Moneroo apparaissent
— exactement ce que la contrainte du plan impose.

**Note hors périmètre, trouvée en cours de grep (non liée à la tâche 10)** :
le même fichier contient des lignes plus anciennes (03-08-2026), produites par
le module `Mailer` du **rendez-vous** (pas des dons), qui journalisent
l'adresse email du destinataire en cas d'échec d'envoi, par exemple :

```
[03-Aug-2026 09:58:01 UTC] [Mailer] envoi en échec vers jean@example.test — « ✅ Confirmation de votre demande de RDV — Mariage »
```

Ce n'est ni produit par cette recette ni dans le périmètre de la tâche 10
(motifs de paiement / dons) — signalé pour information, non corrigé,
conformément à la consigne de ne rien réparer dans cette tâche.

## Défauts constatés

### D1 — Le statut natif WordPress d'un don ne reflète jamais le paiement

**Où** : `cms/web/app/mu-plugins/prophet-core/src/PostTypes/Don.php` (constantes
`STATUT_RECU`, `STATUT_ECHOUE`, `register_post_status()`) et
`cms/web/app/mu-plugins/prophet-core/src/Don/DonRepository.php` /
`PaiementRepository::appliquerStatut()`.

**Ce qui a été fait** : création d'un don, initialisation (échouée faute de
clé), puis webhook signé appliquant le statut `paye`. Comparaison du
`post_status` réel avant/après avec `wp eval 'print_r(wp_count_posts("don"))'`.

**Attendu** : `Don::STATUT_RECU` (`don_recu`) et `Don::STATUT_ECHOUE`
(`don_echoue`) sont déclarés, enregistrés (`register_post_status()`, avec
`protected => true`, correctement) et même traduits (`Don::statutLabel()`) —
tout indique qu'un don devait transitionner vers ces statuts natifs au fil du
paiement, exactement comme le rendez-vous change de statut WordPress.

**Ce qui se passe réellement** : aucun appel à `wp_update_post()` n'existe
nulle part dans `src/Don/` ni dans `PostTypes/Don.php` (vérifié par grep). Le
`post_status` d'un don reste `don_en_attente` **pour toujours**, y compris
après un don réglé par webhook (vérifié : `wp_count_posts('don')` montre le
don sous `don_en_attente`, jamais sous `don_recu`, malgré `_don_paiement_statut
= paye`). Les statuts `don_recu`/`don_echoue` sont donc du code mort : leurs
compteurs admin (« Reçu (0) », « Échoué (0) », visibles dans la barre de
filtres au-dessus de la liste **Dons**) resteront à zéro indéfiniment, quel
que soit le nombre de dons réglés.

**Portée du défaut** : n'affecte **pas** les points fonctionnels vérifiés par
cette recette — la colonne « Statut » de la liste (personnalisée, lit la méta
`_don_paiement_statut`), le filtre par statut, le total encaissé et l'export
CSV lisent tous la méta de paiement, pas le `post_status`, et se comportent
correctement (vérifié aux points 14, 18, 20). Le défaut est cosmétique mais
trompeur : la ligne de liens de statut natifs de WordPress en haut de la liste
**Dons** ne reflète jamais la réalité.

### D2 — Le QR du motif s'affiche dans la liste, pas sur l'écran d'édition

**Où** : `cms/web/app/mu-plugins/prophet-core/src/PostTypes/MotifPaiement.php`
(colonne admin `motif_lien_qr`) vs. spec §« Le QR code » : « Dans
l'administration, l'écran d'édition d'un motif affiche son QR ».

**Constat** : le QR et ses deux téléchargements (PNG, SVG) sont bien
disponibles et fonctionnels, mais uniquement dans la colonne « Lien et QR » de
la **liste** des motifs (`wp-admin` → Motifs de paiement), pas sur l'écran
d'édition d'un motif individuel. Fonctionnellement équivalent pour l'usage
décrit (récupérer le lien et le QR), mais s'écarte du libellé exact de la
spec. Non bloquant — signalé par souci d'exactitude documentaire ; le README
rédigé pour cette tâche décrit l'emplacement réel (la liste), pas celui de la
spec.

## Ce qui a été créé, restauré et supprimé

**Créé pendant la recette, tout supprimé en fin de recette :**
- Motifs `motif_paiement` : Offrandes (post 78), Alliance (post 79) — **supprimés**.
- Dons `don` : posts 80 à 85 (un par point de recette nécessitant une
  soumission valide) — **tous supprimés**.
- Fichiers QR de test : `cms/web/app/uploads/qr/motif-78.png` (et son `.svg`
  généré puis supprimé par l'invalidation) — **supprimés**. Les fichiers
  `motif-65.png`/`.svg` du motif réel Dîmes, antérieurs à cette recette, n'ont
  pas été touchés.
- `cms/.env` : ligne temporaire `MONEROO_WEBHOOK_SECRET=recette_test_webhook_secret`
  (valeur de test, sans rapport avec une clé réelle), ajoutée pour signer les
  webhooks des points 14/15 — **retirée**, fichier revenu à son état
  d'origine (`cms/.env` est de toute façon ignoré par git).

**Modifié temporairement puis restauré :**
- `motif_actif` des 3 motifs (désactivés pour le point 1, réactivés
  immédiatement après).
- `motif_actif` d'Alliance (désactivé pour le point 4, réactivé après).
- Slug d'Offrandes (`offrandes` → `offrandes-nouveau` → `offrandes`, point 17).

**État final vérifié** : `wp post list --post_type=motif_paiement` ne montre
que le motif réel Dîmes (post 65, actif, slug `dimes`) ; `wp post list
--post_type=don` est vide ; `grep MONEROO cms/.env` ne renvoie rien ; la suite
PHPUnit est verte à l'identique de l'état de départ.

## Fichiers concernés par cette tâche

- `README.md` — nouvelle section « Motifs de paiement et dons », deux lignes
  ajoutées au tableau « Administration du contenu ».
- `docs/superpowers/notes/2026-08-04-recette-dons.md` — ce document.

Aucun fichier de code (`cms/web/app/mu-plugins/prophet-core`,
`cms/web/app/themes/prophet`) n'a été modifié : conformément à la consigne,
les deux défauts constatés (D1, D2) sont documentés, pas corrigés.
