#!/usr/bin/env bash
set -euo pipefail

NGROK_BIN="${NGROK_BIN:-/home/diel_/bin/ngrok}"
PORT="${1:-${NGROK_PORT:-8000}}"
NGROK_API_URL="${NGROK_API_URL:-http://127.0.0.1:4040/api/tunnels}"
APP_URL="http://127.0.0.1:${PORT}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
NGROK_LOG="${NGROK_LOG:-/tmp/conectta-ngrok-${PORT}.log}"
NGROK_PID=""

if [[ ! -x "${NGROK_BIN}" ]]; then
    echo "Erro: ngrok nao encontrado ou sem permissao de execucao em ${NGROK_BIN}."
    echo "Ajuste com: NGROK_BIN=/caminho/do/ngrok $0 ${PORT}"
    exit 1
fi

echo "Iniciando ngrok para ${APP_URL}"
echo "Use Ctrl+C para encerrar."
echo

"${NGROK_BIN}" http "${PORT}" >"${NGROK_LOG}" 2>&1 &
NGROK_PID="$!"

cleanup() {
    if [[ -n "${NGROK_PID}" ]] && kill -0 "${NGROK_PID}" 2>/dev/null; then
        kill "${NGROK_PID}" 2>/dev/null || true
    fi
}
trap cleanup EXIT INT TERM

public_url=""

for _ in {1..20}; do
    if ! kill -0 "${NGROK_PID}" 2>/dev/null; then
        echo "Erro: o ngrok parou antes de criar o tunel."
        echo "Log: ${NGROK_LOG}"
        tail -n 20 "${NGROK_LOG}" || true
        wait "${NGROK_PID}" || true
        exit 1
    fi

    if tunnels_json="$(curl -fsS "${NGROK_API_URL}" 2>/dev/null)"; then
        public_url="$(
            printf '%s' "${tunnels_json}" \
                | php -r '$data = json_decode(stream_get_contents(STDIN), true); echo $data["tunnels"][0]["public_url"] ?? "";'
        )"

        if [[ -n "${public_url}" ]]; then
            echo "URL publica:"
            echo "${public_url}"
            echo
            echo "Painel local do ngrok: http://127.0.0.1:4040"
            echo "Log do ngrok: ${NGROK_LOG}"
            echo
            break
        fi
    fi

    sleep 1
done

if [[ -z "${public_url}" ]]; then
    echo "Erro: nao foi possivel obter a URL publica do ngrok."
    echo "Log: ${NGROK_LOG}"
    tail -n 20 "${NGROK_LOG}" || true
    exit 1
fi

if curl -fsS "${APP_URL}" >/dev/null 2>&1; then
    echo "Laravel ja esta respondendo em ${APP_URL}."
    echo "Mantendo ngrok ativo. Use Ctrl+C para encerrar."
    wait "${NGROK_PID}"
else
    echo "Subindo Laravel em ${APP_URL}..."
    echo
    cd "${ROOT_DIR}"
    php artisan serve --host=127.0.0.1 --port="${PORT}"
fi
