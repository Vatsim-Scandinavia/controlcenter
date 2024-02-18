<?php

namespace App\Notifications;

use anlutro\LaravelSettings\Facade as Setting;
use App\Mail\WarningMail;
use App\Models\Endorsement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InactivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private User $user;

    /**
     * Create a new notification instance.
     *
     * @param  Endorsement  $endorsement
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
        if (Setting::get('atcActivityAllowInactiveControlling')) {
            $textLines = [
                'Your ATC Status has been set as **inactive**. You may however still log on the network in our division if you wish.',
                'According to local rules, you are required to have at least ' . Setting::get('atcActivityRequirement') . ' online hours during the last ' . Setting::get('atcActivityQualificationPeriod') . ' months. You did not fulfill this requirement, and therefore you are now set as inactive.',
                'Please check local rules for more information what it means to be inactive.',
            ];
        } else {
            $textLines = [
                'Your ATC Status has been set as **inactive**. You are no longer allowed to log on the network in our division.',
                'According to local rules, you are required to have at least ' . Setting::get('atcActivityRequirement') . ' online hours during the last ' . Setting::get('atcActivityQualificationPeriod') . ' months. You did not fulfill this requirement, and therefore you are now set as inactive.',
                'To control online again, you will need to apply for a refresh training with [' . Setting::get('atcActivityContact') . '](' . Setting::get('linkContact') . '),',
            ];
        }

        return (new WarningMail('You are now inactive', $this->user, $textLines))
            ->to($this->user->email, $this->user->name);
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
