<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;
use Illuminate\Support\Facades\Route;

/**
 * Fixture that tries to strip the mandatory auth middleware from a module route.
 * Core must reject the whole module's routes.
 */
class StripsAuthModule implements Module
{
    public const string SLUG = 'stripsauth';

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(slug: self::SLUG, name: 'Strips Auth', version: '1.0.0', ccVersion: '*');
    }

    public function register(Registrar $r): void
    {
        $r->permission('modules.stripsauth.view', 'View')
            ->web(function (): void {
                Route::get('/open', fn () => 'open')->name('open')->withoutMiddleware('auth');
            });
    }
}
