<?php

namespace App\Services;

use App\Helpers\ActivityLevel;
use App\Helpers\LogName;
use App\Models\ActivityLog;

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
     * Write a legacy-style log entry, mapping the category onto the activity
     * log_name and the severity onto the level.
     */
    private static function record(ActivityLevel $level, LogName $category, string $message): void
    {
        activity($category)
            ->tap(fn (ActivityLog $log) => $log->level = $level)
            ->log($message);
    }

    public static function debug(LogName $category, string $message): void
    {
        self::record(ActivityLevel::Debug, $category, $message);
    }

    public static function info(LogName $category, string $message): void
    {
        self::record(ActivityLevel::Info, $category, $message);
    }

    public static function warning(LogName $category, string $message): void
    {
        self::record(ActivityLevel::Warning, $category, $message);
    }

    public static function danger(LogName $category, string $message): void
    {
        self::record(ActivityLevel::Danger, $category, $message);
    }
}
