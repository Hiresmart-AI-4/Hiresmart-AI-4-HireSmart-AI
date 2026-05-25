#!/bin/sh
set -e

cd /var/www/html/laravel

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache

php artisan storage:link --force || true
php artisan optimize:clear

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

php artisan config:cache
php artisan view:cache

php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
