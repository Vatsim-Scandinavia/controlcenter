<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\Task;

class TaskController extends Controller
{

    /**
     * 
     * Show the application task dashboard.
     * @return \Illuminate\Http\Response
     * 
     */
    public function index()
    {
        $user = auth()->user();
        $tasks = Task::where('recipient_user_id', $user->id)->get()->sortBy('created_at');

        return view('tasks.index', compact('tasks'));
    }

    public static function getTypes(){
        // Specify the directory where your subclasses are located
        $subclassesDirectory = app_path('Tasks/Types');

        // Initialize an array to store the subclasses
        $subclasses = [];

        // Get all PHP files in the directory
        $files = File::files($subclassesDirectory);

        foreach ($files as $file) {
            // Get the class name from the file path
            $className = 'App\\Tasks\\Types\\' . pathinfo($file, PATHINFO_FILENAME);;

            // Check if the class exists and is a subclass of Types
            if (class_exists($className) && is_subclass_of($className, 'App\\Tasks\\Types\\Types')) {
                $subclasses[] = new $className();
            }
        }

        return $subclasses;
    }

}
