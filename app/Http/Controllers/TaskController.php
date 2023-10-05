<?php

namespace App\Http\Controllers;

use App\Helpers\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\Task;
use App\Rules\ValidTaskType;

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
        $tasks = Task::where('recipient_user_id', $user->id)->where('status', TaskStatus::PENDING->value)->get()->sortBy('created_at');

        return view('tasks.index', compact('tasks'));
    }

    /**
     * 
     * Store a newly created task in storage.
     * @param  \Illuminate\Http\Request  $request
     * 
     */
    public function store(Request $request){

        $data = $request->validate([
            'type' => ['required', new ValidTaskType],
            'message' => 'sometimes|min:3|max:256',
            'reference_user_id' => 'required|exists:users,id',
            'reference_training_id' => 'required|exists:trainings,id',
            'recipient_user_id' => 'required|exists:users,id',
        ]);

        $data['sender_user_id'] = auth()->user()->id;
        $data['created_at'] = now();

        // Create the model
        $task = Task::create($data);

        // Run the create method on the task type to trigger type specific actions on creation
        $task->type()->create($task);

        return redirect()->back()->with('success', 'Task created successfully.');
    }
    
    /**
     * 
     * Complete the specified task
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     */
    public function complete(Request $request, int $task){

        $task = Task::findOrFail($task);

        $task->status = TaskStatus::COMPLETED->value;
        $task->closed_at = now();
        $task->save();

        // Run the complete method on the task type to trigger type specific actions on completion
        $task->type()->complete($task);

        return redirect()->back();
    }

    /** 
     * 
     * Return the task type classes
     * @return array
     */
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

    /**
     * 
     * Check if a task type is valid
     * @param string $type
     * @return bool
     * 
     */
    public static function isValidType($type){
        $types = self::getTypes();

        foreach($types as $taskType){
            if($taskType::class == $type){
                return true;
            }
        }

        return false;
    }

}
