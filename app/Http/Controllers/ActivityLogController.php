<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

/**
 * This controller logs various activity and stores it in database for logging purposes.
 */
class ActivityLogController extends Controller
{
    /**
     * Internal function to save the log according to type
     *
     * @param  string  $type
     * @param  string  $message
     * @return void
     */
    private static function log($type, $category, $message)
    {
        $log = new ActivityLog();

        $log->type = $type;
        $log->category = $category;
        $log->message = $message;

        $request = request();

        if (isset($request)) {
            if (isset($request->user()->id)) {
                $log->user_id = $request->user()->id;
            }

            $log->ip_address = $request->ip();
            $log->user_agent = $request->userAgent();
        }

        $log->created_at = now();
        $log->save();
    }

    /**
     * Store a debug log
     *
     * @param  string  $message
     * @return void
     */
    public static function debug($category, $message)
    {
        ActivityLogController::log('DEBUG', $category, $message);
    }

    /**
     * Store a info log
     *
     * @param  string  $message
     * @return void
     */
    public static function info($category, $message)
    {
        ActivityLogController::log('INFO', $category, $message);
    }

    /**
     * Store a warning log
     *
     * @param  string  $message
     * @return void
     */
    public static function warning($category, $message)
    {
        ActivityLogController::log('WARNING', $category, $message);
    }

    /**
     * Store a danger log
     *
     * @param  string  $message
     * @return void
     */
    public static function danger($category, $message)
    {
        ActivityLogController::log('DANGER', $category, $message);
    }

    /**
     * Display a listing of the logs to the view.
     *
     * @param  anlutro\LaravelSettings\Facade  $setting
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', ActivityLog::class);
        $logs = ActivityLog::where('category', '!=', null)->with('user')->get()->sortByDesc('created_at');

        return view('admin.logs', compact('logs'));
    }
}
