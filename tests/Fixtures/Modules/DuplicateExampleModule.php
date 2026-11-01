<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;

/**
 * Fixture module that deliberately reuses the 'example' slug, to exercise
 * duplicate-slug handling: the shadowed candidate must not vanish silently but
 * be recorded as a discovery failure.
 */
class DuplicateExampleModule implements Module
{
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: 'example',
            name: 'Duplicate Example',
            version: '1.0.0',
            ccVersion: '*',
        );
    }

    public function register(Registrar $r): void {}
}
