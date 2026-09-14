<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],


    'groq' => [
        'key'           => env('GROQ_API_KEY'),
        'base_url'      => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model_primary'  => env('GROQ_MODEL_PRIMARY', 'openai/gpt-oss-120b'),
        'model_secondary'=> env('GROQ_MODEL_SECONDARY', 'qwen/qwen3-27b'),
        'model_fallback' => env('GROQ_MODEL_FALLBACK', 'openai/gpt-oss-20b'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
