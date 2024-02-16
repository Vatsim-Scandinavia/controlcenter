<?php

namespace App\Notifications;

use App\Http\Controllers\TrainingController;
use App\Mail\TrainingMail;
use App\Models\Area;
use App\Models\Training;
use App\Models\TrainingInterest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TrainingInterestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training;

    private $interest;

    private $reminder;

    private $subjectPrefix = '';

    /**
     * Create a new notification instance.
     *
     * @param  TrainingInterest  $interest  reference to the interest model
     * @param  bool|null  $reminder  optional, default false, if this notification is a reminder
     * @param  string  $key
     */
    public function __construct(Training $training, TrainingInterest $interest, bool $reminder = false)
    {
        $this->training = $training;
        $this->interest = $interest;
        $this->reminder = $reminder;

        if ($this->reminder) {
            $this->subjectPrefix = 'Reminder: ';
        }
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
     * @return TrainingInterestMail
     */
    public function toMail($notifiable)
    {
        // If training is not standard, specify this in the e-mail.
        $trainingType = '';
        if ($this->training->type != 1) {
            $trainingType = strtolower(TrainingController::$types[$this->training->type]['text']) . ' ';
        }

        $textLines = [
            'Periodically we are asking you to confirm the interest for your ATC controller application with us.',
            'Please confirm your continued interest for your ' . $this->training->getInlineRatings() . ' ' . $trainingType . 'training.',
            '**Deadline:** ' . $this->interest->deadline->toEuropeanDate(),
            '*If no confirmation is received within deadline, your training request will be automatically closed and your slot in the queue or training will be lost.*',
        ];

        $contactMail = Area::find($this->training->area_id)->contact;
        $actionUrl = route('training.confirm.interest', ['training' => $this->training->id, 'key' => $this->interest->key]);

        return (new TrainingMail($this->subjectPrefix . 'Confirm Continued Training Interest', $this->training, $textLines, $contactMail, $actionUrl, 'Confirm Interest', 'success'))
            ->to($this->training->user->notificationEmail, $this->training->user->name);
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
            'training_id' => $this->training->id,
            'key' => $this->interest->key,
            'deadline' => $this->interest->deadline->format('Y-m-d H:i:s'),
            'reminder' => $this->reminder,
        ];
    }
}
