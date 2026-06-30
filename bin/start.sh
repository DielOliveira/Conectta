#!/usr/bin/env bash
set -euo pipefail

cd "$HOME/Conectta/app"

if ! mysqladmin ping -uconectta -pconectta --silent >/dev/null 2>&1; then
  echo "Iniciando MySQL..."
  sudo service mysql start
fi

echo "Conectta: http://127.0.0.1:8000/admin/login"
php artisan serve --host=0.0.0.0 --port=8000
