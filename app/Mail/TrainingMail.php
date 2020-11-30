<?php

namespace App\Mail;

use App\Training;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrainingMail extends Mailable
{
    use Queueable, SerializesModels;

    private $training, $mailSubject, $textLines, $contactMail, $actionUrl, $actionText, $actionColor;

    /**
     * Create a new message instance.
     *
     * @param string $mailSubject
     * @param Training $training
     * @param array $textLines
     * @param string $contactMail
     * @param string $actionUrl
     * @param string $actionText
     * @param string $actionColor
     * @return void
     */
    public function __construct(string $mailSubject, Training $training, array $textLines, string $contactMail = null, string $actionUrl = null, string $actionText = null, string $actionColor = 'primary')
    {
        $this->mailSubject = $mailSubject;
        $this->training = $training;
        
        $this->textLines = $textLines;
        $this->contactMail = $contactMail;

        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
        $this->actionColor = $actionColor;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->mailSubject)->markdown('mail.training', [
            'firstName' => $this->training->user->first_name,
            'textLines' => $this->textLines,
            'contactMail' => $this->contactMail,

            'actionUrl' => $this->actionUrl,
            'actionText' => $this->actionText,
            'actionColor' => $this->actionColor,
        ]);
    }
}
