<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLevel;
use App\Models\ActivityLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
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
     * Display a paginated, filterable listing of the activity log.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): View
    {
        $this->authorize('index', ActivityLog::class);

        $logs = ActivityLog::with('causer')
            ->when($request->query('log_name'), fn ($query, $logName) => $query->where('log_name', $logName))
            ->when($request->query('level'), fn ($query, $level) => $query->where('level', $level))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $categories = ['access', 'training', 'booking', 'endorsement', 'other'];
        $levels = array_map(fn (ActivityLevel $level) => $level->value, ActivityLevel::cases());

        return view('admin.logs', compact('logs', 'categories', 'levels'));
    }
}
