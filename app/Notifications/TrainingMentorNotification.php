<?php

namespace App\Notifications;

use App\Http\Controllers\TrainingController;
use App\Mail\TrainingMail;
use App\Training;
use App\Country;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrainingMentorNotification extends Notification implements ShouldQueue
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return TrainingMail
     */
    public function toMail($notifiable)
    {

        $textLines = [
            "It's your turn! You've been assigned a mentor for you training: ".$this->training->getInlineRatings().' in '.Country::find($this->training->country_id)->name.'.',
            "Your mentor is: **".$this->training->getInlineMentors()."**. You can contact them through the message system at forums or search them up on [Discord](http://discord.vatsim-scandinavia.org/).",
            "If you do not contact your mentor within 7 days, your training request will be closed and you loose the place in the queue.",
        ];

        $country = Country::find($this->training->country_id);
        if(isset($country->template_newmentor)){
            $textLines[] = $country->template_newmentor;
        }

        $contactMail = $country->contact;
        return (new TrainingMail('Training Mentor Assigned', $this->training, $textLines, $contactMail))
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
