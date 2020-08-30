<?php

namespace App\Mail;

use App\Training;
use App\Country;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrainingCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    private $training, $contactMail;

     /**
     * Create a new message instance.
     *
     * @param Training $training
     * @param string $key
     * @param $deadline
     */
    public function __construct(Training $training, string $contactMail)
    {
        $this->training = $training;
        $this->contactMail = $contactMail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Set up
        $introLines[] = 'We herby confirm that we have received your training request for the following ratings in '.Country::find($this->training->country_id)->name.':';
        foreach ($this->training->ratings as $rating) {
            $introLines[] = ' - ' . $rating->name;
        }

        $introLines[] = 'The request is now in queue. Expected waiting time: '.\Setting::get('trainingQueue');

        // Create mail
        return $this->subject('Confirmation of Training Request')->markdown('mail.training.created', [
            'greeting' => 'Hello ' . $this->training->user->first_name . ',',
            'introLines' => $introLines,
            'contactMail' => $this->contactMail,
        ]);
    }
}
