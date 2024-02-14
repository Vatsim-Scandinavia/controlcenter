<?php

namespace App\Notifications;

use anlutro\LaravelSettings\Facade as Setting;
use App\Mail\WarningMail;
use App\Models\Area;
use App\Models\Endorsement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InactivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private User $user;

    private ?Area $area;

    /**
     * Create a new notification instance.
     *
     * @param  Endorsement  $endorsement
     */
    public function __construct(User $user, ?Area $area = null)
    {
        $this->user = $user;
        $this->area = $area;
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

        if (isset($this->area)) {
            $inactiveString = 'Your ATC status has been set as **inactive** in **' . $this->area->name . '**.';
        } else {
            $inactiveString = 'Your ATC status has been set as **inactive**.';
        }

        if (Setting::get('atcActivityAllowInactiveControlling')) {
            $loginRuleString = 'You may however still log on the network in ' . (isset($this->area) ? $this->area->name : 'our area') . ' if you wish. Please check the local policies for more information what it means to be inactive.';
        } else {
            $loginRuleString = 'You are no longer allowed to log on the network in ' . (isset($this->area) ? $this->area->name : 'our area') . '. To control online again, you will need to apply for a refresh training with [' . Setting::get('atcActivityContact') . '](' . Setting::get('linkContact') . ')';
        }

        $textLines = [
            $inactiveString,
            $loginRuleString,
            'According to local rules, you are required to have at least ' . Setting::get('atcActivityRequirement') . ' online hours during the last ' . Setting::get('atcActivityQualificationPeriod') . ' months. You did not fulfill this requirement, and therefore you are now set as inactive.',
        ];

        return (new WarningMail('You are now inactive', $this->user, $textLines))
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
