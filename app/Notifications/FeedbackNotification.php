<?php

namespace App\Notifications;

use anlutro\LaravelSettings\Facade as Setting;
use App\Mail\StaffNoticeMail;
use App\Models\Endorsement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FeedbackNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $feedback;

    /**
     * Create a new notification instance.
     *
     * @param  Endorsement  $endorsement
     */
    public function __construct($feedback)
    {
        $this->feedback = $feedback;
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

        if (! Setting::get('feedbackEnabled')) {
            return false;
        }

        $position = isset($this->feedback->referencePosition) ? $this->feedback->referencePosition->callsign : 'N/A';
        $controller = isset($this->feedback->referenceUser) ? $this->feedback->referenceUser->name : 'N/A';

        $textLines = [
            'New feedback has been submitted by ' . $this->feedback->submitter->name . ' (' . $this->feedback->submitter->id . '). You may respond by replying to this email.',
            '___',
            '**Controller**: ' . $controller,
            '**Position**: ' . $position,
            '**Pilot callsign**: ' . ($this->feedback->pilot_callsign ?? 'N/A'),
            '**Date of event**: ' . ($this->feedback->event_date?->toEuropeanDate() ?? 'N/A'),
            '**Departure**: ' . ($this->feedback->departure_airport ?? 'N/A'),
            '**Arrival**: ' . ($this->feedback->arrival_airport ?? 'N/A'),
            '___',
            '**Feedback**',
            $this->feedback->feedback,

        ];

        $feedbackForward = Setting::get('feedbackForwardEmail');

        return (new StaffNoticeMail('Feedback submited', $textLines))
            ->to($feedbackForward)
            ->replyTo($this->feedback->submitter->personalNotificationEmail, $this->feedback->submitter->name);
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
