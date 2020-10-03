<?php

namespace App\Notifications;

use App\Mail\TrainingMail;
use App\Training;
use App\Country;
use App\Http\Controllers\TrainingController;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;

class TrainingInterestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training, $key, $deadline;
    private $subjectPrefix = "";

    /**
     * Create a new notification instance.
     *
     * @param Training $training
     * @param string $key
     */
    public function __construct(Training $training, string $key, $deadline = null)
    {
        $this->training = $training;
        $this->key = $key;

        // Check if deadline is manually set, because it's reminder
        if(isset($deadline)){
            $this->deadline = $deadline;
            $this->subjectPrefix = "Reminder: ";
        } else {
            $this->deadline = now()->addDays(14);
        }
        
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return TrainingInterestMail
     */
    public function toMail($notifiable)
    {

        // If training is not standard, specify this in the e-mail.
        $trainingType = "";
        if($this->training->type != 1){
            $trainingType = strtolower(TrainingController::$types[$this->training->type]['text']) . " ";
        }

        $textLines = [
            'Periodically we are asking you to confirm the interest for your ATC controller application with us.',
            'Please confirm your continued interest for your *'.$trainingType.'training* for '.$this->training->getInlineRatings(),
            '**Deadline:** '.$this->deadline->toEuropeanDate(),
            '*If no confirmation is received within deadline, your training request will be automatically closed and your slot in the queue will be lost.*'
        ];

        $contactMail = Country::find($this->training->country_id)->contact;
        $actionUrl = route('training.confirm.interest', ['training' => $this->training->id, 'key' => $this->key] );

        return (new TrainingMail($this->subjectPrefix.'Confirm Continued Training Interest', $this->training, $textLines, $contactMail, $actionUrl, 'Confirm Interest', 'success'))
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
            'key' => $this->key,
            'type' => $this->training->type,
            'deadline' => $this->deadline
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
