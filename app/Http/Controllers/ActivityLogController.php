<?php

namespace App\Http\Controllers;

use App\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{

    private static function log($type, $message){
        $log = new ActivityLog();

        $log->type = $type;
        $log->message = $message;
        
        $request = request();

        if(isset($request)){
            $log->user_id = $request->user()->id;
            $log->ip_address = $request->ip();
            $log->user_agent = $request->userAgent();
        }
        
        $log->created_at = now();
        $log->save();
    }

    public static function debug($message){
        ActivityLogController::log("DEBUG", $message);
    }

    public static function info($message){
        ActivityLogController::log("INFO", $message);
    }

    public static function warning($message){
        ActivityLogController::log("WARNING", $message);
    }

    public static function danger($message){
        ActivityLogController::log("DANGER", $message);
    }
}
