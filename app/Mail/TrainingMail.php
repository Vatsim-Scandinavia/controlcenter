<?php

namespace App\Mail;

use App\Models\Training;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrainingMail extends Mailable
{
    use Queueable, SerializesModels;

    private $training;

    private $mailSubject;

    private $textLines;

    private $contactMail;

    private $actionUrl;

    private $actionText;

    private $actionColor;

    /**
     * Create a new message instance.
     *
     * @param  array  $textLines  an array of markdown lines to add
     * @param  string  $contactMail  optional contact e-mail to put in footer
     * @param  string  $actionUrl  optinal action button url
     * @param  string  $actionText  optional action button text
     * @param  string  $actionColor  optional bootstrap button color override
     * @return void
     */
    public function __construct(string $mailSubject, Training $training, array $textLines, ?string $contactMail = null, ?string $actionUrl = null, ?string $actionText = null, string $actionColor = 'primary')
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
