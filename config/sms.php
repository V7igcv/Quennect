<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Driver
    |--------------------------------------------------------------------------
    |
    | Supported drivers: "null", "semaphore"
    | The "null" driver is safe for local development because it performs
    | no outbound HTTP requests.
    |
    */

    'driver' => env('SMS_DRIVER', 'semaphore'),

    'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '+63'),

    'semaphore' => [
        'base_url' => env('SEMAPHORE_BASE_URL', 'https://api.semaphore.co/api/v4'),
        'endpoint' => env('SEMAPHORE_ENDPOINT', '/messages'),
        'timeout' => (int) env('SEMAPHORE_TIMEOUT', 10),
        'api_key' => env('SEMAPHORE_API_KEY'),
        'sender' => env('SEMAPHORE_SENDER'),
    ],
];
