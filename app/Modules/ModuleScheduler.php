<?php

namespace App\Modules;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Applies the scheduled tasks registered by booted (enabled and compatible)
 * modules to the application's schedule.
 *
 * Only {@see ModuleRegistry::booted()} registrars are consulted, so a disabled
 * module contributes no scheduled work. Invoked from the console kernel after
 * the core schedule has been defined. Each callback is isolated so a throwing
 * module cannot break the rest of the application's schedule.
 */
class ModuleScheduler
{
    public static function schedule(Schedule $schedule, ModuleRegistry $registry): void
    {
        foreach ($registry->booted() as $slug => $registrar) {
            foreach ($registrar->schedules as $entry) {
                $commandClass = $entry['command'];

                try {
                    // A module may only schedule one of its own registered
                    // commands; it never receives the application schedule.
                    if (! in_array($commandClass, $registrar->commands, true)) {
                        throw new \RuntimeException("command [{$commandClass}] is not registered by this module");
                    }

                    // Core creates the scheduled event; the module's closure only
                    // sets timing/constraints on that event.
                    $event = $schedule->command($commandClass);
                    ($entry['timing'])($event);
                } catch (Throwable $e) {
                    Log::error("Module [{$slug}] failed while scheduling: {$e->getMessage()}", ['exception' => $e, 'module' => $slug]);
                    ModuleRegistry::captureToSentry($e, $slug);
                }
            }
        }
    }
}
