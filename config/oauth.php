<?php

return [

    /*
     * The location of the VATSIM OAuth interface
     */
    'base' => env('OAUTH_URL', 'https://handover.vatsim-scandinavia.org'),

    /*
     * The consumer key for your organisation
     */
    'id' => env('OAUTH_ID'),

    /*
     * The secret key for your organisation
     * Do not give this to anyone else or display it to your users. It must be kept server-side
     */
    'secret' => env('OAUTH_SECRET'),

];
