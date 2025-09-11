<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SslErrorNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $sslCheck;

    /**
     * Create a new message instance.
     */
    public function __construct($sslCheck)
    {
        $this->sslCheck = $sslCheck;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "SSL Certificate Error - {$this->sslCheck->website->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ssl-error',
            with: [
                'sslCheck' => $this->sslCheck,
                'website' => $this->sslCheck->website,
                'errorMessage' => $this->sslCheck->error_message,
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
