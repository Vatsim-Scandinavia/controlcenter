<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller for mentor's overview of their students, not the report.
 */
class MentorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        $user = Auth::user();
        $trainings = $user->mentoringTrainings();
        $types = TrainingController::$types;
        if ($user->hasPermission('training.mentor-dashboard.view')) {
            return view('mentor.index', compact('trainings', 'user', 'types'));
        }

        abort(403);
    }
}
