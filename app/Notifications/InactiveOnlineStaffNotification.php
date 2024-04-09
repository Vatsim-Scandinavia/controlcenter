<?php

namespace App\Notifications;

use App\Mail\StaffNoticeMail;
use App\Models\Endorsement;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InactiveOnlineStaffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $sendTo;

    private $user;

    private $position;

    private $logonTime;

    /**
     * Create a new notification instance.
     *
     * @param  Endorsement  $endorsement
     */
    public function __construct($sendTo, $user, $position, $logonTime)
    {
        $this->sendTo = $sendTo;
        $this->user = $user;
        $this->position = $position;
        $this->logonTime = $logonTime;
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
            $this->user->name . ' (' . $this->user->id . ') has been warned for logging on the network with an **inactive** ATC status.',
            'Position: ' . $this->position,
            'Logon time: ' . Carbon::parse($this->logonTime)->toEuropeanDateTime(),
            'All admins and moderators in area in question has been notified.',
        ];

        return (new StaffNoticeMail('Unauthorized network logon recorded', $textLines))
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
