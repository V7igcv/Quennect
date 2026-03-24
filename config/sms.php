<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Driver
    |--------------------------------------------------------------------------
    |
    | Supported drivers: "null", "smsapiph", "twilio", "semaphore"
    | The "null" driver is safe for local development because it performs
    | no outbound HTTP requests.
    |
    */

    'driver' => env('SMS_DRIVER', 'null'),

    'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '+63'),

    'smsapiph' => [
        'base_url' => env('SMSAPIPH_BASE_URL', 'https://smsapi.ph/api'),
        'endpoint' => env('SMSAPIPH_ENDPOINT', '/sms/send'),
        'timeout' => (int) env('SMSAPIPH_TIMEOUT', 10),
        'api_key' => env('SMSAPIPH_API_KEY'),
        'sender' => env('SMSAPIPH_SENDER'),
    ],

    'semaphore' => [
        'base_url' => env('SEMAPHORE_BASE_URL', 'https://api.semaphore.co/api/v4'),
        'api_key' => env('SEMAPHORE_API_KEY'),
        'sender' => env('SEMAPHORE_SENDER'),
    ],

    'twilio' => [
        'base_url' => env('TWILIO_BASE_URL', 'https://api.twilio.com/2010-04-01'),
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from_number' => env('TWILIO_FROM_NUMBER'),
        'timeout' => (int) env('TWILIO_TIMEOUT', 10),
    ],
];
