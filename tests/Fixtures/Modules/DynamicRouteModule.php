<?php

namespace Tests\Fixtures\Modules;

use Closure;
use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;

/**
 * Fixture whose register() runs a test-supplied closure, so a single test can
 * exercise an arbitrary route-registration attempt (a route-guarantee bypass).
 */
class DynamicRouteModule implements Module
{
    public static ?Closure $register = null;

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(slug: 'dyn', name: 'Dyn', version: '1.0.0', ccVersion: '*');
    }

    public function register(Registrar $r): void
    {
        if (self::$register !== null) {
            (self::$register)($r);
        }
    }
}
