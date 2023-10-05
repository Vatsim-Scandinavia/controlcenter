<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Helpers\TaskStatus;
use App\Notifications\TaskNotification;
use Illuminate\Console\Command;
use App\Notifications\TrainingInterestNotification;

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
        $pendingTasks = Task::where('status', TaskStatus::PENDING->value)->where('recipient_notified', false)->get();

        // For senders who have not yet been notified
        $completedTasks = Task::where('status', TaskStatus::COMPLETED->value)->where('sender_notified', false)->get();
        $declinedTasks = Task::where('status', TaskStatus::DECLINED->value)->where('sender_notified', false)->get();

        // Put together the list of email recipients
        $tasks = $pendingTasks->merge($completedTasks)->merge($declinedTasks);
        $usersRecipients = $pendingTasks->pluck('recipient_user_id')->merge($completedTasks->pluck('sender_user_id'))->merge($declinedTasks->pluck('sender_user_id'))->unique();
        $userModels = User::whereIn('id', $usersRecipients)->get();

        foreach($userModels as $user){

            // If no tasks for this user, skip
            if(!$tasks->where('recipient_user_id', $user->id)->count() && ! $tasks->where('sender_user_id', $user->id)->count()){
                continue;
            }

            // If user has disabled task notifications, mark as notified and skip
            if(!$user->setting_notify_tasks){
                
                $tasks->where('recipient_user_id', $user->id)->each(function($task){
                    $task->recipient_notified = true;
                    $task->save();
                });

                $tasks->where('sender_user_id', $user->id)->each(function($task){
                    $task->sender_notified = true;
                    $task->save();
                });

                continue;
            }

            $user->notify(new TaskNotification(
                $user,
                $tasks->where('recipient_user_id', $user->id),
                $tasks->where('sender_user_id', $user->id)->whereIn('status', [TaskStatus::COMPLETED->value, TaskStatus::DECLINED->value]),
            ));
        }

        return Command::SUCCESS;
    }
}
