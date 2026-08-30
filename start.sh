#!/usr/bin/env bash
#
# Sobe o backend Laravel (wpp-trello-copilot) com um único comando.
# Faz o setup inicial automaticamente na primeira execução (composer install,
# .env, chave da aplicação, banco SQLite, migrations e assets do Tailwind).

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CORE_DIR="$SCRIPT_DIR/core"

cd "$CORE_DIR"

if [ ! -f .env ]; then
    echo "==> Criando .env a partir de .env.example"
    cp .env.example .env
fi

if [ ! -d vendor ]; then
    echo "==> Instalando dependências PHP (composer install)"
    composer install --no-interaction
fi

if ! grep -q "^APP_KEY=base64" .env 2>/dev/null; then
    echo "==> Gerando APP_KEY"
    php artisan key:generate
fi

if [ ! -f database/database.sqlite ]; then
    echo "==> Criando banco SQLite"
    touch database/database.sqlite
fi

echo "==> Rodando migrations"
php artisan migrate --force

if [ ! -d node_modules ]; then
    echo "==> Instalando dependências JS (npm install)"
    npm install
fi

if [ ! -d public/build ]; then
    echo "==> Compilando assets (npm run build)"
    npm run build
fi

echo "==> Subindo servidor em http://localhost:8000 (Ctrl+C para encerrar)"
php artisan dev
