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

    'midtrans' => [
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    ],

    'reverb' => [
        'app_id' => env('REVERB_APP_ID'),
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'host' => env('REVERB_HOST'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'https'),
    ],

    /*
    | Multi-content portal APIs (anime / comic / short-drama).
    | Live-proxied per request and cached in the app cache — nothing is imported to the DB.
    */
    'manco' => [
        // Sanka Vollerei — backs both anime (Otakudesu) and comic (Komiku). No key required.
        'sanka_base'  => env('SANKA_BASE', 'https://www.sankavollerei.web.id'),
        // Dracin / Anichin short-drama. Requires a paid X-API-Key from @Anichin_Premium_Bot.
        'dracin_base' => env('DRACIN_BASE', 'https://api.anichin.bio'),
        'dracin_key'  => env('DRACIN_API_KEY', ''),
        // Browser UA required by the scraper APIs (they 403 non-browser agents).
        'scraper_ua'  => env('SCRAPER_UA', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'),
        // Film: TMDB (metadata) + self-hosted TMDB-Embed-API (stream resolution).
        'tmdb_key'    => env('TMDB_API_KEY', ''),
        'embed_base'  => env('TMDB_EMBED_BASE', 'http://127.0.0.1:8787'),
        // OpenSubtitles (film subtitles). Free API key from opensubtitles.com → API Consumer.
        'opensubtitles_key' => env('OPENSUBTITLES_API_KEY', ''),
        // manco-rust sync engine (comic page resolver). Port dibuat konfigurabel karena 8000
        // sudah dipakai app lain di server ini; default ke 8101 (lihat systemd mancosync-rust).
        'rust_base' => env('MANCO_RUST_BASE', 'http://127.0.0.1:8101'),
    ],

];
