<?php

require dirname(__DIR__).'/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'u583813446_iaioi_db';
$username = $_ENV['DB_USERNAME'] ?? 'u583813446_iaioi_user';
$password = $_ENV['DB_PASSWORD'] ?? '';

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
    $username,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

function utf8_text(string $value): string
{
    return base64_decode($value);
}

$hotelId = $pdo
    ->query("SELECT id FROM listings WHERE slug = 'demo-aqaba-sea-view-hotel' LIMIT 1")
    ->fetchColumn();

if (! $hotelId) {
    echo "Hotel demo listing is missing.\n";
    exit(1);
}

$pdo->prepare('DELETE FROM hotel_rooms WHERE hotel_listing_id = ?')->execute([$hotelId]);

$roomInsert = $pdo->prepare(
    'INSERT INTO hotel_rooms
        (hotel_listing_id, name_ar, name_en, room_type, description_ar, capacity_adults, capacity_children, price_per_night, currency_code, total_rooms, is_active, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())',
);
$imageInsert = $pdo->prepare(
    'INSERT INTO hotel_room_images
        (hotel_room_id, path, alt_text_ar, sort_order, is_cover, created_at, updated_at)
     VALUES (?, ?, ?, 0, 1, NOW(), NOW())',
);
$dateInsert = $pdo->prepare(
    'INSERT INTO hotel_room_calendar_dates
        (hotel_room_id, date, status, available_quantity, price_override, created_at, updated_at)
     VALUES (?, DATE_ADD(CURDATE(), INTERVAL ? DAY), ?, ?, NULL, NOW(), NOW())',
);

$rooms = [
    [
        'name_ar' => utf8_text('2LrYsdmB2Kkg2YXYstiv2YjYrNipINio2KXYt9mE2KfZhNipINio2K3YsdmK2Kk='),
        'name_en' => 'Sea View Double Room',
        'room_type' => 'double',
        'description_ar' => utf8_text('2LrYsdmB2Kkg2YTYtNiu2LXZitmGINmF2Lkg2LTYsdmB2Kkg2YXYt9mE2Kkg2LnZhNmJINin2YTYqNit2LEu'),
        'capacity_adults' => 2,
        'capacity_children' => 1,
        'price_per_night' => 95.00,
        'total_rooms' => 8,
        'image' => 'https://picsum.photos/seed/arab-rentals-hotel-room-sea-1/900/600',
        'dates' => [[0, 'available', 3], [1, 'available', 2], [4, 'booked', 0]],
    ],
    [
        'name_ar' => utf8_text('2KzZhtin2K0g2LnYp9im2YTZig=='),
        'name_en' => 'Family Suite',
        'room_type' => 'suite',
        'description_ar' => utf8_text('2KzZhtin2K0g2YXZhtin2LPYqCDZhNmE2LnYp9im2YTYp9iqINmF2Lkg2LrYsdmB2KrZitmGINmI2LXYp9mE2Kku'),
        'capacity_adults' => 4,
        'capacity_children' => 2,
        'price_per_night' => 160.00,
        'total_rooms' => 4,
        'image' => 'https://picsum.photos/seed/arab-rentals-hotel-family-suite-1/900/600',
        'dates' => [[0, 'available', 2], [2, 'available', 1], [9, 'booked', 0]],
    ],
];

foreach ($rooms as $room) {
    $roomInsert->execute([
        $hotelId,
        $room['name_ar'],
        $room['name_en'],
        $room['room_type'],
        $room['description_ar'],
        $room['capacity_adults'],
        $room['capacity_children'],
        $room['price_per_night'],
        'JOD',
        $room['total_rooms'],
    ]);

    $roomId = (int) $pdo->lastInsertId();
    $imageInsert->execute([$roomId, $room['image'], $room['name_ar']]);

    foreach ($room['dates'] as [$offset, $status, $quantity]) {
        $dateInsert->execute([$roomId, $offset, $status, $quantity]);
    }
}

echo "Demo hotel rooms refreshed.\n";
