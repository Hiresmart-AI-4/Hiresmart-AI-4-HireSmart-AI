#!/bin/sh
set -e

cd /var/www

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not set. Generate one with: php artisan key:generate --show"
    exit 1
fi

# Use file-based cache/sessions at boot (avoids missing DB tables before migrate)
export CACHE_STORE=file
export SESSION_DRIVER=file
export QUEUE_CONNECTION=sync

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

php artisan storage:link --force 2>/dev/null || true

# Run migrations first so PostgreSQL tables (cache, sessions, etc.) exist
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

# Clear stale bootstrap files (do not use optimize:clear — it hits DB cache before tables exist)
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
