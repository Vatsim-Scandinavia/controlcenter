<?php

namespace App\Notifications;

use App\Mail\EndorsementMail;
use App\Models\Endorsement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EndorsementModifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $endorsement;

    /**
     * Create a new notification instance.
     */
    public function __construct(Endorsement $endorsement)
    {
        $this->endorsement = $endorsement;
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
        $validTo = isset($this->endorsement->valid_to) ? $this->endorsement->valid_to->toEuropeanDateTime() : 'Forever';

        $textLines = [
            'Your **' . ucwords(strtolower((string) $this->endorsement->type)) . ' Endorsement** has been modified at following positions: *' . $this->endorsement->positions->pluck('callsign')->implode(', ') . '*',
            '**New expire date:** ' . $validTo,
        ];

        return (new EndorsementMail('Training Endorsement Modified', $this->endorsement, $textLines))
            ->to($this->endorsement->user->notificationEmail, $this->endorsement->user->name);
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
            'endorsement_id' => $this->endorsement->id,
        ];
    }
}
