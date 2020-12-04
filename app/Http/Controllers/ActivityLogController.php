<?php

namespace App\Http\Controllers;

use App\ActivityLog;
use Illuminate\Http\Request;

/**
 * This controller logs various activity and stores it in database for logging purposes.
 */
class ActivityLogController extends Controller
{

    /**
     * Internal function to save the log according to type
     * 
     * @param string $type
     * @param string $message
     * @return void
     */
    private static function log($type, $message){
        $log = new ActivityLog();

        $log->type = $type;
        $log->message = $message;
        
        $request = request();

        if(isset($request)){

            if(isset($request->user()->id)){
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
     * @param string $message
     * @return void
     */
    public static function debug($message){
        ActivityLogController::log("DEBUG", $message);
    }

    /**
     * Store a info log
     * 
     * @param string $message
     * @return void
     */
    public static function info($message){
        ActivityLogController::log("INFO", $message);
    }

    /**
     * Store a warning log
     * 
     * @param string $message
     * @return void
     */
    public static function warning($message){
        ActivityLogController::log("WARNING", $message);
    }

    /**
     * Store a danger log
     * 
     * @param string $message
     * @return void
     */
    public static function danger($message){
        ActivityLogController::log("DANGER", $message);
    }
}
