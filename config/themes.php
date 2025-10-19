<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default UI Theme
    |--------------------------------------------------------------------------
    |
    | The fallback theme that is applied when a user has not selected a
    | preference or the preference has been removed. This should match one of
    | the keys within the "options" array below.
    |
    */
    'default' => 'light',

    /*
    |--------------------------------------------------------------------------
    | Registered Themes
    |--------------------------------------------------------------------------
    |
    | Define the UI themes that can be selected by end users. Each entry should
    | contain a human readable label and, optionally, a short description that
    | can be surfaced in the UI. Add custom themes here and provide the
    | corresponding styles within your front-end assets.
    |
    */
    'options' => [
        'light' => [
            'label' => 'Light',
            'description' => 'Balanced theme optimised for bright environments.',
            'meta_color' => '#ffffff',
        ],
        'dark' => [
            'label' => 'Dark',
            'description' => 'Dimmed theme for low-light viewing.',
            'meta_color' => '#0f172a',
        ],
    ],
];
