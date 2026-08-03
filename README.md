# Prophète Jeremiah Nahoum — Application de Prise de Rendez-vous

Application web complète de prise de rendez-vous pour le **Prophète Jeremiah Nahoum**, "Le Conseiller des Rois".

## Stack Technique

| Couche | Technologie |
|--------|-------------|
| CMS | WordPress sur Bedrock 1.28 (PHP 8.2) |
| Thème | Sage 11 (Blade, Vite) — `cms/web/app/themes/prophet` |
| Champs personnalisés | Carbon Fields |
| Base de données | MySQL 8 |
| Email | SMTP (wp_mail) |
| WhatsApp | Lien wa.me généré côté serveur |
| Conteneur | Docker multi-stage (PHP-FPM) + docker-compose |
| Reverse proxy | Caddy 2 (HTTPS automatique) |

---

## Démarrage rapide

### Prérequis
- Docker + Docker Compose
- Node.js 20+ et npm 10+ (pour builder le thème)

### Développement local

```bash
# 1. Copier et configurer les variables d'environnement
cp cms/.env.example cms/.env
# Éditer cms/.env avec vos valeurs

# 2. Démarrer la pile de développement (Caddy, PHP-FPM, MySQL, WP-CLI)
docker compose -f docker-compose.cms.dev.yml up -d

# 3. Installer WordPress (une seule fois)
./cms/scripts/install-wp.sh

# 4. Charger le contenu réel du site
docker compose -f docker-compose.cms.dev.yml exec -T wpcli wp prophet seed

# 5. Lancer le serveur de développement du thème (Vite, hot-reload)
cd cms/web/app/themes/prophet && npm install && npm run dev
```

Le site sera disponible sur `http://localhost:8080`, l'administration sur `http://localhost:8080/wp/wp-admin`.

### Production

```bash
# 1. Copier et configurer les variables d'environnement de production
cp cms/.env.example cms/.env
# Éditer cms/.env : WP_ENV=production, WP_HOME=https://votre-domaine.com,
# secrets réels, DOMAIN et ACME_EMAIL pour Caddy

# 2. Construire les images (thème compilé en étage séparé) et démarrer
docker compose -f docker-compose.cms.yml up -d --build
```

Caddy obtient et renouvelle automatiquement les certificats HTTPS via Let's Encrypt.

---

## Configuration (.env)

| Variable | Description | Exemple |
|----------|-------------|---------|
| `MONGODB_URI` | URI de connexion MongoDB | `mongodb://localhost:27017/prophetrdv` |
| `SMTP_HOST` | Serveur SMTP | `smtp.gmail.com` |
| `SMTP_PORT` | Port SMTP | `587` |
| `SMTP_USER` | Email expéditeur | `votre@gmail.com` |
| `SMTP_PASS` | Mot de passe d'application | Voir note Gmail |
| `PROPHET_EMAIL` | Email de notification | `prophet@exemple.com` |
| `PROPHET_PHONE_1` | Numéro WhatsApp principal | `+22897169090` |
| `PROPHET_PHONE_2` | Numéro WhatsApp secondaire | `+2348119265483` |
| `NUXT_PUBLIC_SITE_URL` | URL publique du site | `https://votre-domaine.com` |

**Note Gmail :** Activez l'authentification 2 facteurs sur votre compte Google, puis créez un "App Password" sur [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords).

---

## Ajouter les photos

Placez vos photos dans `/public/images/` :

| Fichier | Utilisation | Dimensions |
|---------|-------------|------------|
| `prophet-main.jpg` | Colonne gauche (hero) | 400 × 500 px |
| `gallery-1.jpg` | Galerie photo 1 | 800 × 600 px |
| `gallery-2.jpg` | Galerie photo 2 | 800 × 600 px |
| `gallery-3.jpg` | Galerie photo 3 | 800 × 600 px |
| `gallery-4.jpg` | Galerie photo 4 | 800 × 600 px |
| `gallery-5.jpg` | Galerie photo 5 | 800 × 600 px |
| `gallery-6.jpg` | Galerie photo 6 | 800 × 600 px |

Après ajout, remplacez les placeholders dans :
- `components/HeroLeft.vue` (ligne ~55) : décommentez la balise `<img>`
- `components/GallerySection.vue` : remplacez les `<div>` placeholder par des `<img>`

---

## Production avec Docker

```bash
# Construire et démarrer
make build && make up

# Voir les logs
make logs

# Redémarrer l'app après modif .env
make restart

# Accès MongoDB
make mongo
```

---

## Déploiement sur VPS (Ubuntu/Debian)

```bash
# 1. Modifier l'URL du repo dans scripts/deploy.sh
#    REPO_URL="https://github.com/TON_USER/prophet-rdv.git"

# 2. Lancer le déploiement (depuis votre machine locale ou le VPS)
chmod +x scripts/deploy.sh
./scripts/deploy.sh main

# Le script :
# ✓ Vérifie les prérequis (Docker, Git)
# ✓ Clone ou met à jour le repo
# ✓ Vérifie la présence du .env (le crée depuis .env.example si absent)
# ✓ Build l'image Docker sans cache
# ✓ Remplace les anciens conteneurs
# ✓ Vérifie la santé de l'application
# ✓ Nettoie les images inutilisées
# ✓ Loggue le déploiement dans /var/log/prophet-rdv-deploy.log
```

---

## Activer HTTPS (SSL)

```bash
# 1. Installer Certbot sur le VPS
apt-get install certbot

# 2. Obtenir les certificats (arrêtez Nginx temporairement)
docker compose stop nginx
certbot certonly --standalone -d votre-domaine.com

# 3. Copier les certificats
cp /etc/letsencrypt/live/votre-domaine.com/fullchain.pem /opt/prophet-rdv/nginx/ssl/
cp /etc/letsencrypt/live/votre-domaine.com/privkey.pem /opt/prophet-rdv/nginx/ssl/

# 4. Décommenter le bloc HTTPS dans nginx/default.conf
#    et activer la redirection HTTP→HTTPS

# 5. Redémarrer Nginx
docker compose restart nginx
```

---

## Structure des fichiers

```
prophet-rdv/
├── pages/
│   ├── index.vue              # Page principale (3 sections)
│   └── confirmation.vue       # Page de confirmation post-RDV
├── components/
│   ├── HeroLeft.vue           # Colonne gauche (branding + photo)
│   ├── RdvForm.vue            # Formulaire de rendez-vous
│   ├── ConsultationPicker.vue # Sélecteur de 12 types de consultation
│   ├── GallerySection.vue     # Section galerie photos
│   ├── TestimonialsSection.vue# Section témoignages
│   ├── TestimonialCard.vue    # Carte témoignage individuelle
│   ├── AppFooter.vue          # Pied de page
│   └── ui/                    # Composants shadcn-vue
├── server/
│   ├── api/rdv.post.ts        # Route POST /api/rdv
│   ├── models/Appointment.ts  # Modèle Mongoose
│   └── utils/
│       ├── mailer.ts          # Envoi d'emails (Nodemailer)
│       └── whatsapp.ts        # Construction URLs WhatsApp
├── lib/
│   ├── validations.ts         # Schéma Zod + constantes
│   └── utils.ts               # Utilitaires (cn, formatDate, wa)
├── public/images/             # Photos (à ajouter)
├── nginx/default.conf         # Configuration Nginx
├── scripts/deploy.sh          # Script de déploiement VPS
├── Dockerfile                 # Build multi-stage
├── docker-compose.yml         # Orchestration services
├── Makefile                   # Raccourcis de commandes
└── .env.example               # Template variables d'environnement
```

---

## Personnalisation

### Changer les couleurs
Dans `tailwind.config.ts` → `theme.extend.colors.prophet` :
```ts
prophet: {
  green: '#1D9E75',   // Couleur principale
  dark:  '#0F172A',   // Fond colonne gauche
  gold:  '#BA7517',   // Accents dorés
  light: '#E1F5EE',   // Fond clair vert
}
```

### Changer les numéros WhatsApp
Dans `.env` :
```
PROPHET_PHONE_1=+33600000000
PROPHET_PHONE_2=+33611111111
```

### Ajouter un type de consultation
Dans `lib/validations.ts` → `CONSULTATION_TYPES`
Dans `components/ConsultationPicker.vue` → tableau `consultations`

### Modifier les horaires disponibles
Dans `lib/validations.ts` → `HEURES`

---

## Commandes utiles

```bash
make help           # Afficher toutes les commandes
make dev            # Mode développement
make build          # Build Docker
make up             # Démarrer en prod
make down           # Arrêter
make logs           # Logs en direct
make restart        # Redémarrer l'app
make deploy         # Déployer sur VPS
make shell          # Shell dans le conteneur
make mongo          # MongoDB shell
make clean          # Tout supprimer
```
