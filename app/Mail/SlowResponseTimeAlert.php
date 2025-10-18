<?php

namespace App\Mail;

use App\Models\Website;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SlowResponseTimeAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Website $website,
        public array $checkData
    ) {}

    public function envelope(): Envelope
    {
        $level = $this->getResponseTimeLevel();

        return new Envelope(
            subject: "[{$level}] Slow Response Time Alert - ".$this->website->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.slow-response-time',
            with: [
                'website' => $this->website,
                'checkData' => $this->checkData,
                'urgencyLevel' => $this->getResponseTimeLevel(),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function getResponseTimeLevel(): string
    {
        $responseTime = $this->checkData['response_time'] ?? 0;

        if ($responseTime >= 10000) {
            return 'CRITICAL';
        } elseif ($responseTime >= 5000) {
            return 'WARNING';
        }

        return 'WARNING';
    }
}
