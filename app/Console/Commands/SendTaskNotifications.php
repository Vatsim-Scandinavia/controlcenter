<?php

namespace App\Console\Commands;

use App\Helpers\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskNotification;
use Illuminate\Console\Command;

class SendTaskNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:task:notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send out the digest of task notifications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // For recipients who have not yet been notified
        $pendingTasks = Task::where('status', TaskStatus::PENDING)->where('assignee_notified', false)->get();

        // For senders who have not yet been notified
        $completedTasks = Task::where('status', TaskStatus::COMPLETED)->where('creator_notified', false)->get();
        $declinedTasks = Task::where('status', TaskStatus::DECLINED)->where('creator_notified', false)->get();

        // Put together the list of email recipients
        $tasks = $pendingTasks->merge($completedTasks)->merge($declinedTasks);
        $usersRecipients = $pendingTasks->pluck('assignee_user_id')->merge($completedTasks->pluck('creator_user_id'))->merge($declinedTasks->pluck('creator_user_id'))->unique();
        $userModels = User::whereIn('id', $usersRecipients)->get();

        foreach ($userModels as $user) {

            // If no tasks for this user, skip
            if (! $tasks->where('assignee_user_id', $user->id)->count() && ! $tasks->where('creator_user_id', $user->id)->count()) {
                continue;
            }

            // If user has disabled task notifications, mark as notified and skip
            if (! $user->setting_notify_tasks) {

                $tasks->where('assignee_user_id', $user->id)->each(function ($task) {
                    $task->assignee_notified = true;
                    $task->save();
                });

                $tasks->where('creator_user_id', $user->id)->each(function ($task) {
                    $task->creator_notified = true;
                    $task->save();
                });

                continue;
            }

            $user->notify(new TaskNotification(
                $user,
                $tasks->where('assignee_user_id', $user->id),
                $tasks->where('creator_user_id', $user->id)->whereIn('status', [TaskStatus::COMPLETED, TaskStatus::DECLINED]),
            ));
        }

        return Command::SUCCESS;
    }
}
