<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WarningMail extends Mailable
{
    use Queueable, SerializesModels;

    private $mailSubject;

    private $user;

    private $textLines;

    /**
     * Create a new message instance.
     *
     * @param  Endorsement  $endorsement
     * @param  array  $textLines  an array of markdown lines to add
     * @param  string  $contactMail  optional contact e-mail to put in footer
     * @param  string  $actionUrl  optinal action button url
     * @param  string  $actionText  optional action button text
     * @param  string  $actionColor  optional bootstrap button color override
     * @return void
     */
    public function __construct(string $mailSubject, User $user, array $textLines)
    {
        $this->mailSubject = $mailSubject;
        $this->user = $user;
        $this->textLines = $textLines;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->mailSubject)->markdown('mail.warning', [
            'firstName' => $this->user->first_name,
            'textLines' => $this->textLines,
        ]);
    }
}
