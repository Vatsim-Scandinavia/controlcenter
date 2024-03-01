<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VATSIM API
    |--------------------------------------------------------------------------
    |
    */

    'api_token' => env('VATSIM_API_TOKEN', null),

    /*
    |--------------------------------------------------------------------------
    | Booking API Integration
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the booking API integration.
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
