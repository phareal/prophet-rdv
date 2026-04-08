.PHONY: dev build up down logs restart deploy shell mongo clean help

# ─── Développement local ──────────────────────────────────────
dev:
	npm run dev

install:
	npm install && cp -n .env.example .env || true

# ─── Docker ──────────────────────────────────────────────────
build:
	docker compose build --no-cache

up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f

restart:
	docker compose restart app

# ─── Déploiement VPS ─────────────────────────────────────────
deploy:
	chmod +x scripts/deploy.sh && ./scripts/deploy.sh

deploy-branch:
	chmod +x scripts/deploy.sh && ./scripts/deploy.sh $(BRANCH)

# ─── Utilitaires ─────────────────────────────────────────────
shell:
	docker compose exec app sh

mongo:
	docker compose exec mongo mongosh prophetrdv

clean:
	docker compose down --volumes --remove-orphans
	docker image prune -f

status:
	docker compose ps

# ─── Aide ─────────────────────────────────────────────────────
help:
	@echo ""
	@echo "  Prophet RDV — Commandes disponibles"
	@echo "  ────────────────────────────────────"
	@echo "  make install    → Installer les dépendances + copier .env"
	@echo "  make dev        → Démarrer en mode développement"
	@echo "  make build      → Construire l'image Docker"
	@echo "  make up         → Démarrer les conteneurs en arrière-plan"
	@echo "  make down       → Arrêter les conteneurs"
	@echo "  make logs       → Suivre les logs en temps réel"
	@echo "  make restart    → Redémarrer le conteneur app"
	@echo "  make deploy     → Déployer sur VPS (branche main)"
	@echo "  make shell      → Ouvrir un shell dans le conteneur app"
	@echo "  make mongo      → Ouvrir mongosh dans le conteneur MongoDB"
	@echo "  make clean      → Tout supprimer (conteneurs + volumes)"
	@echo "  make status     → Voir le statut des conteneurs"
	@echo ""
