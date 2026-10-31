<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;

/**
 * Fixture declaring a Control Center version constraint this instance cannot
 * satisfy, used to prove incompatible modules are never booted.
 */
class IncompatibleFixtureModule implements Module
{
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: 'incompatible',
            name: 'Incompatible Module',
            version: '1.0.0',
            ccVersion: '^99.0',
        );
    }

    public function register(Registrar $r): void
    {
        $r->permission('modules.incompatible.view', 'Never reached');
    }
}
