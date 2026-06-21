# Arab Rentals Backend API

Laravel REST API foundation for an Arabic RTL bookings and rentals marketplace.

This first phase contains:

- MySQL database schema documentation.
- Laravel migrations for core data, category-specific details, availability, favorites, and admin/app users.
- Eloquent models and relationships.
- Versioned API routes under `/api/v1`.
- Public listing/category/filter/location endpoints.
- Admin authentication and listing CRUD API structure.
- Admin category CRUD and dynamic per-category filters.

## Hostinger setup

The production backend is deployed outside `public_html` at
`domains/iaioi.com/backend`. After uploading the source, run:

```bash
cd backend
composer install --no-dev --optimize-autoloader
cp .env.production.example .env
php artisan key:generate --force
php artisan optimize:clear
php artisan migrate --force
```

Production API base:

```text
https://iaioi.com/api/v1
```

The schema is documented in `docs/database-schema.md`.
The API routes are documented in `docs/api-structure.md`.
