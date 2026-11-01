<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;
use RuntimeException;

/**
 * Fixture module whose register() hook throws, to exercise the registry's
 * failure isolation and Sentry tagging.
 */
class ThrowingModule implements Module
{
    public const string SLUG = 'throwing';

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: self::SLUG,
            name: 'Throwing Module',
            version: '1.0.0',
            ccVersion: '*',
            description: 'Fixture module that fails while registering.',
            author: 'Control Center',
        );
    }

    public function register(Registrar $r): void
    {
        throw new RuntimeException('Boom from throwing module.');
    }
}
