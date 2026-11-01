<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;
use Illuminate\Support\Facades\Route;

/**
 * Fixture whose web callback resolves a core route name. If staging hid the
 * existing routes, route('login') would throw and the module would be rejected.
 */
class CoreLookupModule implements Module
{
    public const string SLUG = 'corelookup';

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(slug: self::SLUG, name: 'Core Lookup', version: '1.0.0', ccVersion: '*');
    }

    public function register(Registrar $r): void
    {
        $r->permission('modules.corelookup.view', 'View')
            ->web(function (): void {
                Route::redirect('/core-login', route('login'))->name('go');
            });
    }
}
