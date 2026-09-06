#!/bin/sh
set -e

: "${PORT:=8080}"
sed "s|\${PORT}|${PORT}|g" /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

php artisan migrate --force --no-interaction

# Opt-in one-shot seeding for a fresh environment; unset the variable afterwards.
if [ "${RUN_SEEDERS}" = "true" ]; then
    php artisan db:seed --force --no-interaction
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec supervisord -c /etc/supervisord.conf
