<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;
use Illuminate\Support\Facades\Route;

/**
 * Fixture with an API route that does NOT declare api-token itself. The api()
 * group must still require a key, so the route is not public by default.
 */
class UntokenedApiModule implements Module
{
    public const string SLUG = 'untokened';

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(slug: self::SLUG, name: 'Untokened API', version: '1.0.0', ccVersion: '*');
    }

    public function register(Registrar $r): void
    {
        $r->permission('modules.untokened.view', 'View')
            ->api(function (): void {
                Route::get('/open', fn () => response()->json(['ok' => true]))->name('open');
            });
    }
}
