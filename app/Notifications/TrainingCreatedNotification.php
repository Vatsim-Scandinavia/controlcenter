<?php

namespace App\Notifications;

use App\Mail\TrainingMail;
use App\Models\Area;
use App\Models\Training;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TrainingCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training;

    private $contactMail;

    /**
     * Create a new notification instance.
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
            'We hereby confirm that we have received your training request for ' . $this->training->getInlineRatings() . ' in ' . Area::find($this->training->area_id)->name . '.',
            'The request is now in queue. Expected waiting time is ' . $this->training->area->waiting_time ?? 'unknown',
            'We will periodically ask you to confirm your continued interest for your application with us, it\'s your responsibility to check your email for these requests and reply within the deadline.',
        ];

        $area = Area::find($this->training->area_id);
        if (isset($area->template_newreq)) {
            $textLines[] = $area->template_newreq;
        }

        // Find staff who wants notification of new training request
        $bcc = User::allWithGroup(2, '<=')->where('setting_notify_newreq', true);

        foreach ($bcc as $key => $user) {
            if (! $user->isModeratorOrAbove($this->training->area)) {
                $bcc->pull($key);
            }
        }

        $contactMail = $area->contact;

        return (new TrainingMail('New Training Request Confirmation', $this->training, $textLines, $contactMail))
            ->to($this->training->user->notificationEmail, $this->training->user->name)
            ->bcc($bcc->pluck('notificationEmail'));
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
