<?php

namespace Tests\Fixtures\Modules;

use App\Contracts\DivisionApiContract;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;

/** Fixture registering a division driver with the same name 'shared' as DriverModuleA. */
class DriverModuleB implements Module
{
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(slug: 'driverb', name: 'Driver B', version: '1.0.0', ccVersion: '*');
    }

    public function register(Registrar $r): void
    {
        $r->driver(DivisionApiContract::class, 'shared', fn () => new NoOpAdapter);
    }
}
