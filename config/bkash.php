<?php
return [
    'base_url' => env('BKASH_BASE_URL', 'https://tokenized.pay.bka.sh/v1.2.0-beta'),
    'username' => env('BKASH_USERNAME', ''),
    'password' => env('BKASH_PASSWORD', ''),
    'app_key' => env('BKASH_APP_KEY', ''),
    'app_secret' => env('BKASH_APP_SECRET', ''),
    'debug' => env('BKASH_DEBUG', false),
];
