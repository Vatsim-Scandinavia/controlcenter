<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;
use Illuminate\Support\Facades\Route;
use RuntimeException;

/**
 * Fixture module whose web route callback registers a route and then throws,
 * exercising the route loader's isolation: a throwing callback must neither
 * break application boot nor leave the module's prefix/middleware bleeding onto
 * routes registered afterwards (Router::group() does not pop its group stack
 * when the callback throws).
 */
class ThrowingRouteModule implements Module
{
    public const string SLUG = 'throwroute';

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: self::SLUG,
            name: 'Throwing Route Module',
            version: '1.0.0',
            ccVersion: '*',
            description: 'Fixture module whose route registration throws.',
            author: 'Control Center',
        );
    }

    public function register(Registrar $r): void
    {
        $r->permission('modules.throwroute.view', 'View')
            ->web(function (): void {
                Route::get('/half', fn () => 'half')->name('half');

                throw new RuntimeException('Boom while registering routes.');
            });
    }
}
