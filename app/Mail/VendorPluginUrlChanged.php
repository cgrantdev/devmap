<?php

namespace App\Mail;

use App\Models\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Admin notice that a PeptideMap Connect call changed a vendor URL.
 * The connection token and WooCommerce secrets are not included.
 */
class VendorPluginUrlChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $brandName,
        public string $brandSlug,
        public ?string $previousStoreUrl,
        public ?string $previousProductsUrl,
        public string $storeUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PeptideMap Connect URL change: '.$this->brandSlug,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.vendor-plugin-url-changed',
        );
    }

    public static function forBrand(Brand $brand, ?string $previousStore, ?string $previousFeed, string $storeUrl): self
    {
        return new self(
            $brand->name,
            (string) $brand->slug,
            $previousStore,
            $previousFeed,
            $storeUrl,
        );
    }
}
