<?php

return [
    'enabled' => (bool) env('VISITOR_STATISTICS_ENABLED', env('APP_ENV') === 'production'),
    'timezone' => 'Asia/Jakarta',
    'cookie_name' => 'sid_visitor',
    'cookie_minutes' => 60 * 24 * 30,
    'cache_seconds' => 300,
    'retention_days' => 35,
    'routes' => [
        'home', 'profile', 'information.population', 'information.activities',
        'news.index', 'news.show', 'gallery.index',
        'announcements.index', 'announcements.show', 'download.app',
        'services.pbb', 'services.letter', 'services.complaint',
    ],
];
