<?php

namespace App\Support\ActivityLog;

use App\Helpers\LogName;
use App\Modules\ModuleRegistry;

/**
 * Resolves human labels for activity-log categories (`log_name`).
 *
 * Core categories come from the closed {@see LogName} enum; enabled modules
 * contribute additional categories (e.g. `modules.example`) through
 * {@see ModuleRegistry::activityLogNames()}. This keeps the enum closed for the
 * core while letting a module's audit entries render with a proper label rather
 * than a raw, namespaced string.
 */
class LogNameRegistry
{
    /**
     * All known category labels keyed by their stored `log_name`.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (LogName::cases() as $case) {
            $labels[$case->value] = ucfirst($case->value);
        }

        return $labels + app(ModuleRegistry::class)->activityLogNames();
    }

    /**
     * The display label for a stored category, falling back to a humanised form
     * of the raw name for categories no longer (or not yet) registered.
     */
    public static function label(string $name): string
    {
        return self::labels()[$name] ?? ucfirst($name);
    }
}
