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
            foreach ($registrar->schedules as $callback) {
                try {
                    $callback($schedule);
                } catch (Throwable $e) {
                    Log::error("Module [{$slug}] failed while scheduling: {$e->getMessage()}", ['exception' => $e, 'module' => $slug]);
                }
            }
        }
    }
}
