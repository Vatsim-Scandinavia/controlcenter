<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;
use RuntimeException;

/**
 * Fixture module whose manifest() throws. Such a module is installed on disk
 * but cannot be turned into a discovered module, so it must be recorded as a
 * discovery failure and block any operation (e.g. user:delete) that needs every
 * installed package accounted for.
 */
class ThrowingManifestModule implements Module
{
    public function manifest(): ModuleManifest
    {
        throw new RuntimeException('manifest exploded');
    }

    public function register(Registrar $r): void {}
}
