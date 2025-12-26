<?php
// config/hardware.php

return [
    /*
    |--------------------------------------------------------------------------
    | Hardware API Key
    |--------------------------------------------------------------------------
    |
    | This key is used to authenticate ESP32 hardware devices.
    | Make sure this matches the key in your ESP32 code.
    |
    */
    'api_key' => env('HARDWARE_API_KEY', 'default-key-change-me'),

    /*
    |--------------------------------------------------------------------------
    | Hardware Throttle Settings
    |--------------------------------------------------------------------------
    |
    | Rate limiting for hardware devices to prevent abuse.
    |
    */
    'throttle' => [
        'max_attempts' => 30,
        'decay_minutes' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Device UID
    |--------------------------------------------------------------------------
    |
    | Default UID for testing purposes.
    |
    */
    'default_device_uid' => '911',
];
