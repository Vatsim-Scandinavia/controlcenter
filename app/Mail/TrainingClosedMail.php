<?php

namespace App\Mail;

use App\Training;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrainingClosedMail extends Mailable
{
    use Queueable, SerializesModels;

    private $training, $closedBy, $reason;

     /**
     * Create a new message instance.
     *
     * @param Training $training
     * @param string $key
     * @param $deadline
     */
    public function __construct(Training $training, string $closedBy, string $reason = null)
    {
        $this->training = $training;
        $this->closedBy = $closedBy;
        $this->reason = $reason;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Set up
        $introLines[] = 'We would like to inform you that your training request has been '.$this->closedBy.'.';

        if(isset($this->reason)){
            $introLines[] = 'Reason: '.$this->reason;
        }

        $introLines[] = 'The training request in question is for following ratings:';
        foreach ($this->training->ratings as $rating) {
            $introLines[] = ' - ' . $rating->name;
        }

        $introLines[] = 'If you believe this is a mistake, contact your mentor or appropriate training assistant for the country you applied for.';

        // Create mail
        return $this->subject('Training Request Closed')->markdown('mail.training.closed', [
            'greeting' => 'Hello ' . $this->training->user->first_name . ',',
            'introLines' => $introLines,
        ]);
    }
}
