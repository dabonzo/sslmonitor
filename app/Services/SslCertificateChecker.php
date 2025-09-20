<?php

namespace App\Services;

use App\Models\SslCheck;
use App\Models\SslCertificate as SslCertificateModel;
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
        $startTime = microtime(true);

        try {
            $url = parse_url($website->url);
            $host = $url['host'] ?? $website->url;

            $certificate = SslCertificate::createForHostname($host, $timeout);
            $responseTime = microtime(true) - $startTime;

            $result = $this->parseCertificateData($certificate);
            $result['checked_at'] = now();
            $result['response_time'] = round($responseTime, 3);

            return $result;

        } catch (Exception $e) {
            $responseTime = microtime(true) - $startTime;

            return [
                'status' => SslStatusCalculator::STATUS_ERROR,
                'checked_at' => now(),
                'expires_at' => null,
                'issuer' => null,
                'subject' => null,
                'serial_number' => null,
                'signature_algorithm' => null,
                'is_valid' => false,
                'days_until_expiry' => null,
                'error_message' => $this->getErrorMessage($e),
                'response_time' => round($responseTime, 3),
                'plugin_metrics' => [],
                'certificate_chain_length' => null,
                'protocol_version' => null,
                'cipher_suite' => null,
                'key_size' => null,
                'ocsp_status' => null,
            ];
        }
    }

    public function checkAndStoreCertificate(Website $website, int $timeout = 10): SslCheck
    {
        $result = $this->checkCertificate($website, $timeout);

        // Create SSL check record
        $sslCheck = SslCheck::create([
            'website_id' => $website->id,
            'status' => $result['status'],
            'checked_at' => $result['checked_at'],
            'expires_at' => $result['expires_at'],
            'issuer' => $result['issuer'],
            'subject' => $result['subject'],
            'serial_number' => $result['serial_number'],
            'signature_algorithm' => $result['signature_algorithm'],
            'is_valid' => $result['is_valid'],
            'days_until_expiry' => $result['days_until_expiry'],
            'error_message' => $result['error_message'],
        ]);

        // If check was successful, also create/update SSL certificate record
        if ($result['status'] !== SslStatusCalculator::STATUS_ERROR && $result['expires_at']) {
            $website->sslCertificates()->updateOrCreate(
                [
                    'serial_number' => $result['serial_number'],
                ],
                [
                    'issuer' => $result['issuer'],
                    'subject' => $result['subject'],
                    'expires_at' => $result['expires_at'],
                    'signature_algorithm' => $result['signature_algorithm'],
                    'is_valid' => $result['is_valid'],
                    'last_checked_at' => $result['checked_at'],
                ]
            );
        }

        return $sslCheck;
    }

    public function parseCertificateData(SslCertificate $certificate): array
    {
        $rawFields = $certificate->getRawCertificateFields();
        $expirationDate = Carbon::createFromTimestamp($rawFields['validTo_time_t']);
        $daysUntilExpiry = $this->statusCalculator->calculateDaysUntilExpiry($expirationDate);
        $isValid = $certificate->isValid();

        // Determine status using the status calculator
        $status = $this->statusCalculator->calculateStatus($expirationDate, $isValid, $daysUntilExpiry);

        // Extract additional certificate details for v4 plugin architecture
        $certificateChainLength = $this->extractCertificateChainLength($certificate);
        $protocolVersion = $this->extractProtocolVersion($certificate);
        $cipherSuite = $this->extractCipherSuite($certificate);
        $keySize = $this->extractKeySize($certificate);
        $ocspStatus = $this->extractOcspStatus($certificate);

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
            'plugin_metrics' => [
                'certificate_chain_length' => $certificateChainLength,
                'protocol_version' => $protocolVersion,
                'cipher_suite' => $cipherSuite,
                'key_size' => $keySize,
                'ocsp_status' => $ocspStatus,
            ],
            'certificate_chain_length' => $certificateChainLength,
            'protocol_version' => $protocolVersion,
            'cipher_suite' => $cipherSuite,
            'key_size' => $keySize,
            'ocsp_status' => $ocspStatus,
        ];
    }

    private function extractCertificateChainLength(SslCertificate $certificate): ?int
    {
        try {
            $rawFields = $certificate->getRawCertificateFields();
            // Try to get chain length from peer_certificate_chain if available
            return isset($rawFields['peer_certificate_chain'])
                ? count($rawFields['peer_certificate_chain'])
                : 1; // Default to 1 for the certificate itself
        } catch (Exception $e) {
            return null;
        }
    }

    private function extractProtocolVersion(SslCertificate $certificate): ?string
    {
        try {
            $rawFields = $certificate->getRawCertificateFields();
            return $rawFields['protocol_version'] ?? $rawFields['version'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function extractCipherSuite(SslCertificate $certificate): ?string
    {
        try {
            $rawFields = $certificate->getRawCertificateFields();
            return $rawFields['cipher_suite'] ?? $rawFields['cipher'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function extractKeySize(SslCertificate $certificate): ?int
    {
        try {
            $rawFields = $certificate->getRawCertificateFields();

            // Try various ways to get key size
            if (isset($rawFields['publickey_bits'])) {
                return (int) $rawFields['publickey_bits'];
            }

            if (isset($rawFields['rsa_bits'])) {
                return (int) $rawFields['rsa_bits'];
            }

            // Try to extract from public key info
            if (isset($rawFields['public_key'])) {
                $publicKeyDetails = openssl_pkey_get_details(openssl_pkey_get_public($rawFields['public_key']));
                return $publicKeyDetails['bits'] ?? null;
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function extractOcspStatus(SslCertificate $certificate): ?string
    {
        try {
            $rawFields = $certificate->getRawCertificateFields();

            // Check for OCSP status information
            if (isset($rawFields['ocsp_status'])) {
                return $rawFields['ocsp_status'];
            }

            // Check for OCSP extension
            if (isset($rawFields['extensions']['authorityInfoAccess'])) {
                $aia = $rawFields['extensions']['authorityInfoAccess'];
                if (str_contains($aia, 'OCSP')) {
                    return 'available';
                }
            }

            return 'unknown';
        } catch (Exception $e) {
            return null;
        }
    }

    private function extractHostFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? $url;
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