<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as OpenAI, Jooble, Geoapify, Mailboxlayer, Brevo, AWS and more. This file provides the de facto
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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'jooble' => [
        'api_key' => env('JOOBLE_API_KEY'),
        'base_url' => env('JOOBLE_BASE_URL', 'https://jooble.org/api'),
        'timeout' => env('JOOBLE_TIMEOUT', 20),
    ],

    'geoapify' => [
        'api_key' => env('GEOAPIFY_API_KEY'),
        'base_url' => env('GEOAPIFY_BASE_URL', 'https://api.geoapify.com/v1/geocode/search'),
        'timeout' => env('GEOAPIFY_TIMEOUT', 10),
    ],

    'mailboxlayer' => [
        'api_key' => env('MAILBOXLAYER_API_KEY'),
        'base_url' => env('MAILBOXLAYER_BASE_URL', 'https://apilayer.net/api/check'),
        'timeout' => env('MAILBOXLAYER_TIMEOUT', 10),
        'strict' => filter_var(env('MAILBOXLAYER_STRICT', false), FILTER_VALIDATE_BOOL),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'base_url' => env('BREVO_BASE_URL', 'https://api.brevo.com/v3'),
        'timeout' => env('BREVO_TIMEOUT', 15),
        'from_email' => env('BREVO_FROM_EMAIL', env('MAIL_FROM_ADDRESS', 'noreply@example.com')),
        'from_name' => env('BREVO_FROM_NAME', env('APP_NAME', 'HireSmart AI')),
    ],

];
