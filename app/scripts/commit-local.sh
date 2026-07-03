#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "${ROOT_DIR}"

if [[ -z "$(git status --porcelain)" ]]; then
    echo "Nada para commitar."
    exit 0
fi

echo "Alteracoes encontradas:"
git status --short
echo

read -r -p "Descricao do commit: " COMMIT_MESSAGE

if [[ -z "${COMMIT_MESSAGE// }" ]]; then
    echo "Erro: descricao do commit nao pode ficar vazia."
    exit 1
fi

git add -A

if git diff --cached --quiet; then
    echo "Nada para commitar apos preparar os arquivos."
    exit 0
fi

git commit -m "${COMMIT_MESSAGE}"

echo
echo "Commit local criado:"
git log -1 --oneline
