<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;
use ControlCenter\ModuleApi\Settings\SettingsSchema;

/**
 * Fixture module that records how many times its register() hook has run, so a
 * test can prove that surfaces which must not activate a module (e.g. the admin
 * settings page for a disabled module) never invoke register().
 */
class RegisterSpyModule implements Module
{
    public const string SLUG = 'registerspy';

    public static int $registerCount = 0;

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: self::SLUG,
            name: 'Register Spy Module',
            version: '1.0.0',
            ccVersion: '*',
            description: 'Fixture module that counts register() invocations.',
            author: 'Control Center',
        );
    }

    public function register(Registrar $r): void
    {
        self::$registerCount++;

        $r->permission('modules.registerspy.view', 'View')
            ->adminSettings((new SettingsSchema)->text('title', 'Title'));
    }
}
