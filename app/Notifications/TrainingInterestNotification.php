<?php

namespace App\Notifications;

use App\Mail\TrainingMail;
use App\Training;
use App\Country;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;

class TrainingInterestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training, $key, $deadline;

    /**
     * Create a new notification instance.
     *
     * @param Training $training
     * @param string $key
     */
    public function __construct(Training $training, string $key)
    {
        $this->training = $training;
        $this->key = $key;

        $this->deadline = now()->addDays(14);
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
     * @param mixed $notifiable
     * @return TrainingInterestMail
     */
    public function toMail($notifiable)
    {
        $textLines = [
            'Periodically we are asking you to confirm the interest for your ATC controller application with us.',
            'Please confirm your continued interest in your training for '.$this->training->getInlineRatings(),
            '**Deadline:** '.$this->deadline->toEuropeanDate(),
            '*If no confirmation is received within deadline, your training request will be automatically closed and your slot in the queue will be lost.*'
        ];

        $contactMail = Country::find($this->training->country_id)->contact;
        $actionUrl = route('training.confirm.interest', ['training' => $this->training->id, 'key' => $this->key] );

        return (new TrainingMail('Confirm Continued Training Interest', $this->training, $textLines, $contactMail, $actionUrl, 'Confirm Interest', 'success'))
            ->to($this->training->user->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {

        $this->addToDB();

        return [
            'training_id' => $this->training->id,
            'key' => $this->key
        ];
    }

    /**
     * Add the notification to our special log table
     *
     */
    private function addToDB()
    {
        if (count(DB::table(Training::CONTINUED_INTEREST_NOTIFICATION_LOG_TABLE)->where('notification_id', $this->id)->get()) == 0) {
            DB::table(Training::CONTINUED_INTEREST_NOTIFICATION_LOG_TABLE)->insert([
                'notification_id' => $this->id,
                'training_id' => $this->training->id,
                'key' => $this->key,
                'deadline' => $this->deadline,
                'created_at' => now()
            ]);
        }
    }
}
