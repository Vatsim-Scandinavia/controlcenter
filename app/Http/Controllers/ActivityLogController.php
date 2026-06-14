<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLevel;
use App\Models\ActivityLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Displays the activity log and provides backwards-compatible logging shims.
 *
 * The static debug/info/warning/danger methods are thin wrappers over the
 * activity() helper, kept so the ~28 existing call sites keep working while they
 * are migrated to the new API. They are removed once migration is complete.
 */
class ActivityLogController extends Controller
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

    /**
     * Display a listing of the logs to the view.
     *
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('index', ActivityLog::class);
        $logs = ActivityLog::where('category', '!=', null)->with('user')->get()->sortByDesc('created_at');

        return view('admin.logs', compact('logs'));
    }
}
