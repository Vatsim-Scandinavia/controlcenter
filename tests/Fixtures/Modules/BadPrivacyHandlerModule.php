<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;

/**
 * Fixture module whose manifest declares a privacy handler class that does not
 * exist. Its data cannot be cleaned, so it must block user:delete (fail closed)
 * — even while the module is disabled.
 */
class BadPrivacyHandlerModule implements Module
{
    public const string SLUG = 'bad-privacy';

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: self::SLUG,
            name: 'Bad Privacy Handler',
            version: '1.0.0',
            ccVersion: '*',
            privacyHandler: 'Tests\\Fixtures\\Modules\\NoSuchPrivacyHandler',
        );
    }

    public function register(Registrar $r): void {}
}
