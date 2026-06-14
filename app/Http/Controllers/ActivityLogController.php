<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLevel;
use App\Models\ActivityLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
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
