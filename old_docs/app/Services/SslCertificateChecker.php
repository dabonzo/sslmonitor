<?php

namespace App\Services;

use App\Models\SslCheck;
use App\Models\Website;
use Carbon\Carbon;
use Exception;
use Spatie\SslCertificate\SslCertificate;

class SslCertificateChecker
{
    protected SslStatusCalculator $statusCalculator;

    public function __construct()
    {
        $this->statusCalculator = new SslStatusCalculator;
    }

    public function checkCertificate(Website $website, int $timeout = 10): array
    {
        try {
            $url = parse_url($website->url);
            $host = $url['host'] ?? $website->url;

            $certificate = SslCertificate::createForHostname($host, $timeout);

            return $this->parseCertificateData($certificate);

        } catch (Exception $e) {
            return [
                'status' => SslStatusCalculator::STATUS_ERROR,
                'expires_at' => null,
                'issuer' => null,
                'subject' => null,
                'serial_number' => null,
                'signature_algorithm' => null,
                'is_valid' => false,
                'days_until_expiry' => null,
                'error_message' => $this->getErrorMessage($e),
            ];
        }
    }

    public function parseCertificateData(SslCertificate $certificate): array
    {
        $rawFields = $certificate->getRawCertificateFields();
        $expirationDate = Carbon::createFromTimestamp($rawFields['validTo_time_t']);
        $daysUntilExpiry = $this->statusCalculator->calculateDaysUntilExpiry($expirationDate);
        $isValid = $certificate->isValid();

        // Determine status using the status calculator
        $status = $this->statusCalculator->calculateStatus($expirationDate, $isValid, $daysUntilExpiry);

        return [
            'status' => $status,
            'expires_at' => $expirationDate,
            'issuer' => $certificate->getIssuer(),
            'subject' => $certificate->getDomain(),
            'serial_number' => $certificate->getSerialNumber(),
            'signature_algorithm' => $certificate->getSignatureAlgorithm(),
            'is_valid' => $isValid,
            'days_until_expiry' => $daysUntilExpiry,
            'error_message' => null,
        ];
    }

    public function checkAndStoreCertificate(Website $website, int $timeout = 10): SslCheck
    {
        $result = $this->checkCertificate($website, $timeout);

        return SslCheck::create([
            'website_id' => $website->id,
            'status' => $result['status'],
            'checked_at' => now(),
            'expires_at' => $result['expires_at'],
            'issuer' => $result['issuer'],
            'subject' => $result['subject'],
            'serial_number' => $result['serial_number'],
            'signature_algorithm' => $result['signature_algorithm'],
            'is_valid' => $result['is_valid'],
            'days_until_expiry' => $result['days_until_expiry'],
            'error_message' => $result['error_message'],
        ]);
    }

    private function getErrorMessage(Exception $e): string
    {
        $message = $e->getMessage();

        // Handle common error patterns
        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return 'Connection timeout while checking SSL certificate';
        }

        if (str_contains($message, 'resolve') || str_contains($message, 'DNS')) {
            return 'DNS resolution failed for hostname';
        }

        if (str_contains($message, 'connection') || str_contains($message, 'Connection')) {
            return 'Unable to establish connection to server';
        }

        if (str_contains($message, 'SSL') || str_contains($message, 'certificate')) {
            return 'SSL certificate error: '.$message;
        }

        return 'Network error while checking SSL certificate: '.$message;
    }
}
