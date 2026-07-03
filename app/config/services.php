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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'lytex' => [
        'base_url' => env('LYTEX_BASE_URL', 'https://api-pay.lytex.com.br'),
        'client_id' => env('LYTEX_CLIENT_ID'),
        'client_secret' => env('LYTEX_CLIENT_SECRET'),
        'auth_scheme' => env('LYTEX_AUTH_SCHEME', 'Bearer'),
        'timeout' => env('LYTEX_TIMEOUT', 30),
    ],

    'whatsapp' => [
        'zapi' => [
            'base_url' => env('WHATSAPP_ZAPI_BASE_URL', 'https://api.z-api.io'),
            'instance_id' => env('WHATSAPP_ZAPI_INSTANCE_ID'),
            'token' => env('WHATSAPP_ZAPI_TOKEN'),
            'client_token' => env('WHATSAPP_ZAPI_CLIENT_TOKEN'),
            'timeout' => env('WHATSAPP_ZAPI_TIMEOUT', 30),
            'pix_endpoint' => env('WHATSAPP_ZAPI_PIX_ENDPOINT', 'send-button-pix'),
        ],
    ],

];
