<?php

namespace App\Mail;

use App\Models\Website;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SslCertificateInvalidAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Website $website,
        public array $checkData
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[CRITICAL] SSL Certificate Invalid Alert - '.$this->website->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ssl-invalid',
            with: [
                'website' => $this->website,
                'checkData' => $this->checkData,
                'urgencyLevel' => 'CRITICAL',
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
