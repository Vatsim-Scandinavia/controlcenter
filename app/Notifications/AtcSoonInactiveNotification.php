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
            'We would like to notify you that your ATC active status in ' . $this->area->name . ' is about to expire.',
            '**' . sprintf(
                'We require a minimum of %s hours in a period of %s months. You currently have %.1f online hours.',
                Setting::get('atcActivityRequirement'),
                Setting::get('atcActivityQualificationPeriod'),
                $this->atcHours
            ) . '**',
            'Please continue controlling on the network to maintain your ATC active status and avoid a long wait for refresher training.',
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
