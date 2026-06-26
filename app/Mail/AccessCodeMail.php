<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccessCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose,
        public int $expiresInMinutes,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Your SIMDEX confirmation code')
            ->view('emails.access-code');
    }
}
