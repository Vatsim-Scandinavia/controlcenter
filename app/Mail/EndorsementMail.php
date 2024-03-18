<?php

namespace App\Mail;

use App\Models\Endorsement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EndorsementMail extends Mailable
{
    use Queueable, SerializesModels;

    private $endorsement;

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
    public function __construct(string $mailSubject, Endorsement $endorsement, array $textLines, ?string $contactMail = null, ?string $actionUrl = null, ?string $actionText = null, string $actionColor = 'primary')
    {
        $this->mailSubject = $mailSubject;
        $this->endorsement = $endorsement;

        $this->textLines = $textLines;
        $this->contactMail = $contactMail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->mailSubject)->markdown('mail.endorsement', [
            'firstName' => $this->endorsement->user->first_name,
            'textLines' => $this->textLines,
        ]);
    }
}
