<?php

return [
    'media' => [
        'allowed_prefixes' => [
            'news/',
            'galleries/',
            'land/',
            'activities/',
            'complaints/',
            'service-archives/',
        ],
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
            'video/mp4',
            'video/quicktime',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
        ],
        'inline_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
            'video/mp4',
            'video/quicktime',
        ],
    ],
];
