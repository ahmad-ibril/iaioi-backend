# Hostinger Deployment Guide for iaioi.com

This project is prepared for:

- Flutter Web frontend on `https://iaioi.com`
- Laravel 10 backend API on `https://iaioi.com/api/v1`
- MySQL database on Hostinger
- Public uploads on `https://iaioi.com/storage/...`

## Recommended Domain Layout

Use the same domain for the frontend and API:

```text
https://iaioi.com            Flutter Web
https://iaioi.com/api/v1     Laravel API
https://iaioi.com/storage    Uploaded files
```

This is preferred over `https://api.iaioi.com/api` for this project because
`APP_URL=https://iaioi.com` is required and uploaded media URLs are generated
from the main domain. Same-origin API calls also reduce CORS issues.

## Hostinger Folder Layout

Create or use this structure under the domain directory:

```text
domains/iaioi.com/
  backend/       Laravel project, not public
  public_html/   Flutter build output
```

The file `public_html/api/index.php` is included in the Flutter web build and
boots Laravel from `api/../../backend`.

If your backend folder is not named `backend`, edit:

```text
mobile_app/web/api/index.php
```

and update the `$basePath` line before building Flutter.

## Build Flutter Web

From the repository root on your local machine:

```powershell
.\deployment\hostinger\build-public-html.ps1 `
  -GoogleClientId "YOUR_WEB_GOOGLE_CLIENT_ID.apps.googleusercontent.com"
```

If Windows blocks script execution locally, use:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass `
  -File .\deployment\hostinger\build-public-html.ps1 `
  -GoogleClientId "YOUR_WEB_GOOGLE_CLIENT_ID.apps.googleusercontent.com"
```

This runs:

```powershell
flutter build web --release --base-href / `
  --dart-define API_BASE_URL=https://iaioi.com/api/v1 `
  --dart-define GOOGLE_CLIENT_ID=YOUR_WEB_GOOGLE_CLIENT_ID.apps.googleusercontent.com
```

Use the same Web OAuth client ID in Flutter and Laravel. In Google Cloud, add
these Authorized JavaScript origins to that client:

```text
https://iaioi.com
https://www.iaioi.com
```

Upload the contents of:

```text
mobile_app/build/web/
```

to Hostinger:

```text
domains/iaioi.com/public_html/
```

The build must include:

- `index.html`
- `.htaccess`
- `api/index.php`
- `manifest.json`
- `robots.txt`
- `sitemap.xml`

## Flutter Routing on Hostinger

The file below handles Flutter Web fallback routing and sends `/api/*` to
Laravel:

```text
mobile_app/web/.htaccess
```

It is copied into `build/web/.htaccess` by the build.

## Deploy Laravel Backend

Upload the `backend` directory to:

```text
domains/iaioi.com/backend/
```

Do not upload these generated or sensitive files:

```text
backend/.env
backend/storage/logs/*
backend/storage/framework/cache/data/*
backend/storage/framework/sessions/*
backend/storage/framework/views/*
backend/vendor/    optional if you will run composer install on Hostinger
```

After uploading the backend, the prepared SSH deployment script can run the
full install, migration, seed, storage and cache sequence:

```bash
bash ~/domains/iaioi.com/backend/scripts/deploy-hostinger.sh
```

The script always targets `~/domains/iaioi.com/backend`.

## Configure Laravel Production Environment

On Hostinger, copy:

```text
backend/.env.production.example
```

to:

```text
backend/.env
```

Then edit these values in Hostinger File Manager or over SSH:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://iaioi.com
APP_NAME="IAIOI"

DB_HOST=localhost
DB_DATABASE=u583813446_iaioi_db
DB_USERNAME=u583813446_iaioi_user
DB_PASSWORD=YOUR_HOSTINGER_DB_PASSWORD

CORS_ALLOWED_ORIGINS=https://iaioi.com,https://www.iaioi.com
SANCTUM_STATEFUL_DOMAINS=iaioi.com,www.iaioi.com
SESSION_DOMAIN=.iaioi.com
SESSION_SECURE_COOKIE=true
FILESYSTEM_DISK=public
PUBLIC_STORAGE_LINK=/home/u583813446/domains/iaioi.com/public_html/storage
GOOGLE_CLIENT_IDS=YOUR_WEB_GOOGLE_CLIENT_ID.apps.googleusercontent.com
```

Generate the app key once on Hostinger:

```bash
cd domains/iaioi.com/backend
php artisan key:generate --force
```

## Install PHP Dependencies

If you did not upload `vendor/`, run:

```bash
cd domains/iaioi.com/backend
composer install --no-dev --optimize-autoloader
```

If Composer is not available as `composer`, install or use Hostinger Composer
as described in their SSH documentation.

## Laravel Cache and Optimization

Run after `.env` is correct:

```bash
cd domains/iaioi.com/backend
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

After any future `.env`, route, or config change, run:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Database Setup

Create a MySQL database from Hostinger hPanel, then run:

```bash
cd domains/iaioi.com/backend
php artisan migrate --force
```

For production starter data without demo listings:

```bash
php artisan db:seed --class=LocationSeeder --force
php artisan db:seed --class=CategorySeeder --force
php artisan db:seed --class=AdminUserSeeder --force
```

Do not run the full `DatabaseSeeder` in production unless demo listings are
wanted, because it also calls `DemoListingSeeder`.

If you need to migrate existing local data instead of starting fresh, export it
locally and import it through Hostinger phpMyAdmin:

```bash
mysqldump -u root -p arab_rentals > iaioi-production.sql
```

Then import `iaioi-production.sql` into the Hostinger database.

## Storage and Uploads

Laravel stores uploaded listing media under:

```text
backend/storage/app/public/
```

Create the public storage link configured by `PUBLIC_STORAGE_LINK`:

```bash
cd domains/iaioi.com/backend
php artisan storage:link
```

Also ensure Laravel storage directories are writable:

```bash
cd domains/iaioi.com/backend
chmod -R 775 storage bootstrap/cache
mkdir -p storage/app/public
```

After this, uploaded images should resolve as:

```text
https://iaioi.com/storage/listings/...
```

## API Smoke Tests

After upload and cache commands:

```bash
curl https://iaioi.com/api/v1/health
curl https://iaioi.com/api/v1/categories
curl https://iaioi.com/api/v1/home
curl https://iaioi.com/api/debug
```

Expected health response:

```json
{"status":"ok","app_url":"https://iaioi.com","database":"u583813446_iaioi_db","db_connected":true,"php":"8.3.30","laravel":"10.x"}
```

## Security Checklist

- `APP_ENV=production`
- `APP_DEBUG=false`
- A generated `APP_KEY` exists
- Hostinger SSL is enabled for `iaioi.com`
- `.env` is not inside `public_html`
- Laravel source is not inside `public_html`
- `storage` and `bootstrap/cache` are writable
- Uploaded media is exposed only through `public_html/storage`
- CORS is limited to `https://iaioi.com` and `https://www.iaioi.com`
- Admin password is changed before seeding `AdminUserSeeder`

## Optional API Subdomain

Only use `https://api.iaioi.com/api/v1` if Hostinger is configured with a
separate subdomain whose document root points to Laravel `public/`.

If you choose that path, change:

```text
mobile_app/lib/src/core/config/app_config.dart
deployment/hostinger/build-public-html.ps1
backend/.env.production.example
backend/.env
```

to use:

```text
API_BASE_URL=https://api.iaioi.com/api/v1
APP_URL=https://api.iaioi.com
CORS_ALLOWED_ORIGINS=https://iaioi.com,https://www.iaioi.com
```

For the current project, the same-domain setup is the recommended production
configuration.

## References

- Hostinger Laravel deployment:
  `https://www.hostinger.com/support/6152127-how-to-deploy-laravel-8-at-hostinger/`
- Hostinger Composer over SSH:
  `https://www.hostinger.com/support/8727597-how-to-install-composer-locally-at-hostinger/`
- Hostinger `.htaccess` file:
  `https://www.hostinger.com/support/1583307-how-to-create-an-htaccess-file-at-hostinger/`
