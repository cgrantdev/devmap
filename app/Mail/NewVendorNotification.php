<?php

namespace App\Mail;

use App\Models\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewVendorNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Brand $brand,
        public string $contactEmail,
        public string $website,
        public ?string $phone,
        public ?string $country,
        public ?string $description,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Vendor Signup: {$this->brand->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-vendor-notification',
        );
    }
}
