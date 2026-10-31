<?php

use ControlCenter\Modules\Moodle\MoodleModule;
use ControlCenter\Modules\Vateud\VateudModule;

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
    | First-party modules that ship inside the standard image. They are
    | discovered in addition to any third-party `controlcenter-module` Composer
    | packages. List the class implementing ControlCenter\ModuleApi\Module.
    |
    */
    'register' => array_filter([
        // First-party modules bundled in the standard image. Guarded with
        // class_exists so the list stays valid before the packages are
        // installed (e.g. in a minimal test image).
        class_exists(MoodleModule::class) ? MoodleModule::class : null,
        class_exists(VateudModule::class) ? VateudModule::class : null,
    ]),

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
