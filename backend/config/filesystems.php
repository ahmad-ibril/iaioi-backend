<?php

return [
    'default' => env('FILESYSTEM_DISK', 'public'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'https://iaioi.com'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [
        env('PUBLIC_STORAGE_LINK', public_path('storage')) => storage_path('app/public'),
    ],
];
