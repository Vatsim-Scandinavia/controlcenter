<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

}
