<?php

namespace App\Notifications;

use App\Mail\TrainingMail;
use App\Models\Area;
use App\Models\Training;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TrainingPreStatusNotification extends Notification implements ShouldQueue
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
        $textLines[] = 'We would like to inform you that your training request for ' . $this->training->getInlineRatings() . ' in ' . Area::find($this->training->area_id)->name . ' has now been assigned to pre-training.';
        $area = Area::find($this->training->area_id);
        if (isset($area->template_pretraining)) {
            $textLines[] = $area->template_pretraining;
        }

        $contactMail = Area::find($this->training->area_id)->contact;

        return (new TrainingMail('Training Assigned', $this->training, $textLines, $contactMail))
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
        ];
    }
}
