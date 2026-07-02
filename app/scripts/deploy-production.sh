#!/usr/bin/env bash
set -euo pipefail

LOCAL_BRANCH="main"
REMOTE_NAME="origin"
VPS_HOST="191.252.200.172"
VPS_USER="root"
VPS_KEY="${HOME}/.ssh/conectta_vps"
REMOTE_REPO_DIR="/var/www/conectta/repo"
REMOTE_APP_DIR="${REMOTE_REPO_DIR}/app"
GITHUB_SSH_KEY="${HOME}/.ssh/id_ed25519"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "${ROOT_DIR}"

current_branch="$(git branch --show-current)"
if [[ "${current_branch}" != "${LOCAL_BRANCH}" ]]; then
    echo "Erro: branch atual e '${current_branch}', mas producao usa '${LOCAL_BRANCH}'."
    exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Erro: existem alteracoes locais sem commit."
    echo "Faça commit antes de publicar em producao."
    git status --short
    exit 1
fi

echo "Publicando branch ${LOCAL_BRANCH} no GitHub..."
git -c core.sshCommand="ssh -F /dev/null -i ${GITHUB_SSH_KEY} -o IdentitiesOnly=yes" push "${REMOTE_NAME}" "${LOCAL_BRANCH}"

echo "Atualizando VPS..."
ssh -F /dev/null -i "${VPS_KEY}" -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new "${VPS_USER}@${VPS_HOST}" bash -s <<'REMOTE'
set -euo pipefail

cd /var/www/conectta/repo
git fetch origin main
git checkout main
git pull --ff-only origin main

cd /var/www/conectta/repo/app
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
npm install --no-audit --no-fund
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R root:www-data /var/www/conectta/repo/app
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f ! -name .gitignore -exec chmod 664 {} \;

echo "Commit em producao:"
git -C /var/www/conectta/repo rev-parse --short HEAD
REMOTE

echo "Deploy concluido."
