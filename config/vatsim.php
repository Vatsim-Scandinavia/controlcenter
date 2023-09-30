<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VATSIM Booking API
    |--------------------------------------------------------------------------
    |
    */

    'api_token' => env('VATSIM_API_TOKEN', null),

    'booking_api_url' => env('VATSIM_BOOKING_API_URL', 'https://atc-bookings.vatsim.net/api'),
    'booking_api_token' => env('VATSIM_BOOKING_API_TOKEN', null),

];
