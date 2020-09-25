<?php

namespace App\Notifications;

use App\Http\Controllers\TrainingController;
use App\Mail\TrainingMail;
use App\User;
use App\Country;
use App\Training;
use App\TrainingExamination;
use App\TrainingReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrainingExamNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training, $report;

    /**
     * Create a new notification instance.
     *
     * @param Training $training
     * @param string $key
     */
    public function __construct(Training $training, TrainingExamination $report)
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
            "This is a confirmation of your examination result for training: ".$this->training->getInlineRatings().' in '.Country::find($this->training->country_id)->name.'.',
            "Result: **".$this->report->result."**",
            "*For questions regarding your examination, contact your mentor.*",
        ];

        // Find staff who wants notification of new training request
        $bcc = User::where('setting_notify_newexamreport', true)->where('group', '<=', '2')->get()->pluck('email');

        return (new TrainingMail('Training Examination Result', $this->training, $textLines, null, route('training.show', $this->training->id), "Read Report"))
            ->to($this->training->user->email)
            ->bcc($bcc);
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
