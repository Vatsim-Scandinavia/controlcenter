<?php

namespace App\Notifications;

use App\Mail\TaskMail;
use App\Models\User;
use App\Helpers\TaskStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskNotification extends Notification
{
    use Queueable;

    private $user;

    private $receivedTasks;

    private $updatedTasks;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, $receivedTasks, $updatedTasks)
    {
        $this->user = $user;
        $this->receivedTasks = $receivedTasks;
        $this->updatedTasks = $updatedTasks;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {

        $textLines = [];
        $textLines[] = "Here is an update for some of your tasks.";


        if($this->receivedTasks->count()){
            $textLines[] = '## New tasks';

            foreach($this->receivedTasks as $task){
                $textLines[] = "- **" . $task->type()->getName() . "** from " . User::find($task->sender_user_id)->name . " (" . $task->sender_user_id . ")";
            }
            
        }

        if($this->updatedTasks->count()){
            $textLines[] = '## Updated tasks';

            foreach($this->updatedTasks as $task){
                $textLines[] = "- **" . $task->type()->getName() . "** is now " . Str::lower(TaskStatus::from($task->status)->name);
            }
        }

        return (new TaskMail('Task Digest', $this->user, $textLines))
            ->to($this->user->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [];
    }
}
