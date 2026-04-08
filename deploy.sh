#!/bin/bash
set -e

PROJECT_NAME="prophete-jeremiah-nahoum"

echo "==> Vérification de Vercel CLI..."
if ! command -v vercel &> /dev/null; then
  echo "Vercel CLI non trouvé. Installation..."
  npm install -g vercel
fi

echo "==> Build du projet Nuxt..."
npm run build

echo "==> Déploiement sur Vercel (projet: $PROJECT_NAME)..."
vercel deploy --prod \
  --name "$PROJECT_NAME" \
  --yes

echo "==> Déploiement terminé !"
