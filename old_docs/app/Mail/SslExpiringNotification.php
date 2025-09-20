<?php

namespace App\Mail;

use App\Models\SslCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SslExpiringNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public SslCheck $sslCheck;

    /**
     * Create a new message instance.
     */
    public function __construct(SslCheck $sslCheck)
    {
        $this->sslCheck = $sslCheck;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $website = $this->sslCheck->website;
        $daysLeft = $this->sslCheck->days_until_expiry;

        return new Envelope(
            subject: "SSL Certificate Expiring Soon - {$website->name} ({$daysLeft} days left)",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ssl-expiring',
            with: [
                'sslCheck' => $this->sslCheck,
                'website' => $this->sslCheck->website,
                'daysLeft' => $this->sslCheck->days_until_expiry,
                'expiresAt' => $this->sslCheck->expires_at,
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
