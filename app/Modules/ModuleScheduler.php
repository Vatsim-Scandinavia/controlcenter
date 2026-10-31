<?php

namespace App\Modules;

use Illuminate\Console\Scheduling\Schedule;

/**
 * Applies the scheduled tasks registered by booted (enabled and compatible)
 * modules to the application's schedule.
 *
 * Only {@see ModuleRegistry::booted()} registrars are consulted, so a disabled
 * module contributes no scheduled work. Invoked from the console kernel after
 * the core schedule has been defined.
 */
class ModuleScheduler
{
    public static function schedule(Schedule $schedule, ModuleRegistry $registry): void
    {
        foreach ($registry->booted() as $registrar) {
            foreach ($registrar->schedules as $callback) {
                $callback($schedule);
            }
        }
    }
}
