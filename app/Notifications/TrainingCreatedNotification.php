<?php

namespace App\Notifications;

use App\Mail\TrainingMail;
use App\Training;
use App\Country;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrainingCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training, $contactMail;

    /**
     * Create a new notification instance.
     *
     * @param Training $training
     * @param string $key
     */
    public function __construct(Training $training)
    {
        $this->training = $training;
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
     * @return TrainingCreatedMail
     */
    public function toMail($notifiable)
    {

        $textLines = [
            'We herby confirm that we have received your training request for '.$this->training->getInlineRatings().' in '.Country::find($this->training->country_id)->name.'.',
            'The request is now in queue. Expected waiting time: '.\Setting::get('trainingQueue'),
        ];

        $contactMail = Country::find($this->training->country_id)->contact;

        return (new TrainingMail('New Training Request Confirmation', $this->training, $textLines, $contactMail))
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
