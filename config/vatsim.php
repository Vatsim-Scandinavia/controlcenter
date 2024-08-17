<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VATSIM Core API
    |
    | This is the configuration for the VATSIM Core API integration.
    | This is used to configure the secrets.
    |--------------------------------------------------------------------------
    |
    */

    'core_api_token' => env('VATSIM_CORE_API_TOKEN', null),

    /*
    |--------------------------------------------------------------------------
    | VATSIM ATC Bookings API Integration
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the VATSIM ATC Bookings API integration.
    | This is used to configure the secrets and endpoints.
    |
    */

    'booking_api_url' => env('VATSIM_BOOKING_API_URL', 'https://atc-bookings.vatsim.net/api'),
    'booking_api_token' => env('VATSIM_BOOKING_API_TOKEN', null),

    /*
    |--------------------------------------------------------------------------
    | Divisional API Integration
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the divisional API integration.
    | This is used to configure the secrets and endpoints.
    |
    */

    'division_api_driver' => env('VATSIM_DIVISION_API_DRIVER'),
    'division_api_url' => env('VATSIM_DIVISION_API_URL'),
    'division_api_token' => env('VATSIM_DIVISION_API_TOKEN'),

];
