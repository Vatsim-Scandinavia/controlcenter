<?php

namespace App\Http\Controllers;

use App\Helpers\TaskStatus;
use App\Models\Area;
use App\Models\Task;
use App\Models\User;
use App\Rules\ValidTaskType;
use App\Services\TaskService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Show the application task dashboard.
     */
    public function index(Authenticatable $user, ?string $activeFilter = null): View
    {
        $this->authorize('update', Task::class);

        if ($activeFilter == 'sent') {
            $tasks = Task::where('creator_user_id', $user->id)->get()->sortByDesc('created_at');
        } elseif ($activeFilter == 'archived') {
            $tasks = Task::where('assignee_user_id', $user->id)->whereIn('status', [TaskStatus::COMPLETED->value, TaskStatus::DECLINED->value])->get()->sortByDesc('closed_at');
        } else {
            $tasks = Task::where('assignee_user_id', $user->id)->where('status', TaskStatus::PENDING->value)->with('creator', 'subject', 'assignee', 'subjectTraining')->get()->sortBy('created_at');
        }

        return view('tasks.index', compact('tasks', 'activeFilter'));
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(Request $request, Authenticatable $user): RedirectResponse
    {

        $this->authorize('create', Task::class);

        $data = $request->validate([
            'type' => ['required', new ValidTaskType],
            'message' => 'sometimes|min:3|max:256',
            'subject_user_id' => 'required|exists:users,id',
            'subject_training_id' => 'required|exists:trainings,id',
            'subject_training_rating_id' => 'nullable|exists:ratings,id',
            'assignee_user_id' => 'required|exists:users,id',
        ]);

        $data['creator_user_id'] = $user->id;

        // Check if recipient is mentor or above
        $recipient = User::findOrFail($data['assignee_user_id']);

        // Policy check if recpient can recieve a task
        if ($recipient->can('receive', Task::class)) {
            app(TaskService::class)->createTask($data);

            return redirect()->back()->with('success', 'Task created successfully.');
        }

        return redirect()->back()->withErrors('Recipient is not allowed to receive tasks.');

    }

    /**
     * Complete the specified task.
     */
    public function complete(Request $request, Task|int $task): RedirectResponse
    {
        $this->authorize('update', Task::class);
        $task = Task::findOrFail($task);

        $error = app(TaskService::class)->completeTask($task);
        if (isset($error)) {
            return redirect()->back()->withErrors($error);
        }

        return redirect()->back()->with('success', sprintf('Completed task regarding %s from %s.', $task->subject->name, $task->creator->name));
    }

    /**
     * Decline the specified task.
     */
    public function decline(Request $request, Task|int $task): RedirectResponse
    {
        $this->authorize('update', Task::class);
        $task = Task::findOrFail($task);

        $error = app(TaskService::class)->declineTask($task);
        if (isset($error)) {
            return redirect()->back()->withErrors($error);
        }

        return redirect()->back()->with('success', sprintf('Declined task regarding %s from %s.', $task->subject->name, $task->creator->name));
    }

    /**
     * Return the task type classes
     *
     * @return array
     */
    public static function getTypes()
    {
        return app(TaskService::class)->getTypes();
    }

    /**
     * Get popular task Assignees
     *
     * @return Illuminate\Database\Eloquent\Collection;
     */
    public static function getPopularAssignees(Area $area)
    {
        $users = User::whereHas('tasks', function ($query) use ($area) {
            $query->whereHas('subjectTraining', function ($query) use ($area) {
                $query->where('area_id', $area->id);
            });
        })->withCount('tasks')->orderBy('tasks_count', 'desc')->limit(10)->get();

        // Filter out users who no longer can receive tasks and end up with 3
        $users = $users->filter(function ($user) {
            return $user->hasPermission('tasks.suggested-recipient');
        })->take(3);

        return $users;
    }

    /**
     * Check if a task type is valid
     *
     * @param  string  $type
     * @return bool
     */
    public static function isValidType($type)
    {
        return app(TaskService::class)->isValidType($type);
    }
}
