<?php

namespace App\Notifications;

use App\Training;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ContinuedTrainingInterestNotification extends Notification // implements ShouldQueue
{
    use Queueable;

    private $training, $key, $deadline;

    /**
     * Create a new notification instance.
     *
     * @param Training $training
     * @param string $key
     */
    public function __construct(Training $training, string $key)
    {
        $this->training = $training;
        $this->key = $key;

        $this->deadline = now()->addDays(14);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {

        $this->addToDB();

        $mailMessage = (new MailMessage)
            ->subject('Confirm your continued interest in your training')
            ->greeting('Dear ' . $this->training->user->first_name . ',')
            ->line('Please confirm your continued interest in your training for the following ratings:');

        foreach ($this->training->ratings as $rating) {
            $mailMessage->line(' - ' . $rating->name);
        }

        $mailMessage->action('Confirm interest', url(route('training.confirm.interest', ['training' => $this->training->id, 'key' => $this->key] )));

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {

        $this->addToDB();

        return [
            'training_id' => $this->training->id,
            'key' => $this->key
        ];
    }

    /**
     * Add the notification to our special log table
     *
     */
    private function addToDB()
    {
        if (count(DB::table(Training::CONTINUED_INTEREST_NOTIFICATION_LOG_TABLE)->where('notification_id', $this->id)->get()) == 0) {
            DB::table(Training::CONTINUED_INTEREST_NOTIFICATION_LOG_TABLE)->insert([
                'notification_id' => $this->id,
                'training_id' => $this->training->id,
                'key' => $this->key,
                'deadline' => $this->deadline,
                'created_at' => now()
            ]);
        }
    }
}
