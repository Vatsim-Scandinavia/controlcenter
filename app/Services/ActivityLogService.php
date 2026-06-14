<?php

namespace App\Services;

use App\Helpers\ActivityLevel;
use App\Models\ActivityLog;
use Illuminate\Support\Str;

/**
 * Backwards-compatible logging shims over the activity() helper.
 *
 * The static debug/info/warning/danger methods are thin wrappers, kept so the
 * ~28 existing call sites keep working while they are migrated to the new API.
 * They are removed once migration is complete.
 */
class ActivityLogService
{
    /**
     * Write a legacy-style log entry, mapping the free-text category onto the
     * activity log_name and the severity onto the level.
     */
    private static function record(ActivityLevel $level, string $category, string $message): void
    {
        activity(Str::lower($category))
            ->tap(fn (ActivityLog $log) => $log->level = $level)
            ->log($message);
    }

    public static function debug(string $category, string $message): void
    {
        self::record(ActivityLevel::Debug, $category, $message);
    }

    public static function info(string $category, string $message): void
    {
        self::record(ActivityLevel::Info, $category, $message);
    }

    public static function warning(string $category, string $message): void
    {
        self::record(ActivityLevel::Warning, $category, $message);
    }

    public static function danger(string $category, string $message): void
    {
        self::record(ActivityLevel::Danger, $category, $message);
    }
}
