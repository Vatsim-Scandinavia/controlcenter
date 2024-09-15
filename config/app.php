<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Control Center'),

    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    |
    | This is the value of the SIMVER of the application.
    | Needs to be updated manually for each iteration we do.
    |
    */
    /* x-release-please-start-version */
    'version' => '6.0.0',
    /* x-release-please-end */

    /*
    |--------------------------------------------------------------------------
    | Application Owner
    |--------------------------------------------------------------------------
    |
    | This value is the owner of your application. This value is used when the
    | framework needs to place the application's owner in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'owner_name' => env('APP_OWNER_NAME', 'Subdivision name'),

    /*
    |--------------------------------------------------------------------------
    | Short name of application owner
    |--------------------------------------------------------------------------
    |
    | Same as above, but the shortened name. Mostly used in more compact views.
    |
    */

    'owner_name_short' => env('APP_OWNER_NAME_SHORT', 'UNKNOWN'),

    /*
    |--------------------------------------------------------------------------
    | Application Owner Code
    |--------------------------------------------------------------------------
    |
    | 3-4 letter name identifying the entity in VATSIM API.
    | For example 'SCA', NOT 'VATSCA'.
    |
    */

    'owner_code' => env('APP_OWNER_CODE', 'UNK'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Logos
    |--------------------------------------------------------------------------
    |
    */

    'logo' => env('APP_LOGO', 'vatsca.svg'),
    'logo_mail' => env('APP_LOGO_MAIL', 'vatsca-email.png'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_GB',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Tracking script
    |--------------------------------------------------------------------------
    |
    | Adding a config where we use the provided tracking script to the header
    |
    */

    'tracking_script' => env('APP_TRACKING_SCRIPT'),

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

        /*
        * Custom providers
        */

        App\Providers\CarbonServiceProvider::class,
        App\Providers\DivisionApiServiceProvider::class,

    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Network & Requests
    |--------------------------------------------------------------------------
    | Configure the list of proxies that you trust if you are running Control
    | Center behind a proxy such as nginx, traefik or similarly.
    | Separate allowed proxies with a comma (no space!).
    | If you're running Control Center in a container, you may set it to '*'.
    */
    'proxies' => [
        'trusted' => env('TRUSTED_PROXIES'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),

];
