# Database Schema

The schema uses one central `listings` table for shared service data, then category detail tables for filterable fields that differ by section.

## Core Tables

- `users`: app users and admins, separated by `role`.
- `countries`: supported countries in the Arab region.
- `cities`: cities/regions linked to countries.
- `categories`: service sections such as chalets, sports fields, wedding halls, cars, buses, hotels, and tourism offices.
- `category_filters`: dynamic filter definitions per category. Admins can add, edit, and remove filters without adding a new migration.
- `listings`: shared service data: title, description, location, contact numbers, base price, currency, status, and publishing state.
- `listing_attribute_values`: dynamic values for category filters, such as furnished status, rooms count, gender policy, working hours, delivery service, or provider rating.
- `listing_images`: uploaded images for each service.
- `listing_features`: service features/add-ons shown in details screens.
- `listing_calendar_dates`: availability calendar per service.
- `favorites`: saved listings for app users.

## Category Detail Tables

- `chalet_details`: area size, rooms, bathrooms, pool, heated pool, guests.
- `sports_field_details`: sport type and field metadata.
- `wedding_hall_details`: capacity and hall options.
- `wedding_supply_details`: product/package metadata.
- `car_rental_details`: car type, brand, model, driver option.
- `bus_rental_details`: seats count and driver option.
- `hotel_details`: stars and check-in/out details.
- `hotel_rooms`: rooms belonging to a hotel listing.
- `hotel_room_images`: room-level images.
- `hotel_room_calendar_dates`: room-level availability.
- `tourism_program_details`: destination, duration, trip date, trip type, included services, and flight times.

## Availability Rules

- `available`: explicitly available day.
- `booked`: unavailable because it is reserved.
- `blocked`: unavailable because admin manually blocked it.
- Missing dates may be treated by the API as available unless blocked/booked rows exist in a requested range.

## API Filtering Strategy

Common filters use `listings`: category, city, price range, text search, active status, and distance sorting.

Category filters use detail relationships:

- Chalets: area size, rooms, pool, heated pool.
- Sports fields: field type.
- Wedding halls: capacity.
- Cars: car type and with/without driver.
- Buses: seats count and with/without driver.
- Hotels: stars.
- Tourism programs: country, city, duration, date, and domestic/international trip type.

New and admin-defined categories use `category_filters` + `listing_attribute_values`.

Example dynamic filters:

```text
GET /api/v1/categories/apartments-rent/listings?filters[furnished_status]=furnished&filters[rooms_count][min]=2
GET /api/v1/categories/turkish-baths/listings?filters[gender_policy]=families
GET /api/v1/categories/furniture-moving/listings?filters[movement_scope]=inside_city
```

Each dynamic filter has:

- `key`: API field name, for example `rooms_count`.
- `input_type`: `text`, `number`, `boolean`, `select`, `multi_select`, `date`, `time`, or `rating`.
- `options`: select choices for the app UI.
- `is_filterable`: whether it appears in user filters.
- `is_sortable`: whether it can later be used for dynamic sorting.

Categories also include:

- `group_key`: such as `bookings`, `entertainment-tourism`, `real-estate`, `services`, or `garden-nursery`.
- `supports_booking`: controls whether the app should show calendar availability for that category.
- `parent_id`: optional category hierarchy support.
