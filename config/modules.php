<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Safe mode
    |--------------------------------------------------------------------------
    |
    | When enabled, no modules are booted regardless of their enabled state in
    | the database. Use this to recover an instance that a misbehaving module
    | has made unusable. Core features keep working.
    |
    */
    'safe_mode' => env('MODULES_SAFE_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Bundled modules
    |--------------------------------------------------------------------------
    |
    | Explicitly registered modules that ship inside an image, discovered in
    | addition to any installed `controlcenter-module` Composer packages. Set
    | via the comma-separated MODULES_REGISTER env var (each the class
    | implementing ControlCenter\ModuleApi\Module), or list classes here.
    |
    */
    'register' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MODULES_REGISTER', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Settings key prefix
    |--------------------------------------------------------------------------
    |
    | Module settings are persisted under this prefix, e.g. `modules.<slug>.<key>`.
    |
    */
    'settings_prefix' => 'modules',
];
