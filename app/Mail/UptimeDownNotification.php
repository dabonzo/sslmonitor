<?php

namespace App\Mail;

use App\Models\UptimeCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UptimeDownNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public UptimeCheck $uptimeCheck;

    /**
     * Create a new message instance.
     */
    public function __construct(UptimeCheck $uptimeCheck)
    {
        $this->uptimeCheck = $uptimeCheck;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $website = $this->uptimeCheck->website;
        $status = ucfirst($this->uptimeCheck->status);

        return new Envelope(
            subject: "Website {$status} - {$website->name} - {$website->url}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.uptime-down',
            with: [
                'uptimeCheck' => $this->uptimeCheck,
                'website' => $this->uptimeCheck->website,
                'status' => $this->uptimeCheck->status,
                'statusCode' => $this->uptimeCheck->http_status_code,
                'responseTime' => $this->uptimeCheck->response_time_ms,
                'errorMessage' => $this->uptimeCheck->error_message,
                'checkedAt' => $this->uptimeCheck->checked_at,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
