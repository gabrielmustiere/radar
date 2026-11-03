#!/bin/bash

set -euo pipefail

# --reset : efface la base de production, rejoue les migrations et recrée le compte de RADAR_USER_EMAIL.
RESET=0
case "${1:-}" in
  "") ;;
  --reset) RESET=1 ;;
  *) echo "Usage : $0 [--reset]"; exit 1 ;;
esac

ENV_DIR="environments/prod"
ENV_SCRIPT_FILE="$ENV_DIR/.env.script"

for file in "$ENV_SCRIPT_FILE" "$ENV_DIR/.env"; do
  if [ ! -f "$file" ]; then
    echo "❌ - $file introuvable : le créer depuis $file.example."
    exit 1
  fi
done

set -o allexport
source "$ENV_SCRIPT_FILE"
set +o allexport

if [ "$RESET" = 1 ] && { [ -z "${RADAR_USER_EMAIL:-}" ] || [ -z "${RADAR_USER_PASSWORD:-}" ]; }; then
  echo "❌ - --reset : RADAR_USER_EMAIL et RADAR_USER_PASSWORD doivent être renseignés dans $ENV_SCRIPT_FILE."
  exit 1
fi

SITE_DIR="/var/www/$SITE_NAME"
CADDY_CONF_FILE="/etc/caddy/conf/$SITE_NAME.caddy"
MAIN_CADDYFILE="/etc/caddy/Caddyfile"
IMPORT_LINE="import conf/$SITE_NAME.caddy"
COMPOSE="sudo docker compose -f $ENV_DIR/compose.yaml"

remote() {
  ssh "$REMOTE_USER@$REMOTE_HOST" "set -euo pipefail; $1"
}

step() {
  echo ""
  echo "----------------------------------------"
  echo "🔍 - $1"
}

echo ""
echo "🚀 - Déploiement de $SITE_NAME sur $REMOTE_HOST (branche $GIT_BRANCH, port $PROJECT_PORT)"
if [ "$RESET" = 1 ]; then
  echo "⚠️  - --reset : la base de production sera effacée (liens, catégories, sources, comptes)."
  read -r -p "    Taper le nom du site pour confirmer ($SITE_NAME) : " CONFIRM
  if [ "$CONFIRM" != "$SITE_NAME" ]; then
    echo "❌ - Confirmation incorrecte : rien n'a été déployé."
    exit 1
  fi
fi

step "Étape 1 : dépôt git dans $SITE_DIR"
remote "
  if [ ! -d '$SITE_DIR/.git' ]; then
    sudo mkdir -p '$SITE_DIR'
    sudo chown '$REMOTE_USER:$REMOTE_USER' '$SITE_DIR'
    git clone -b '$GIT_BRANCH' '$REPO_URL' '$SITE_DIR'
  else
    cd '$SITE_DIR'
    git fetch origin
    git checkout '$GIT_BRANCH'
    git pull --ff-only origin '$GIT_BRANCH'
  fi
"

step "Étape 2 : fichier d'environnement"
scp -q "$ENV_DIR/.env" "$REMOTE_USER@$REMOTE_HOST:$SITE_DIR/$ENV_DIR/.env"
remote "chmod 600 '$SITE_DIR/$ENV_DIR/.env'"

step "Étape 3 : build de l'image"
remote "cd '$SITE_DIR' && $COMPOSE build app"

# Sans --reset, les données sont conservées : migrations seulement. Jamais de fixtures (absentes de l'image).
step "Étape 4 : migrations (web et worker arrêtés)"
remote "
  cd '$SITE_DIR'
  $COMPOSE stop worker app
"
if [ "$RESET" = 1 ]; then
  echo "🗑️  - Effacement de la base (var/data/radar.db, journaux WAL)"
  remote "cd '$SITE_DIR' && $COMPOSE run --rm migrate rm -f var/data/radar.db var/data/radar.db-wal var/data/radar.db-shm"
fi
remote "cd '$SITE_DIR' && $COMPOSE run --rm migrate"

if [ "$RESET" = 1 ]; then
  # Le mot de passe passe par l'entrée standard : ni dans la ligne de commande distante, ni dans l'environnement des conteneurs.
  step "Étape 4 bis : compte $RADAR_USER_EMAIL"
  printf '%s\n' "$RADAR_USER_PASSWORD" | ssh "$REMOTE_USER@$REMOTE_HOST" \
    "set -euo pipefail; cd '$SITE_DIR' && $COMPOSE run --rm -T migrate bin/console app:user:create '$RADAR_USER_EMAIL' --password-stdin"
fi

step "Étape 5 : démarrage du web et du worker"
# --wait sur le web seul : le worker n'a pas de healthcheck, compose le traiterait comme un échec.
remote "
  cd '$SITE_DIR'
  $COMPOSE up -d --wait --wait-timeout 120 --remove-orphans app
  $COMPOSE up -d worker
"

# Le Caddy hôte sert tous les sites du serveur : reload (gracieux), jamais restart.
step "Étape 6 : configuration Caddy"
remote "
  sudo tee '$CADDY_CONF_FILE' > /dev/null <<'CADDY'
$SITE_NAME {
    reverse_proxy 127.0.0.1:$PROJECT_PORT
}
CADDY
  grep -Fxq '$IMPORT_LINE' '$MAIN_CADDYFILE' || echo '$IMPORT_LINE' | sudo tee -a '$MAIN_CADDYFILE' > /dev/null
  sudo caddy validate --config '$MAIN_CADDYFILE' --adapter caddyfile
  sudo systemctl reload caddy
"

echo ""
echo "✅ - Déployé : https://$SITE_NAME"
if [ "$RESET" = 0 ]; then
  echo "👤 - Autre compte : ssh $REMOTE_USER@$REMOTE_HOST, puis dans $SITE_DIR :"
  echo "     $COMPOSE exec app bin/console app:user:create <email>"
fi
