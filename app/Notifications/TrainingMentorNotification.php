<?php

namespace App\Notifications;

use anlutro\LaravelSettings\Facade as Setting;
use App\Mail\TrainingMail;
use App\Models\Area;
use App\Models\Training;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TrainingMentorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training;

    /**
     * Create a new notification instance.
     *
     * @param  string  $key
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
        $textLines = [
            "It's your turn! You've been assigned a mentor for your training: " . $this->training->getInlineRatings() . ' in ' . Area::find($this->training->area_id)->name . '.',
            'Your mentor is: **' . $this->training->getInlineMentors() . '**. You can contact them through the message system at forums or search them up on [Discord](' . Setting::get('linkDiscord') . ').',
            'If you do not contact your mentor within 7 days, your training request will be closed and you lose the place in the queue.',
        ];

        $area = Area::find($this->training->area_id);
        if (isset($area->template_newmentor)) {
            $textLines[] = $area->template_newmentor;
        }

        $contactMail = $area->contact;

        return (new TrainingMail('Training Mentor Assigned', $this->training, $textLines, $contactMail))
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
            'mentors' => $this->training->getInlineMentors(),
        ];
    }
}
