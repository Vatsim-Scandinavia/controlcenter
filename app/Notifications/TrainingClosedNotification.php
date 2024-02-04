<?php

namespace App\Notifications;

use App\Http\Controllers\TrainingController;
use App\Mail\TrainingMail;
use App\Models\Area;
use App\Models\Training;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TrainingClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training;

    private $closedBy;

    private $reason;

    /**
     * Create a new notification instance.
     *
     * @param  int|null  $closedBy the training status code that indicates who closed it
     * @param  string|null  $reason optional reason of closure communicated to the receiver
     */
    public function __construct(Training $training, int $closedBy, ?string $reason = null)
    {
        $this->training = $training;
        $this->closedBy = strtolower(TrainingController::$statuses[$closedBy]['text']);
        $this->reason = $reason;
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
        $textLines[] = 'We would like to inform you that your training request for ' . $this->training->getInlineRatings() . ' in ' . Area::find($this->training->area_id)->name . ' has been *' . $this->closedBy . '*.';
        if (isset($this->reason)) {
            $textLines[] = '**Reason for closure:** ' . $this->reason;
        }

        $contactMail = Area::find($this->training->area_id)->contact;

        // Find staff who wants notification of new training request
        $bcc = User::allWithGroup(2, '<=')->where('setting_notify_closedreq', true);

        foreach ($bcc as $key => $user) {
            if (! $user->isModeratorOrAbove($this->training->area)) {
                $bcc->pull($key);
            }
        }

        return (new TrainingMail('Training Request Closed', $this->training, $textLines, $contactMail))
            ->to($this->training->user->email, $this->training->user->name)
            ->bcc($bcc->pluck('email'));
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
            'new_status' => $this->training->status,
            'reason' => $this->reason,
        ];
    }
}
