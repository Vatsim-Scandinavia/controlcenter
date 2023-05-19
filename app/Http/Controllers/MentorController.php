<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * Controller for mentor's overview of their students, not the report.
 */
class MentorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $trainings = $user->mentoringTrainings();
        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;
        if ($user->isMentorOrAbove()) {
            return view('mentor.index', compact('trainings', 'user', 'statuses', 'types'));
        }

        abort(403);
    }
}
