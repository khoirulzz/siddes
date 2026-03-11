<?php

return [
    'enabled' => (bool) env('CLOUDINARY_ENABLED', false),
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME', env('VITE_CLOUDINARY_CLOUD_NAME', '')),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),
    'secure' => (bool) env('CLOUDINARY_SECURE', true),
    'timeout_seconds' => (int) env('CLOUDINARY_TIMEOUT_SECONDS', 20),

    'folders' => [
        'dynamic' => env('CLOUDINARY_FOLDER_DYNAMIC', env('VITE_CLOUDINARY_FOLDER_DYNAMIC', 'sid/dynamic')),
        'archives' => env('CLOUDINARY_FOLDER_ARCHIVES', env('VITE_CLOUDINARY_FOLDER_ARCHIVES', 'sid/archives')),
    ],
];

