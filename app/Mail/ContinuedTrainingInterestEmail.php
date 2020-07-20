<?php

namespace App\Mail;

use App\Training;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContinuedTrainingInterestEmail extends Mailable
{
    use Queueable, SerializesModels;

    private $training, $key, $deadline;

    /**
     * Create a new message instance.
     *
     * @param Training $training
     * @param string $key
     * @param $deadline
     */
    public function __construct(Training $training, string $key, $deadline)
    {
        $this->training = $training;
        $this->key = $key;
        $this->deadline = $deadline;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Set up
        $introLines = [
            'Please confirm your continued interest in your training for the following ratings:',
        ];

        foreach ($this->training->ratings as $rating) {
            $introLines[] = ' - ' . $rating->name;
        }

        $introLines[] = 'Deadline: ' . $this->deadline->toEuropeanDate();

        // Create mail
        return $this->markdown('mail.notifications.continued-interest', [
            'level' => '',
            'actionUrl' => route('training.confirm.interest', ['training' => $this->training->id, 'key' => $this->key] ),
            'actionText' => 'Confirm Interest',
            'greeting' => 'Dear ' . $this->training->user->first_name . ',',
            'introLines' => $introLines,
            'outroLines' => [],
            'displayableActionUrl' => route('training.confirm.interest', ['training' => $this->training->id, 'key' => $this->key] ),
        ]);
    }
}
