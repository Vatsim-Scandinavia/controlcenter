<?php

namespace App\Notifications;

use App\Http\Controllers\TrainingController;
use App\Mail\TrainingMail;
use App\Country;
use App\Training;
use App\TrainingReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrainingReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training, $report;

    /**
     * Create a new notification instance.
     *
     * @param Training $training
     * @param TrainingReport $report to reference
     * @param string $key
     */
    public function __construct(Training $training, TrainingReport $report)
    {
        $this->training = $training;
        $this->report = $report;
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
            "Your mentor ".$this->report->author->name.' has written a new report for your training.',
        ];

        return (new TrainingMail('Training Report', $this->training, $textLines, null, route('training.show', $this->training->id), "Read Report"))
            ->to($this->training->user->email, $this->training->user->name);
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
            'training_report_id' => $this->report->id
        ];
    }
}
