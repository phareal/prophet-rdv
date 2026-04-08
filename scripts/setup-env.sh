#!/usr/bin/env bash
# =============================================
# setup-env.sh — Configuration interactive du .env
# Usage : chmod +x scripts/setup-env.sh
#         ./scripts/setup-env.sh
# =============================================

set -euo pipefail

# Toujours s'exécuter depuis la racine du projet
cd "$(dirname "$0")/.."

ENV_FILE=".env"
BACKUP_FILE=".env.backup.$(date +%Y%m%d_%H%M%S)"

# ── Couleurs ──────────────────────────────────
BOLD='\033[1m'
DIM='\033[2m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m'

# ── Helpers affichage ─────────────────────────
section() { echo ""; echo -e "${BLUE}${BOLD}── $1 ──────────────────────────────────────${NC}"; }
hint()    { echo -e "  ${DIM}↳ $1${NC}"; }
ok()      { echo -e "${GREEN}✓${NC} $1"; }
warn()    { echo -e "${YELLOW}⚠${NC}  $1"; }
info()    { echo -e "${CYAN}ℹ${NC}  $1"; }

# ── Ouvrir une URL (macOS / Linux / WSL) ──────
open_url() {
  local url="$1"
  local label="${2:-$url}"

  echo ""
  echo -e "  ${MAGENTA}${BOLD}🔗 $label${NC}"

  if command -v open &>/dev/null; then
    open "$url"
  elif command -v xdg-open &>/dev/null; then
    xdg-open "$url"
  elif command -v wslview &>/dev/null; then
    wslview "$url"
  else
    echo -e "  ${YELLOW}Impossible d'ouvrir automatiquement. Copiez l'URL ci-dessus.${NC}"
  fi

  sleep 1
  echo -e -n "  ${DIM}Appuyez sur Entrée une fois la page chargée...${NC}"
  read -r
}

# ── Lire la valeur actuelle depuis le .env ────
current() {
  local key="$1"
  if [ -f "$ENV_FILE" ]; then
    grep -E "^${key}=" "$ENV_FILE" 2>/dev/null | cut -d'=' -f2- | tr -d '\r\n' || true
  fi
}

# ── Prompt standard ───────────────────────────
ask() {
  local key="$1"
  local label="$2"
  local default="${3:-$(current "$key")}"
  local hint_text="${4:-}"

  echo ""
  if [ -n "$hint_text" ]; then hint "$hint_text"; fi

  if [ -n "$default" ]; then
    echo -e -n "  ${BOLD}${label}${NC} ${DIM}[${default}]${NC} : "
  else
    echo -e -n "  ${BOLD}${label}${NC} : "
  fi

  read -r input
  echo "${input:-$default}"
}

# ── Prompt masqué pour les secrets ───────────
ask_secret() {
  local key="$1"
  local label="$2"
  local hint_text="${3:-}"

  echo ""
  if [ -n "$hint_text" ]; then hint "$hint_text"; fi

  local has_current=""
  if [ -n "$(current "$key")" ]; then
    has_current=" ${DIM}[existant — Entrée pour conserver]${NC}"
  fi

  echo -e -n "  ${BOLD}${label}${NC}${has_current} : "
  read -rs input
  echo ""

  if [ -z "$input" ] && [ -n "$(current "$key")" ]; then
    current "$key"
  else
    echo "$input"
  fi
}

# ════════════════════════════════════════════════
# BANNIÈRE
# ════════════════════════════════════════════════
clear
echo ""
echo -e "${BLUE}${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  ⚙  Configuration Prophet RDV — .env${NC}"
echo -e "${BLUE}${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
info "Ce script génère le fichier ${BOLD}.env${NC} à la racine du projet."
info "Les pages requises s'ouvrent dans votre navigateur au bon moment."
info "Appuyez sur Entrée pour conserver la valeur entre crochets."

if [ -f "$ENV_FILE" ]; then
  cp "$ENV_FILE" "$BACKUP_FILE"
  ok "Sauvegarde : ${BOLD}${BACKUP_FILE}${NC}"
fi

# ════════════════════════════════════════════════
# SECTION 1 — APPLICATION
# ════════════════════════════════════════════════
section "Application"

NODE_ENV=$(ask "NODE_ENV" "Environnement" "production" "production | development")
NUXT_PUBLIC_SITE_URL=$(ask "NUXT_PUBLIC_SITE_URL" "URL du site" "" \
  "Ex : https://prophetjeremiahnahoum.com")

# ════════════════════════════════════════════════
# SECTION 2 — MONGODB
# ════════════════════════════════════════════════
section "MongoDB"

info "Docker : mongodb://mongo:27017/prophetrdv"
info "Local  : mongodb://localhost:27017/prophetrdv"

MONGODB_URI=$(ask "MONGODB_URI" "URI MongoDB" "mongodb://mongo:27017/prophetrdv")

# ════════════════════════════════════════════════
# SECTION 3 — SMTP
# ════════════════════════════════════════════════
section "SMTP — Envoi d'emails"

SMTP_HOST=$(ask "SMTP_HOST"   "Serveur SMTP"   "smtp.gmail.com")
SMTP_PORT=$(ask "SMTP_PORT"   "Port SMTP"      "587" "587 (STARTTLS) | 465 (SSL)")
SMTP_SECURE=$(ask "SMTP_SECURE" "TLS strict"   "false" "true pour port 465, false pour 587")
SMTP_USER=$(ask "SMTP_USER"   "Email SMTP"     "" "Votre adresse Gmail")

echo ""
info "Ouverture de Gmail App Passwords pour générer votre mot de passe..."
open_url "https://myaccount.google.com/apppasswords" \
  "myaccount.google.com → Sécurité → App Passwords"

SMTP_PASS=$(ask_secret "SMTP_PASS" "App Password Gmail" \
  "Copiez le mot de passe à 16 caractères généré par Google")

PROPHET_EMAIL=$(ask "PROPHET_EMAIL" "Email destinataire (RDV)" "" \
  "Reçoit les notifications de réservation")

# ════════════════════════════════════════════════
# SECTION 4 — CONTACTS
# ════════════════════════════════════════════════
section "Contacts WhatsApp / Téléphone"

PROPHET_PHONE_1=$(ask "PROPHET_PHONE_1" "Téléphone 1" "+22897169090" \
  "Format international : +XXXXXXXXXXX")
PROPHET_PHONE_2=$(ask "PROPHET_PHONE_2" "Téléphone 2" "+2348119265483")

# ════════════════════════════════════════════════
# SECTION 5 — YOUTUBE
# ════════════════════════════════════════════════
section "YouTube Data API v3"

echo ""
info "Étape 1 — Activer YouTube Data API v3..."
open_url \
  "https://console.cloud.google.com/apis/library/youtube.googleapis.com" \
  "console.cloud.google.com → Bibliothèque → YouTube Data API v3 → Activer"

echo ""
info "Étape 2 — Créer une clé API..."
open_url \
  "https://console.cloud.google.com/apis/credentials" \
  "console.cloud.google.com → Identifiants → + Créer des identifiants → Clé API"

YOUTUBE_API_KEY=$(ask "YOUTUBE_API_KEY" "Clé API YouTube" "" "Commence par AIza...")

echo ""
info "Ouverture de la chaîne YouTube pour récupérer le Channel ID..."
open_url \
  "https://www.youtube.com/@ProphetJeremiahNahoum/about" \
  "youtube.com/@ProphetJeremiahNahoum — clic droit → Afficher le code source → chercher channelId"

YOUTUBE_CHANNEL_ID=$(ask "YOUTUBE_CHANNEL_ID" "Channel ID YouTube" "" \
  "Ex : UCxxxxxxxxxxxxxxxxxxxxxxx")

# ════════════════════════════════════════════════
# SECTION 6 — GOOGLE CALENDAR
# ════════════════════════════════════════════════
section "Google Calendar API"

echo ""
info "Étape 1 — Activer Google Calendar API (même projet GCP)..."
open_url \
  "https://console.cloud.google.com/apis/library/calendar-json.googleapis.com" \
  "console.cloud.google.com → Bibliothèque → Google Calendar API → Activer"

GOOGLE_API_KEY=$(ask "GOOGLE_API_KEY" "Clé API Google Calendar" "" \
  "Peut être la même clé que YouTube si même projet GCP")

echo ""
info "Étape 2 — Récupérer l'ID de votre agenda..."
open_url \
  "https://calendar.google.com/calendar/r/settings" \
  "calendar.google.com → Paramètres → [votre agenda] → Intégrer l'agenda → ID de l'agenda"

GOOGLE_CALENDAR_ID=$(ask "GOOGLE_CALENDAR_ID" "Calendar ID" "" \
  "Ex : votre@email.com ou xxxxx@group.calendar.google.com")

# ════════════════════════════════════════════════
# ÉCRITURE DU .env
# ════════════════════════════════════════════════
cat > "$ENV_FILE" <<EOF
# ─── Application ───────────────────────────────────────────
NODE_ENV=${NODE_ENV}
NUXT_PUBLIC_SITE_URL=${NUXT_PUBLIC_SITE_URL}

# ─── MongoDB (géré par docker-compose, ne pas modifier) ────
MONGODB_URI=${MONGODB_URI}

# ─── SMTP Email ─────────────────────────────────────────────
SMTP_HOST=${SMTP_HOST}
SMTP_PORT=${SMTP_PORT}
SMTP_SECURE=${SMTP_SECURE}
SMTP_USER=${SMTP_USER}
SMTP_PASS=${SMTP_PASS}

# ─── YouTube Data API v3 ────────────────────────────────────
YOUTUBE_API_KEY=${YOUTUBE_API_KEY}
YOUTUBE_CHANNEL_ID=${YOUTUBE_CHANNEL_ID}

# ─── Google Calendar API ────────────────────────────────────
GOOGLE_API_KEY=${GOOGLE_API_KEY}
GOOGLE_CALENDAR_ID=${GOOGLE_CALENDAR_ID}

# ─── Destinataires ──────────────────────────────────────────
PROPHET_EMAIL=${PROPHET_EMAIL}
PROPHET_PHONE_1=${PROPHET_PHONE_1}
PROPHET_PHONE_2=${PROPHET_PHONE_2}
EOF

# ════════════════════════════════════════════════
# RÉSUMÉ
# ════════════════════════════════════════════════
echo ""
echo -e "${GREEN}${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}${BOLD}✅ Fichier .env généré !${NC}"
echo -e "${GREEN}${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "  ${BOLD}APPLICATION${NC}"
echo -e "    NODE_ENV             = ${NODE_ENV}"
echo -e "    NUXT_PUBLIC_SITE_URL = ${NUXT_PUBLIC_SITE_URL}"
echo ""
echo -e "  ${BOLD}SMTP${NC}"
echo -e "    SMTP_USER            = ${SMTP_USER}"
echo -e "    SMTP_PASS            = ${DIM}••••••••${NC}"
echo -e "    PROPHET_EMAIL        = ${PROPHET_EMAIL}"
echo ""
echo -e "  ${BOLD}YOUTUBE${NC}"
echo -e "    YOUTUBE_API_KEY      = ${YOUTUBE_API_KEY:+${DIM}••••${YOUTUBE_API_KEY: -4}${NC}}${YOUTUBE_API_KEY:-${YELLOW}(vide — mode mock actif)${NC}}"
echo -e "    YOUTUBE_CHANNEL_ID   = ${YOUTUBE_CHANNEL_ID:-${YELLOW}(vide — mode mock actif)${NC}}"
echo ""
echo -e "  ${BOLD}CALENDAR${NC}"
echo -e "    GOOGLE_API_KEY       = ${GOOGLE_API_KEY:+${DIM}••••${GOOGLE_API_KEY: -4}${NC}}${GOOGLE_API_KEY:-${YELLOW}(vide — mode mock actif)${NC}}"
echo -e "    GOOGLE_CALENDAR_ID   = ${GOOGLE_CALENDAR_ID:-${YELLOW}(vide — mode mock actif)${NC}}"
echo ""

missing=0
[ -z "$YOUTUBE_API_KEY" ]    && warn "YOUTUBE_API_KEY vide → VideoSection en mode mock"    && missing=1
[ -z "$YOUTUBE_CHANNEL_ID" ] && warn "YOUTUBE_CHANNEL_ID vide → VideoSection en mode mock" && missing=1
[ -z "$GOOGLE_API_KEY" ]     && warn "GOOGLE_API_KEY vide → EventsSection en mode mock"    && missing=1
[ -z "$GOOGLE_CALENDAR_ID" ] && warn "GOOGLE_CALENDAR_ID vide → EventsSection en mode mock" && missing=1
[ "$missing" -eq 0 ]         && ok  "Toutes les clés API sont renseignées"

echo ""
info "Prochaine étape : docker compose up -d"
info "Pour relancer ce script : ./scripts/setup-env.sh"
echo ""
