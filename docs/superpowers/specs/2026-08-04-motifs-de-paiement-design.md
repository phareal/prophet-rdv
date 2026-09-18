# Motifs de paiement — dons, liens partageables et QR codes

**Date** : 2026-08-04
**Statut** : spec à valider
**Dépend de** : le paiement Moneroo (`2026-08-03-paiement-moneroo-design.md`), terminé

## Objectif

Permettre au prophète de créer, depuis l'administration, des **motifs de paiement**
— Dîmes, Offrandes, Alliance, et ce qu'il ajoutera demain — que le visiteur choisit
pour régler en ligne. Chaque motif dispose d'un **lien permanent partageable** et
d'un **QR code**, utilisables dans un statut WhatsApp, projetés pendant un culte ou
imprimés sur un flyer.

C'est un module **autonome, détaché de la prise de rendez-vous**. Un don n'est pas
une consultation : il n'a ni créneau, ni confirmation par le prophète, ni email de
demande.

## Une question de vocabulaire, tranchée d'emblée

Le formulaire de rendez-vous a déjà un champ « mode de paiement » : Mobile Money,
virement, PayPal, crypto — le **moyen**. Ce que cette spec ajoute est le **motif** :
la raison du versement. Deux notions distinctes sous des noms voisins seraient une
confusion garantie, en administration comme dans le code.

| Terme | Sens | Où |
|---|---|---|
| **Mode** de paiement | Par quel canal on paie | champ existant du formulaire de rendez-vous |
| **Motif** de paiement | Pourquoi on paie | CPT `motif_paiement`, cette spec |

## Principe directeur

**La plomberie de paiement est mutualisée, pas dupliquée.** Le client HTTP, les
statuts, la vérification serveur et le webhook signé existent déjà et servent le
rendez-vous. Ce module s'y branche au lieu d'en faire une seconde copie.

Une duplication se paierait le jour où une correction de sécurité — sur la
vérification de signature, par exemple — ne serait appliquée qu'à un seul des deux
chemins. C'est le scénario qui coûte cher, et il est évitable dès maintenant.

## Refonte préalable : un sujet payable

Aujourd'hui la couche paiement est accrochée au rendez-vous : `PaiementRepository`
écrit dans les métadonnées d'un `rendez_vous`, `InitHandler` charge une demande par
sa référence. Il faut l'abstraire d'un cran.

```php
interface SujetPaiement
{
    public function postId(): int;
    public function reference(): string;
    public function description(): string;   // ce que Moneroo affiche au payeur
    public function montant(): ?array;       // ['montant' => int, 'devise' => string]
    public function client(): array;         // email, prénom, nom, téléphone
    public function urlRetour(): string;
    public function metaKey(string $champ): string;
}
```

Deux implémentations : `Rdv\RendezVousPayable` et `Don\DonPayable`. Un
`ResolveurDeSujet` retrouve le bon à partir d'une référence, en interrogeant chaque
type enregistré — les points d'entrée existants (initialisation, retour, webhook)
travaillent alors sur l'interface et cessent de connaître le rendez-vous.

Cette refonte ne change aucun comportement observable. Les tests existants du
paiement doivent rester verts sans modification de leurs assertions — c'est le
critère qui prouve que la refonte n'a rien cassé.

## Modèle de contenu

### CPT `motif_paiement`

Public, avec réécriture d'URL sur `/don/` — le lien partageable est l'URL du motif
lui-même.

| Champ | Type | Rôle |
|---|---|---|
| titre | post title | « Dîmes », « Offrandes », « Alliance » |
| description | post content | texte affiché au visiteur |
| `motif_icone` | text | nom lucide, comme le CPT `service` |
| `motif_couleur` | color | pastille de la carte |
| `motif_regime` | select | `libre`, `fixe`, `suggere` |
| `motif_montant_fixe` | text numérique | si régime `fixe` |
| `motif_montants_suggeres` | complex | si régime `suggere` — une valeur par ligne |
| `motif_montant_min` | text numérique | garde-fou, défaut 500 |
| `motif_montant_max` | text numérique | garde-fou, défaut 5 000 000 |
| `motif_actif` | checkbox | retire le motif sans le supprimer |
| ordre | menu_order | ordre d'affichage |

**Trois régimes de montant, parce qu'un seul ne suffit pas.** Une dîme est un
pourcentage que seul le donateur connaît ; une offrande est libre ; une Alliance
peut porter un montant convenu. Le régime `suggere` propose des paliers tout en
laissant saisir autre chose — c'est ce qui marche le mieux en pratique.

### CPT privé `don`

Même esprit que `rendez_vous` : la liste d'administration, la recherche et les
filtres viennent gratuitement.

| Métadonnée | Contenu |
|---|---|
| `don_motif` | identifiant du motif |
| `don_nom`, `don_prenom`, `don_email`, `don_telephone` | donateur |
| `don_message` | mot facultatif |
| `don_ref` | jeton aléatoire de 32 caractères |
| `paiement_*` | les mêmes clés que le rendez-vous, via `SujetPaiement::metaKey()` |

Statuts : `don_en_attente`, `don_recu`, `don_echoue`. Un don n'existe que par son
paiement — contrairement au rendez-vous, il n'a pas de vie propre avant.

## Parcours

### La page de don

Une page unique `/don/` porte le formulaire : un **sélecteur des motifs actifs**, le
montant selon le régime du motif choisi, et le minimum d'informations sur le
donateur — prénom, nom, email, téléphone.

Le sélecteur change le bloc montant sans rechargement, en Alpine : les trois régimes
sont rendus côté serveur et affichés selon le motif retenu.

### Les liens profonds

`/don/dimes/`, `/don/offrandes/`, `/don/alliance/` — chaque motif a son URL propre,
qui ouvre la même page avec **le motif présélectionné**. C'est cette URL qui se
partage et que le QR code encode.

Un lien profond doit rester utilisable si le motif est désactivé plus tard : la page
affiche alors un message clair et la liste des motifs disponibles, jamais une erreur.

### Le QR code

Généré **côté serveur** avec `endroid/qr-code`, en PNG et en SVG, et mis en cache
dans `uploads/qr/`. Un QR produit en JavaScript ne s'imprime pas et ne se partage
pas — or c'est précisément l'usage.

Dans l'administration, l'écran d'édition d'un motif affiche son QR et propose le
téléchargement dans les deux formats. Le fichier est régénéré si l'URL du motif
change, ce qui arrive quand on modifie son identifiant.

### Le paiement

Identique au rendez-vous, par la couche mutualisée : enregistrement du `don` d'abord,
puis initialisation Moneroo, redirection, retour vérifié côté serveur, webhook signé
en filet.

**Le don est enregistré avant toute redirection**, comme le rendez-vous. Un paiement
abandonné laisse une trace exploitable — utile pour comprendre qu'un motif ne
convertit pas.

## Sécurité

Le montant est ici **saisi par le visiteur**, ce qui change la donne par rapport au
rendez-vous où il venait du service. D'où :

- **Bornes serveur obligatoires** : le montant est validé contre le minimum et le
  maximum du motif, côté serveur, après réception. Les attributs HTML `min` et `max`
  ne sont qu'un confort d'affichage.
- **Entier positif** : ni décimale, ni notation scientifique, ni valeur négative.
- En régime `fixe`, le montant soumis est **ignoré** et remplacé par celui du motif.
  Un champ caché serait modifiable.
- En régime `suggere`, une valeur hors des paliers reste acceptée si elle respecte
  les bornes — c'est le principe même du régime.

**La limitation de débit doit changer de forme.** Celle du rendez-vous est d'une
soumission par IP toutes les 60 secondes. Appliquée telle quelle ici, elle bloquerait
une assemblée entière : deux cents personnes qui scannent le même QR pendant un culte
sortent toutes par la même adresse IP publique. La limite portera donc sur le couple
(IP, email) et sera nettement plus permissive — l'abus visé est le robot, pas le
fidèle.

Le reste est inchangé : pas de donnée personnelle dans les journaux, référence
aléatoire non énumérable, signature de webhook vérifiée avec `hash_equals`.

## Administration

- Menu « Motifs de paiement » : création, ordre, activation, QR.
- Menu « Dons » : liste avec motif, montant, statut, donateur ; filtre par motif et
  par statut ; export CSV — un trésorier en aura besoin, et il ne passera pas par la
  base de données.
- Total encaissé par motif, affiché en tête de liste sur la période visible.

## Tests

- Résolution du montant par régime : `libre`, `fixe` (valeur soumise ignorée),
  `suggere` (hors palier accepté dans les bornes).
- Refus d'un montant sous le minimum, au-dessus du maximum, négatif, non entier.
- Un motif inactif refuse un nouveau don mais son lien reste consultable.
- Génération du QR : fichier produit, mis en cache, régénéré si l'URL change.
- La refonte `SujetPaiement` : les tests existants du paiement passent sans
  modification de leurs assertions.
- Idempotence du webhook sur un `don` comme sur un `rendez_vous`.

## Hors périmètre

- Dons récurrents et prélèvements automatiques.
- Reçus fiscaux et documents comptables.
- Objectifs de collecte et jauges de progression.
- Comptes donateurs et historique personnel.

## Risques

| Risque | Traitement |
|---|---|
| La refonte casse le paiement du rendez-vous | Les tests existants doivent passer sans modification de leurs assertions |
| Limitation de débit trop stricte en contexte d'assemblée | Limite sur (IP, email), permissive ; l'abus visé est le robot |
| QR périmé après changement d'identifiant | Régénération déclenchée par le changement d'URL |
| Montant libre exploité pour des paiements dérisoires ou absurdes | Bornes minimum et maximum par motif, validées côté serveur |
| Confusion mode / motif | Vocabulaire tranché en tête de spec, appliqué au code comme à l'administration |
