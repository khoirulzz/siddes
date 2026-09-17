<?php

return [
    'enabled' => filter_var(env('MESSAGING_ENABLED', false), FILTER_VALIDATE_BOOL),
    'base_url' => rtrim((string) env('MESSAGING_API_URL', ''), '/'),
    'operator_key' => env('MESSAGING_OPERATOR_API_KEY', ''),
    'admin_key' => env('MESSAGING_ADMIN_API_KEY', ''),
];
