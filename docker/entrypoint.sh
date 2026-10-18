#!/bin/sh
set -e

: "${PORT:=8080}"
sed "s|\${PORT}|${PORT}|g" /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

php artisan migrate --force --no-interaction

# Migrations run as root during container startup, while PHP-FPM handles
# requests as www-data. Keep SQLite and its directory writable by the app.
chown -R www-data:www-data database

# Opt-in one-shot seeding for a fresh environment; unset the variable afterwards.
if [ "${RUN_SEEDERS}" = "true" ]; then
    php artisan db:seed --force --no-interaction
fi

# Opt-in integrated demo data. The seeder is idempotent and this flag should
# be disabled immediately after the deployment that creates the demo records.
if [ "${RUN_DEMO_SEEDER}" = "true" ]; then
    php artisan db:seed --class=DemoDataSeeder --force --no-interaction
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec supervisord -c /etc/supervisord.conf
