<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorWelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $companyName,
        public string $email,
        public string $password,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Welcome to PeptideMaps — Your vendor account is ready",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vendor-welcome',
        );
    }
}
