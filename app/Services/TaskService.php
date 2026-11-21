<?php

namespace App\Services;

use App\Helpers\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Tasks\Types\Types;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

/**
 * The service layer for tasks.
 *
 * Task creation, completion and declining live here so the web UI and the
 * management API drive the same behaviour.
 */
class TaskService
{
    /**
     * The task type classes, one instance of each.
     *
     * @return array<int, Types>
     */
    public function getTypes(): array
    {
        $subclasses = [];

        foreach (File::files(app_path('Tasks/Types')) as $file) {
            $className = 'App\\Tasks\\Types\\' . pathinfo($file, PATHINFO_FILENAME);

            // Check if the class exists and is a subclass of Types
            if (class_exists($className) && is_subclass_of($className, Types::class)) {
                $subclasses[] = new $className();
            }
        }

        return $subclasses;
    }

    /**
     * Whether the given class name is one of the task types.
     */
    public function isValidType(?string $type): bool
    {
        foreach ($this->getTypes() as $taskType) {
            if ($taskType::class == $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the user is allowed to be assigned tasks.
     */
    public function canReceiveTasks(User $user): bool
    {
        return $user->hasPermission('tasks.manage') || $user->isExaminer();
    }

    /**
     * Create a task and run the type specific creation hook.
     *
     * The caller is responsible for checking that the assignee can receive tasks.
     *
     * @param  array<string, mixed>  $data  task columns, without created_at
     */
    public function createTask(array $data): Task
    {
        $task = Task::create($data + ['created_at' => now()]);

        // Run the create method on the task type to trigger type specific actions on creation
        $task->type()->create($task);

        return $task;
    }

    /**
     * Complete a task, running the type specific completion work.
     *
     * Returns the type's error string on failure (the task stays pending), or null on
     * success.
     */
    public function completeTask(Task $task, ?int $actorId = null): ?string
    {
        $error = $this->actingAs($actorId, fn () => $task->type()->complete($task));
        if (isset($error)) {
            return $error;
        }

        $this->closeTask($task, TaskStatus::COMPLETED);

        return null;
    }

    /**
     * Decline a task, running the type specific decline work.
     *
     * Returns the type's error string on failure (the task stays pending), or null on
     * success.
     */
    public function declineTask(Task $task, ?int $actorId = null): ?string
    {
        $error = $this->actingAs($actorId, fn () => $task->type()->decline($task));
        if (isset($error)) {
            return $error;
        }

        $this->closeTask($task, TaskStatus::DECLINED);

        return null;
    }

    /**
     * Close the task with the given status.
     */
    private function closeTask(Task $task, TaskStatus $status): void
    {
        $task->status = $status;
        $task->assignee_notified = true;
        $task->closed_at = now();
        $task->save();
    }

    /**
     * Run a callback as the given user when nobody is signed in.
     *
     * Task types read the signed-in user as the Division API requester. Without a
     * session, such as on the management API, the actor stands in for that user.
     */
    private function actingAs(?int $actorId, callable $callback): mixed
    {
        if ($actorId === null || Auth::check()) {
            return $callback();
        }

        $actor = User::find($actorId);
        if ($actor === null) {
            return $callback();
        }

        Auth::setUser($actor);

        try {
            return $callback();
        } finally {
            Auth::forgetUser();
        }
    }
}
