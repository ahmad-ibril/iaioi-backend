#!/usr/bin/env bash

set -Eeuo pipefail

backend_dir="${HOME}/domains/iaioi.com/backend"

cd "${backend_dir}"

if [[ ! -f .env ]]; then
    echo "Missing ${backend_dir}/.env" >&2
    exit 1
fi

if grep -Eq '^DB_PASSWORD=(|CHANGE_TO_HOSTINGER_DATABASE_PASSWORD)$' .env; then
    echo "Set the Hostinger DB_PASSWORD in ${backend_dir}/.env first." >&2
    exit 1
fi

if ! grep -Eq '^GOOGLE_CLIENT_IDS=.+\.apps\.googleusercontent\.com' .env; then
    echo "Set GOOGLE_CLIENT_IDS to the Google Web OAuth client ID in ${backend_dir}/.env first." >&2
    exit 1
fi

composer install --no-dev --optimize-autoloader

if ! grep -Eq '^APP_KEY=base64:.+' .env; then
    php artisan key:generate --force
fi

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force
php artisan db:seed --force
php artisan storage:link

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Backend deployment completed."
