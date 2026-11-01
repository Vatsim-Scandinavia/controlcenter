<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;

/**
 * Fixture that schedules a command it never registered via command(). Core must
 * refuse to schedule it.
 */
class UnregisteredScheduleModule implements Module
{
    public const string SLUG = 'unregsched';

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(slug: self::SLUG, name: 'Unregistered Schedule', version: '1.0.0', ccVersion: '*');
    }

    public function register(Registrar $r): void
    {
        // ExampleCommand is NOT registered via command() here.
        $r->schedule(ExampleCommand::class, fn ($event) => $event->daily());
    }
}
