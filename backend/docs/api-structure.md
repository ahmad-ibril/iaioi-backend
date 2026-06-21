# API Structure

Base URL:

```text
/api/v1
```

## Public

- `GET /health`
- `GET /categories`
- `GET /categories/{slug}`
- `GET /categories/{slug}/listings`
- `GET /listings`
- `GET /listings/{slug}`
- `GET /listings/{slug}/availability?from=YYYY-MM-DD&to=YYYY-MM-DD`
- `GET /filters/options`
- `GET /locations/countries`
- `GET /locations/cities?country_id=1`

## Favorites

Requires `auth:sanctum`.

- `GET /favorites`
- `POST /favorites`
- `DELETE /favorites/{listing}`

## Admin

- `POST /admin/login`
- `GET /admin/me`
- `POST /admin/logout`
- `GET /admin/listings`
- `POST /admin/listings`
- `GET /admin/listings/{listing}`
- `PUT/PATCH /admin/listings/{listing}`
- `DELETE /admin/listings/{listing}`
- `GET /admin/categories`
- `POST /admin/categories`
- `GET /admin/categories/{category}`
- `PUT/PATCH /admin/categories/{category}`
- `DELETE /admin/categories/{category}`
- `GET /admin/categories/{category}/filters`
- `POST /admin/categories/{category}/filters`
- `PUT/PATCH /admin/categories/{category}/filters/{filter}`
- `DELETE /admin/categories/{category}/filters/{filter}`

## Listing Query Filters

Common:

- `q`
- `category`
- `country_id`
- `city_id`
- `area`
- `min_price`
- `max_price`
- `available_from`
- `available_to`
- `sort=newest|price_asc|price_desc|distance`
- `latitude`
- `longitude`
- `per_page`

Category-specific:

- Chalets: `min_area`, `rooms_count`, `has_pool`, `pool_is_heated`
- Sports fields: `field_type`
- Wedding halls: `capacity_min`
- Cars: `car_type`, `with_driver`, `seats_min`
- Buses: `with_driver`, `seats_min`
- Hotels: `stars`
- Tourism programs: `trip_type`, `trip_date`

Dynamic filters:

- Send category-defined filters under `filters`.
- Numeric filters accept exact values or `{min, max}`.
- Multi-select filters accept one value or an array.

```text
GET /categories/apartments-rent/listings?filters[furnished_status]=furnished&filters[rooms_count][min]=3
GET /categories/parks/listings?filters[family_friendly]=1&filters[has_kids_games]=1
```

Admin listing payloads can include dynamic values:

```json
{
  "category_id": 21,
  "title_ar": "شقة مفروشة في عمّان",
  "base_price": 350,
  "phone": "+962790000000",
  "whatsapp": "+962790000000",
  "attributes": {
    "furnished_status": "furnished",
    "rooms_count": 3,
    "bathrooms_count": 2,
    "area_size": 120,
    "has_elevator": true
  }
}
```
