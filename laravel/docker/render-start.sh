#!/bin/sh
set -e

cd /var/www

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not set. Generate one with: php artisan key:generate --show"
    exit 1
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

php artisan storage:link --force 2>/dev/null || true
php artisan optimize:clear

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
