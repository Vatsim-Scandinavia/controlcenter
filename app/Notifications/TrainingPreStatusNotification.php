<?php

namespace App\Notifications;

use App\Http\Controllers\TrainingController;
use App\Mail\TrainingMail;
use App\Training;
use App\Country;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrainingPreStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training;

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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return TrainingMail
     */
    public function toMail($notifiable)
    {

        $textLines[] = 'We would like to inform you that your training request for '.$this->training->getInlineRatings().' in '.Country::find($this->training->country_id)->name.' has now been assigned to pre-training..';
        $country = Country::find($this->training->country_id);
        if(isset($country->template_newreq)){
            $textLines[] = $country->template_newreq;
        }

        $contactMail = Country::find($this->training->country_id)->contact;

        return (new TrainingMail('Training Assigned', $this->training, $textLines, $contactMail))
            ->to($this->training->user->email, $this->training->user->name)
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
        ];
    }
}
