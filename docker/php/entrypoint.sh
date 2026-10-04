#!/bin/bash
set -e

cd /var/www/html

# Make sure a .env exists (first boot on a fresh volume/droplet)
if [ ! -f .env ]; then
    echo "No .env found, copying .env.example..."
    cp .env.example .env
fi

# Wait until the database container is accepting connections
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
    until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
        sleep 2
    done
    echo "Database is up."
fi

# Re-assert writable permissions (bind mounts can reset these)
chmod -R ug+rwx storage bootstrap/cache || true

# Regenerate the discovered-package manifest from what's actually installed in this
# image/volume, so a stale cache (e.g. copied in from a host dev environment) can
# never reference packages that aren't really present (no-dev installs, etc.).
composer dump-autoload --optimize --no-interaction
php artisan package:discover --ansi

php artisan key:generate --force --no-interaction
php artisan config:clear
php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
