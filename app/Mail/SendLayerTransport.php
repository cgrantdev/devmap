<?php

namespace App\Mail;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendLayerTransport extends AbstractTransport
{
    public function __construct(
        private string $apiKey,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $to = [];
        foreach ($email->getTo() as $address) {
            $to[] = [
                'name' => $address->getName() ?: $address->getAddress(),
                'email' => $address->getAddress(),
            ];
        }

        $from = $email->getFrom()[0] ?? null;

        $payload = [
            'From' => [
                'name' => $from?->getName() ?: 'PeptideMaps',
                'email' => $from?->getAddress() ?: 'info@peptidemap.com',
            ],
            'To' => $to,
            'Subject' => $email->getSubject() ?: '(no subject)',
            'ContentType' => 'HTML',
            'HTMLContent' => $email->getHtmlBody() ?: $email->getTextBody() ?: '',
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://console.sendlayer.com/api/v1/email', $payload);

        if (!$response->successful()) {
            Log::error('SendLayer API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('SendLayer API error: ' . $response->status() . ' ' . $response->body());
        }
    }

    public function __toString(): string
    {
        return 'sendlayer';
    }
}
