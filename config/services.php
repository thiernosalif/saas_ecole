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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Paiement en ligne (module optionnel `paiement_mobile_money`, cf.
    // PROJET_LARAVEL.md §2.6 et §15.1) — jamais requis pour le chemin espèces.
    'wave' => [
        'api_key' => env('WAVE_API_KEY'),
        'api_secret' => env('WAVE_API_SECRET'),
        'webhook_secret' => env('WAVE_WEBHOOK_SECRET'),
        'base_url' => env('WAVE_BASE_URL', 'https://api.wave.com'),
    ],

    'orange_money' => [
        'client_id' => env('ORANGE_MONEY_CLIENT_ID'),
        'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET'),
        'merchant_key' => env('ORANGE_MONEY_MERCHANT_KEY'),
        'webhook_secret' => env('ORANGE_MONEY_WEBHOOK_SECRET'),
        'base_url' => env('ORANGE_MONEY_BASE_URL', 'https://api.orange.com/orange-money-webpay/sn/v1'),
    ],

];
