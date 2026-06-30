#!/usr/bin/env bash
set -euo pipefail

cd "$HOME/Conectta/app"

if pgrep -f "php artisan serve --host=0.0.0.0 --port=8000" >/dev/null; then
  echo "Conectta ja esta rodando em http://127.0.0.1:8000/admin/login"
  exit 0
fi

nohup php artisan serve --host=0.0.0.0 --port=8000 > "$HOME/Conectta/tmp/server-8000.log" 2>&1 < /dev/null &

echo "Conectta iniciado em http://127.0.0.1:8000/admin/login"
