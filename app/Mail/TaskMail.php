<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskMail extends Mailable
{
    use Queueable, SerializesModels;

    private $mailSubject;

    private $user;

    private $textLines;

    /**
     * Create a new message instance.
     *
     * @param  string  $mailSubject  the subject of the email
     * @param  User  $user  the user to send the email to
     * @param  array  $textLines  an array of markdown lines to add
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
        return $this->subject($this->mailSubject)->markdown('mail.tasks', [
            'firstName' => $this->user->first_name,
            'textLines' => $this->textLines,
            'actionUrl' => route('tasks'),
        ]);
    }
}
