<?php

namespace App\Notifications;

use App\Mail\TrainingMail;
use App\Models\Area;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\TrainingReport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TrainingExamNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training;

    private $report;

    /**
     * Create a new notification instance.
     *
     * @param  TrainingReport  $report  referenced in the notification
     * @param  string  $key
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
            'This is a confirmation of your examination result for training: ' . $this->training->getInlineRatings() . ' in ' . Area::find($this->training->area_id)->name . '.',
            'Result: **' . $this->report->result . '**',
            '*For questions regarding your examination, contact your mentor.*',
        ];

        // Find staff who wants notification of new training request
        $bcc = User::allWithGroup(2, '<=')->where('setting_notify_newexamreport', true);

        foreach ($bcc as $key => $user) {
            if (! $user->isModeratorOrAbove($this->training->area)) {
                $bcc->pull($key);
            }
        }

        return (new TrainingMail('Training Examination Result', $this->training, $textLines, null, route('training.show', $this->training->id), 'Read Report'))
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
            'training_examination_report' => $this->report->id,
        ];
    }
}
