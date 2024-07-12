<?php

namespace App\Notifications;

use App\Mail\MentorNoticeMail;
use App\Models\Endorsement;
use App\Models\TrainingExamination;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MentorExaminationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $sendTo;

    private User $user;

    private TrainingExamination $report;

    private ?string $actionUrl;

    private ?string $actionText;

    /**
     * Create a new notification instance.
     *
     * @param  Endorsement  $endorsement
     */
    public function __construct($sendTo, User $user, TrainingExamination $report, ?string $actionUrl = null, ?string $actionText = null)
    {
        $this->sendTo = $sendTo;
        $this->user = $user;
        $this->report = $report;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
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
     * @return EndorsementMail
     */
    public function toMail($notifiable)
    {
        $textLines = [
            'Your student ' . $this->user->name . ' (' . $this->user->id . ') has been issued an examination report.',
            'Result: **' . $this->report->result . '**',
        ];

        return (new MentorNoticeMail('Your student\'s examination report', $textLines, $this->actionUrl, $this->actionText))
            ->to($this->sendTo->pluck('notificationEmail'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [];
    }
}
