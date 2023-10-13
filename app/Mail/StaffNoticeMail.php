<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StaffNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    private $mailSubject;

    private $textLines;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $mailSubject, array $textLines)
    {
        $this->mailSubject = $mailSubject;
        $this->textLines = $textLines;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->mailSubject)->markdown('mail.staffnotice', [
            'textLines' => $this->textLines,
        ]);
    }
}
