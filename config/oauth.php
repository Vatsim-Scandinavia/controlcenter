<?php

return [

    /*
     * The location of the VATSIM OAuth interface
     */
    'base' => env('OAUTH_URL', 'https://auth.vatsim.net'),

    /*
     * The consumer key for your organisation
     */
    'id' => env('OAUTH_ID'),

    /*
     * The secret key for your organisation
     * Do not give this to anyone else or display it to your users. It must be kept server-side
     */
    'secret' => env('OAUTH_SECRET'),

    /**
     * The scopes the user will be requested
     */
    'scopes' => explode(',', env('OAUTH_SCOPES', 'full_name,email,vatsim_details')),

    /*
     * OAuth variable mapping
     */
    'mapping_cid' => env('OAUTH_MAPPING_CID', 'data-cid'),
    'mapping_mail' => env('OAUTH_MAPPING_EMAIL', 'data-personal-email'),

    'mapping_first_name' => env('OAUTH_MAPPING_FIRSTNAME', 'data-personal-name_first'),
    'mapping_last_name' => env('OAUTH_MAPPING_LASTNAME', 'data-personal-name_last'),

    'mapping_rating' => env('OAUTH_MAPPING_RATING', 'data-vatsim-rating-id'),
    'mapping_rating_short' => env('OAUTH_MAPPING_RATING_SHORT', 'data-vatsim-rating-short'),
    'mapping_rating_long' => env('OAUTH_MAPPING_RATING_LONG', 'data-vatsim-rating-long'),

    'mapping_region' => env('OAUTH_MAPPING_REGION', 'data-vatsim-region-id'),
    'mapping_division' => env('OAUTH_MAPPING_DIVISION', 'data-vatsim-division-id'),
    'mapping_subdivision' => env('OAUTH_MAPPING_SUBDIVISION', 'data-vatsim-subdivision-id'),

];
