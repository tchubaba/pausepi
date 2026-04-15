#!/bin/bash
set -e

APP_ENV="${APP_ENV:-production}"

cd /var/www

# Bootstrap .env on first run
if [ ! -f .env ]; then
    echo "[pausepi] .env not found — copying from .env.example"
    cp .env.example .env
fi

# Install / update PHP dependencies
if [ "$APP_ENV" = "production" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction --quiet
else
    echo "[pausepi] Dev mode — installing all Composer dependencies"
    composer install --no-interaction
    php artisan ide-helper:generate
fi

# Generate app key if not present
if ! grep -qE '^APP_KEY=.+' .env; then
    echo "[pausepi] Generating application key..."
    php artisan key:generate --no-interaction
fi

# Run database migrations
php artisan migrate --force --no-interaction

echo "[pausepi] Starting server on 0.0.0.0:8000 (APP_ENV=${APP_ENV})"
exec php artisan serve --host=0.0.0.0 --port=8000
