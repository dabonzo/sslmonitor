<?php

namespace App\Mail;

use App\Models\AlertConfiguration;
use App\Models\Website;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SslCertificateExpiryAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Website $website,
        public AlertConfiguration $alertConfig,
        public array $certificateData
    ) {}

    public function envelope(): Envelope
    {
        $level = strtoupper($this->alertConfig->alert_level);
        $subject = "[$level] SSL Certificate Alert - {$this->website->name}";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ssl-certificate-expiry',
            with: [
                'website' => $this->website,
                'alertConfig' => $this->alertConfig,
                'certificateData' => $this->certificateData,
                'daysRemaining' => $this->certificateData['ssl_days_remaining'] ?? 0,
                'isLetsEncrypt' => $this->certificateData['is_lets_encrypt'] ?? false,
                'urgencyLevel' => $this->getUrgencyLevel(),
                'actionRequired' => $this->getActionRequired(),
                'dashboardUrl' => route('ssl.websites.show', $this->website),
            ]
        );
    }

    private function getUrgencyLevel(): string
    {
        $days = $this->certificateData['ssl_days_remaining'] ?? 0;

        if ($days <= 0) {
            return 'EXPIRED';
        }
        if ($days <= 3) {
            return 'CRITICAL';
        }
        if ($days <= 7) {
            return 'URGENT';
        }
        if ($days <= 14) {
            return 'WARNING';
        }

        return 'INFO';
    }

    private function getActionRequired(): string
    {
        $days = $this->certificateData['ssl_days_remaining'] ?? 0;
        $isLetsEncrypt = $this->certificateData['is_lets_encrypt'] ?? false;

        if ($days <= 0) {
            return 'IMMEDIATE ACTION REQUIRED: Certificate has expired and website security is compromised.';
        }

        if ($days <= 3) {
            if ($isLetsEncrypt) {
                return 'CRITICAL: Verify Let\'s Encrypt auto-renewal is working. Manual intervention may be needed.';
            }

            return 'CRITICAL: Renew certificate immediately to prevent service disruption.';
        }

        if ($days <= 7) {
            if ($isLetsEncrypt) {
                return 'URGENT: Check Let\'s Encrypt auto-renewal configuration and logs.';
            }

            return 'URGENT: Schedule certificate renewal within the next few days.';
        }

        if ($isLetsEncrypt) {
            return 'Monitor Let\'s Encrypt auto-renewal process.';
        }

        return 'Plan certificate renewal for the coming weeks.';
    }
}
