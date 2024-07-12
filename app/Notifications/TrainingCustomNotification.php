<?php

namespace App\Notifications;

use App\Mail\TrainingMail;
use App\Models\Area;
use App\Models\Training;
use App\Models\TrainingReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TrainingCustomNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Training $training;

    private string $title;

    private array $messageLines;

    private string $url;

    private string $cta;

    /**
     * Create a new notification instance.
     *
     * @param  TrainingReport  $report  referenced in the notification
     * @param  string  $key
     */
    public function __construct(Training $training, string $title, array $messageLines = [], ?string $url = null, ?string $cta = null)
    {
        $this->training = $training;
        $this->title = $title;
        $this->messageLines = $messageLines;
        $this->url = $url;
        $this->cta = $cta;
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
        $contactMail = Area::find($this->training->area_id)->contact;

        // Only add CTA and Url if they are set
        if ($this->url && $this->cta) {
            return (new TrainingMail($this->title, $this->training, $this->messageLines, $contactMail, $this->url, $this->cta))
                ->to($this->training->user->notificationEmail, $this->training->user->name);
        } else {
            return (new TrainingMail($this->title, $this->training, $this->messageLines, $contactMail))
                ->to($this->training->user->notificationEmail, $this->training->user->name);
        }
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
