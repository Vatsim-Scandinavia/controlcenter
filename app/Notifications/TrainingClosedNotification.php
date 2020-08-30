<?php

namespace App\Notifications;

use App\Http\Controllers\TrainingController;
use App\Mail\TrainingClosedMail;
use App\Training;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrainingClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training, $closedBy, $reason;

    /**
     * Create a new notification instance.
     *
     * @param Training $training
     * @param string $key
     */
    public function __construct(Training $training, int $closedBy, string $reason = null)
    {
        $this->training = $training;
        $this->closedBy = strtolower(TrainingController::$statuses[$closedBy]['text']);
        $this->reason = $reason;
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
     * @return TrainingClosedMail
     */
    public function toMail($notifiable)
    {
        return (new TrainingClosedMail($this->training, $this->closedBy, $this->reason))
            ->to($this->training->user->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'training_id' => $this->training->id
        ];
    }
}
