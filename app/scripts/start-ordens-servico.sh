#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${1:-${OS_PORT:-8000}}"

cd "${ROOT_DIR}"

CURRENT_BRANCH="$(git branch --show-current)"

if [[ "${CURRENT_BRANCH}" != "feature/ordens-servico" ]]; then
    echo "Erro: este script deve ser executado no worktree da branch feature/ordens-servico."
    echo "Diretorio esperado: /home/diel_/Conectta/.worktrees/ordens-servico/app"
    exit 1
fi

echo "Ambiente local de Ordens de Servico"
echo "Branch: ${CURRENT_BRANCH}"
echo "Porta: ${PORT}"
echo

exec "${ROOT_DIR}/scripts/start-ngrok.sh" "${PORT}"
