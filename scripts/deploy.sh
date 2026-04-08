#!/bin/bash
set -e

# =============================================
# deploy.sh — Déploiement Prophet RDV sur VPS
# Usage : chmod +x scripts/deploy.sh
#         ./scripts/deploy.sh [branche]
# Compatible : Ubuntu 22.04 / Debian 12
# =============================================

BRANCH=${1:-main}
APP_DIR="/opt/prophet-rdv"
REPO_URL="https://github.com/TON_USER/prophet-rdv.git"   # ← Modifier avant déploiement
LOG_FILE="/var/log/prophet-rdv-deploy.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Couleurs terminal
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log()   { echo -e "${BLUE}▶${NC} $1"; }
ok()    { echo -e "${GREEN}✅${NC} $1"; }
warn()  { echo -e "${YELLOW}⚠️ ${NC} $1"; }
error() { echo -e "${RED}❌${NC} $1"; }

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}🚀 Déploiement Prophet RDV${NC} — ${TIMESTAMP}"
echo -e "📌 Branche : ${YELLOW}${BRANCH}${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# ─────────────────────────────────────────────
# 1. VÉRIFICATIONS PRÉREQUIS
# ─────────────────────────────────────────────
log "Vérification des prérequis..."

command -v docker >/dev/null 2>&1 || { error "Docker non installé. Installez-le : https://docs.docker.com/engine/install/"; exit 1; }
command -v git >/dev/null 2>&1    || { error "Git non installé. Lancez : apt-get install git"; exit 1; }

# Vérifier docker compose (plugin v2 ou standalone v1)
if docker compose version >/dev/null 2>&1; then
    COMPOSE_CMD="docker compose"
elif docker-compose version >/dev/null 2>&1; then
    COMPOSE_CMD="docker-compose"
else
    error "Docker Compose non installé."
    exit 1
fi

ok "Docker et Git disponibles (Compose: ${COMPOSE_CMD})"

# ─────────────────────────────────────────────
# 2. CLONER OU METTRE À JOUR LE REPO
# ─────────────────────────────────────────────
if [ -d "${APP_DIR}/.git" ]; then
    log "Mise à jour du code source (branche: ${BRANCH})..."
    cd "${APP_DIR}"
    git fetch origin
    git checkout "${BRANCH}"
    git pull origin "${BRANCH}"
    ok "Code source mis à jour"
else
    log "Clonage du repo dans ${APP_DIR}..."
    mkdir -p "$(dirname ${APP_DIR})"
    git clone -b "${BRANCH}" "${REPO_URL}" "${APP_DIR}"
    cd "${APP_DIR}"
    ok "Repo cloné"
fi

# ─────────────────────────────────────────────
# 3. VÉRIFIER LE FICHIER .env
# ─────────────────────────────────────────────
if [ ! -f "${APP_DIR}/.env" ]; then
    warn "Fichier .env manquant. Copie du template..."
    cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
    echo ""
    error "IMPORTANT : Éditez ${APP_DIR}/.env avec vos valeurs réelles"
    error "puis relancez ce script : ./scripts/deploy.sh ${BRANCH}"
    echo ""
    exit 1
fi
ok "Fichier .env présent"

# ─────────────────────────────────────────────
# 4. CRÉER LE DOSSIER SSL SI ABSENT
# ─────────────────────────────────────────────
mkdir -p "${APP_DIR}/nginx/ssl"

# ─────────────────────────────────────────────
# 5. BUILD DE L'IMAGE DOCKER
# ─────────────────────────────────────────────
log "Build de l'image Docker (sans cache)..."
${COMPOSE_CMD} -f "${APP_DIR}/docker-compose.yml" build --no-cache
ok "Image construite"

# ─────────────────────────────────────────────
# 6. ARRÊT DE L'ANCIENNE VERSION
# ─────────────────────────────────────────────
log "Arrêt des conteneurs existants..."
${COMPOSE_CMD} -f "${APP_DIR}/docker-compose.yml" down --remove-orphans 2>/dev/null || true
ok "Conteneurs arrêtés"

# ─────────────────────────────────────────────
# 7. DÉMARRAGE DES NOUVEAUX CONTENEURS
# ─────────────────────────────────────────────
log "Démarrage des conteneurs en mode détaché..."
${COMPOSE_CMD} -f "${APP_DIR}/docker-compose.yml" up -d
ok "Conteneurs démarrés"

# ─────────────────────────────────────────────
# 8. VÉRIFICATION DE SANTÉ (attente 30s)
# ─────────────────────────────────────────────
log "Vérification de santé (attente 30 secondes)..."
sleep 30

if ${COMPOSE_CMD} -f "${APP_DIR}/docker-compose.yml" ps | grep -q "Up\|running"; then
    ok "Application démarrée avec succès !"
else
    error "Erreur au démarrage. Voici les derniers logs :"
    echo "─────────────────────────────────────────────"
    ${COMPOSE_CMD} -f "${APP_DIR}/docker-compose.yml" logs --tail=50
    echo "─────────────────────────────────────────────"
    exit 1
fi

# ─────────────────────────────────────────────
# 9. NETTOYAGE DES IMAGES INUTILISÉES
# ─────────────────────────────────────────────
log "Nettoyage des images Docker inutilisées..."
docker image prune -f >/dev/null 2>&1
ok "Nettoyage effectué"

# ─────────────────────────────────────────────
# 10. LOG DU DÉPLOIEMENT
# ─────────────────────────────────────────────
mkdir -p "$(dirname ${LOG_FILE})"
echo "${TIMESTAMP} | Branche=${BRANCH} | Statut=OK" >> "${LOG_FILE}"

# ─────────────────────────────────────────────
# RÉSUMÉ FINAL
# ─────────────────────────────────────────────
SERVER_IP=$(hostname -I | awk '{print $1}')
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Déploiement terminé avec succès !${NC}"
echo -e "🌐 Application : ${BLUE}http://${SERVER_IP}${NC}"
echo -e "📋 Logs       : ${YELLOW}${COMPOSE_CMD} -f ${APP_DIR}/docker-compose.yml logs -f${NC}"
echo -e "🗄️  MongoDB    : ${YELLOW}${COMPOSE_CMD} -f ${APP_DIR}/docker-compose.yml exec mongo mongosh prophetrdv${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}💡 Pour activer HTTPS :${NC}"
echo "  1. Obtenez vos certificats : certbot certonly --standalone -d votre-domaine.com"
echo "  2. Copiez-les dans ${APP_DIR}/nginx/ssl/"
echo "  3. Décommentez le bloc HTTPS dans nginx/default.conf"
echo "  4. Relancez : ${COMPOSE_CMD} restart nginx"
echo ""
