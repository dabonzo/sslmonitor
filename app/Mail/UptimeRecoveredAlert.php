<?php

namespace App\Mail;

use App\Models\Website;
use App\Models\AlertConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UptimeRecoveredAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Website $website,
        public AlertConfiguration $alertConfig,
        public array $uptimeData,
        public ?string $downtime = null
    ) {}

    public function envelope(): Envelope
    {
        $subject = "[RECOVERED] Website Back Online - {$this->website->name}";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.uptime-recovered',
            with: [
                'website' => $this->website,
                'alertConfig' => $this->alertConfig,
                'uptimeData' => $this->uptimeData,
                'responseTime' => $this->uptimeData['response_time'] ?? null,
                'statusCode' => $this->uptimeData['status_code'] ?? 200,
                'recoveredAt' => $this->uptimeData['checked_at'] ?? now(),
                'downtime' => $this->downtime,
                'dashboardUrl' => route('ssl.websites.show', $this->website),
            ]
        );
    }
}
