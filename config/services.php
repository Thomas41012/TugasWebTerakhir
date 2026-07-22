<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Postmark
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Amazon SES
    |--------------------------------------------------------------------------
    */

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resend
    |--------------------------------------------------------------------------
    */

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack
    |--------------------------------------------------------------------------
    */

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env(
                'SLACK_BOT_USER_OAUTH_TOKEN'
            ),

            'channel' => env(
                'SLACK_BOT_USER_DEFAULT_CHANNEL'
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Open-Meteo API
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk mengambil data cuaca global.
    |
    */

    'open_meteo' => [
        'url' => env(
            'OPEN_METEO_BASE_URL',
            'https://api.open-meteo.com/v1'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | REST Countries API
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk mengambil profil negara.
    |
    */

    'rest_countries' => [
        'url' => env(
            'REST_COUNTRIES_BASE_URL',
            'https://restcountries.com/v3.1'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | World Bank API
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk mengambil data ekonomi negara.
    |
    */

    'world_bank' => [
        'url' => env(
            'WORLD_BANK_BASE_URL',
            'https://api.worldbank.org/v2'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate API
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk mengambil nilai tukar mata uang.
    |
    */

    'exchange_rate' => [
        'url' => env(
            'EXCHANGE_RATE_BASE_URL',
            'https://open.er-api.com/v6'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | GNews API
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk mengambil berita global terkait:
    |
    | - Supply Chain
    | - Logistics
    | - Shipping
    | - Economy
    | - Trade
    | - Geopolitical Risk
    |
    */

    'gnews' => [
        'url' => env(
            'GNEWS_BASE_URL',
            'https://gnews.io/api/v4'
        ),

        'key' => env('GNEWS_API_KEY'),
    ],

];