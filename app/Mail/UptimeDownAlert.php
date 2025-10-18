<?php

namespace App\Mail;

use App\Models\AlertConfiguration;
use App\Models\Website;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UptimeDownAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Website $website,
        public AlertConfiguration $alertConfig,
        public array $uptimeData
    ) {}

    public function envelope(): Envelope
    {
        $level = strtoupper($this->alertConfig->alert_level);
        $subject = "[$level] Website Down Alert - {$this->website->name}";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.uptime-down',
            with: [
                'website' => $this->website,
                'alertConfig' => $this->alertConfig,
                'uptimeData' => $this->uptimeData,
                'failureReason' => $this->uptimeData['failure_reason'] ?? 'Unknown error',
                'statusCode' => $this->uptimeData['status_code'] ?? null,
                'lastChecked' => $this->uptimeData['checked_at'] ?? now(),
                'urgencyLevel' => strtoupper($this->alertConfig->alert_level),
                'dashboardUrl' => route('ssl.websites.show', $this->website),
            ]
        );
    }
}
