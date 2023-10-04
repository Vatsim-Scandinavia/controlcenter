<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;

class TaskController extends Controller
{

    /**
     * A list of possible types
     */
    public static $types = [
        1 => ['text' => 'Theoretical Exam Access', 'icon' => 'fas fa-key'],
        2 => ['text' => 'Solo Endorsement', 'icon' => 'fas fa-clock'],
        3 => ['text' => 'Rating Upgrade', 'icon' => 'fas fa-circle-arrow-up'],
        4 => ['text' => 'Custom Memo', 'icon' => 'fas fa-message'],
    ];

    /**
     * 
     * Show the application task dashboard.
     * @return \Illuminate\Http\Response
     * 
     */
    public function index()
    {
        $user = auth()->user();
        $tasks = Task::where('recipient_user_id', $user->id)->get()->sortByDesc('created_at');
        $taskTypes = self::$types;

        return view('tasks.index', compact('tasks', 'taskTypes'));
    }

}
