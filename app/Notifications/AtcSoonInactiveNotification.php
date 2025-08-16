<?php

namespace App\Notifications;

use anlutro\LaravelSettings\Facade as Setting;
use App\Mail\WarningMail;
use App\Models\Endorsement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AtcSoonInactiveNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $user;
    private $area;
    private $atcHours;

    /**
     * Create a new notification instance.
     *
     * @param  Endorsement  $endorsement
     */
    public function __construct($user, $area, $atcHours)
    {
        $this->user = $user;
        $this->area = $area;
        $this->atcHours = $atcHours;
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
            'We would like to notify you that your ATC active status in ' . $this->area->name . ' is about to expire. You have ' . round($this->atcHours, 1) . ' online hours out of the required ' . Setting::get('atcActivityRequirement') . ' hours.',
            'Start controlling again on the network to avoid losing your status and potentially waiting for a long time for refresh training.',
        ];

        return (new WarningMail('ATC Inactivity Reminder', $this->user, $textLines))
            ->to($this->user->notificationEmail, $this->user->name);
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
