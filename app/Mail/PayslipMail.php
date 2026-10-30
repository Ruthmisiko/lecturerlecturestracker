<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Payslip email: the user's subject and message, with the payslip PDF attached (none for a plain message). */
class PayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $messageText,
        public ?string $pdf,
        public ?string $fileName,
        public string $fromAddress,
        public string $fromName,
    ) {}

    public function build()
    {
        $mail = $this->from($this->fromAddress, $this->fromName)
            ->subject($this->mailSubject)
            ->html('<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5;color:#222;">'
                . nl2br(e($this->messageText)) . '</div>')
            ->text('emails.plain', ['text' => $this->messageText]);

        return $this->pdf ? $mail->attachData($this->pdf, $this->fileName, ['mime' => 'application/pdf']) : $mail;
    }
}
