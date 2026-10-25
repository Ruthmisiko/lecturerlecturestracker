<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Login details for a newly created user, branded with the institution's name and colours. */
class WelcomeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $email,
        public string $password,
        public string $role,
        public string $institutionName,
        public string $loginUrl,
        public string $primary,
        public string $primaryText,
        public string $fromAddress,
        public string $fromName,
    ) {}

    public function build()
    {
        return $this->from($this->fromAddress, $this->fromName)
            ->subject("Your {$this->institutionName} Lecturer Tracker login")
            ->view('emails.welcome_user')
            ->text('emails.welcome_user_text');
    }
}
