<?php

namespace Tests\Fixtures\Modules;

use App\Contracts\DivisionApiContract;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;

/** Fixture registering a division driver named 'shared'. */
class DriverModuleA implements Module
{
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(slug: 'drivera', name: 'Driver A', version: '1.0.0', ccVersion: '*');
    }

    public function register(Registrar $r): void
    {
        $r->driver(DivisionApiContract::class, 'shared', fn () => new NoOpAdapter);
    }
}
