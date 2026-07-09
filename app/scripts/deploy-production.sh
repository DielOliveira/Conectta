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

echo "Incrementar versao do sistema em producao?"
echo "  1) Patch  (1.0.0 -> 1.0.1)"
echo "  2) Minor  (1.0.0 -> 1.1.0)"
echo "  3) Major  (1.0.0 -> 2.0.0)"
echo "  4) Manter versao atual"
read -r -p "Escolha [1-4, Enter=4]: " version_choice

case "${version_choice:-4}" in
    1)
        VERSION_BUMP="patch"
        ;;
    2)
        VERSION_BUMP="minor"
        ;;
    3)
        VERSION_BUMP="major"
        ;;
    4)
        VERSION_BUMP="none"
        ;;
    *)
        echo "Erro: opcao de versao invalida."
        exit 1
        ;;
esac

echo "Publicando branch ${LOCAL_BRANCH} no GitHub..."
git -c core.sshCommand="ssh -F /dev/null -i ${GITHUB_SSH_KEY} -o IdentitiesOnly=yes" push "${REMOTE_NAME}" "${LOCAL_BRANCH}"

echo "Atualizando VPS..."
ssh -F /dev/null -i "${VPS_KEY}" -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new "${VPS_USER}@${VPS_HOST}" bash -s -- "${VERSION_BUMP}" <<'REMOTE'
set -euo pipefail

VERSION_BUMP="${1:-none}"

bump_app_version() {
    local current major minor patch next

    current="$(grep -E '^APP_VERSION=' .env 2>/dev/null | tail -n 1 | cut -d '=' -f 2- | tr -d '"'\''[:space:]')"
    current="${current:-1.0.0}"

    IFS='.' read -r major minor patch <<< "${current}"
    major="${major//[^0-9]/}"
    minor="${minor//[^0-9]/}"
    patch="${patch//[^0-9]/}"
    major="${major:-1}"
    minor="${minor:-0}"
    patch="${patch:-0}"

    case "${VERSION_BUMP}" in
        patch)
            patch=$((patch + 1))
            ;;
        minor)
            minor=$((minor + 1))
            patch=0
            ;;
        major)
            major=$((major + 1))
            minor=0
            patch=0
            ;;
        none)
            return 0
            ;;
        *)
            echo "Erro: incremento de versao invalido: ${VERSION_BUMP}"
            exit 1
            ;;
    esac

    next="${major}.${minor}.${patch}"

    if grep -qE '^APP_VERSION=' .env; then
        sed -i "s/^APP_VERSION=.*/APP_VERSION=${next}/" .env
    else
        printf '\nAPP_VERSION=%s\n' "${next}" >> .env
    fi

    echo "Versao atualizada: ${current} -> ${next}"
}

cd /var/www/conectta/repo
git fetch origin main
git checkout main
git pull --ff-only origin main

cd /var/www/conectta/repo/app
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
npm install --no-audit --no-fund
npm run build
php artisan migrate --force
bump_app_version
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

cat >/etc/cron.d/conectta-scheduler <<'CRON'
* * * * * root cd /var/www/conectta/repo/app && php artisan schedule:run >> /var/log/conectta-scheduler.log 2>&1
CRON
chmod 644 /etc/cron.d/conectta-scheduler

chown -R root:www-data /var/www/conectta/repo/app
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f ! -name .gitignore -exec chmod 664 {} \;

echo "Commit em producao:"
git -C /var/www/conectta/repo rev-parse --short HEAD
REMOTE

echo "Deploy concluido."
