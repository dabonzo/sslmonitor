<?php

namespace App\Services;

use Carbon\Carbon;

class SslCertificateAnalysisService
{
    public function analyzeCertificate(string $url): array
    {
        try {
            $parsedUrl = parse_url($url);
            $host = $parsedUrl['host'] ?? $url;
            $port = $parsedUrl['port'] ?? 443;

            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $socket = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (! $socket) {
                return $this->getErrorAnalysis("Connection failed: {$errstr}");
            }

            $cert = stream_context_get_params($socket)['options']['ssl']['peer_certificate'];
            fclose($socket);

            if (! $cert) {
                return $this->getErrorAnalysis('No certificate found');
            }

            return $this->parseCertificateDetails($cert, $host);

        } catch (\Exception $e) {
            return $this->getErrorAnalysis("Analysis failed: {$e->getMessage()}");
        }
    }

    private function parseCertificateDetails($cert, string $host): array
    {
        $certInfo = openssl_x509_parse($cert);
        $certDetails = openssl_x509_read($cert);

        // Extract certificate chain information
        $chainInfo = $this->analyzeCertificateChain($cert);

        // Security analysis
        $securityAnalysis = $this->performSecurityAnalysis($certInfo, $certDetails);

        // Let's Encrypt detection
        $isLetsEncrypt = $this->detectLetsEncrypt($certInfo);

        // Expiry analysis
        $expiryAnalysis = $this->analyzeExpiry($certInfo);

        return [
            'basic_info' => [
                'subject' => $certInfo['subject']['CN'] ?? 'Unknown',
                'issuer' => $certInfo['issuer']['CN'] ?? 'Unknown',
                'issuer_organization' => $certInfo['issuer']['O'] ?? 'Unknown',
                'serial_number' => $this->formatSerialNumber($certInfo['serialNumber'] ?? ''),
                'signature_algorithm' => $certInfo['signatureTypeSN'] ?? 'Unknown',
                'version' => $certInfo['version'] ?? 1,
            ],
            'validity' => [
                'valid_from' => Carbon::createFromTimestamp($certInfo['validFrom_time_t'])->toISOString(),
                'valid_until' => Carbon::createFromTimestamp($certInfo['validTo_time_t'])->toISOString(),
                'days_remaining' => $expiryAnalysis['days_remaining'],
                'is_expired' => $expiryAnalysis['is_expired'],
                'expires_soon' => $expiryAnalysis['expires_soon'],
            ],
            'domains' => [
                'primary_domain' => $certInfo['subject']['CN'] ?? '',
                'subject_alt_names' => $this->extractSANs($certInfo),
                'wildcard_cert' => $this->isWildcardCertificate($certInfo),
                'covers_requested_domain' => $this->domainCoverage($certInfo, $host),
            ],
            'security' => $securityAnalysis,
            'certificate_authority' => [
                'is_lets_encrypt' => $isLetsEncrypt,
                'ca_name' => $certInfo['issuer']['CN'] ?? 'Unknown',
                'ca_organization' => $certInfo['issuer']['O'] ?? 'Unknown',
                'ca_country' => $certInfo['issuer']['C'] ?? '',
            ],
            'chain_info' => $chainInfo,
            'risk_assessment' => $this->assessRisk($certInfo, $securityAnalysis, $expiryAnalysis, $isLetsEncrypt),
        ];
    }

    private function analyzeCertificateChain($cert): array
    {
        // Basic chain analysis - in a full implementation, this would analyze the full chain
        return [
            'length' => 1, // Simplified - would need full chain
            'trusted_root' => true, // Simplified
            'intermediate_cas' => [],
            'chain_valid' => true,
        ];
    }

    private function performSecurityAnalysis(array $certInfo, $certDetails): array
    {
        $keySize = null;
        $keyType = 'Unknown';

        // Extract public key information
        $publicKey = openssl_pkey_get_public($certDetails);
        if ($publicKey) {
            $keyDetails = openssl_pkey_get_details($publicKey);
            $keySize = $keyDetails['bits'] ?? null;
            $keyType = $this->getKeyType($keyDetails['type'] ?? null);
        }

        return [
            'key_algorithm' => $keyType,
            'key_size' => $keySize,
            'signature_algorithm' => $certInfo['signatureTypeSN'] ?? 'Unknown',
            'weak_signature' => $this->isWeakSignature($certInfo['signatureTypeSN'] ?? ''),
            'weak_key' => $this->isWeakKey($keyType, $keySize),
            'security_score' => $this->calculateSecurityScore($keyType, $keySize, $certInfo['signatureTypeSN'] ?? ''),
        ];
    }

    private function detectLetsEncrypt(array $certInfo): bool
    {
        $issuer = strtolower($certInfo['issuer']['CN'] ?? '');
        $org = strtolower($certInfo['issuer']['O'] ?? '');

        return str_contains($issuer, "let's encrypt") ||
               str_contains($org, "let's encrypt") ||
               str_contains($issuer, 'r3') || // Let's Encrypt R3 intermediate
               str_contains($issuer, 'e1'); // Let's Encrypt E1 intermediate
    }

    private function analyzeExpiry(array $certInfo): array
    {
        $expiryTime = $certInfo['validTo_time_t'];
        $now = time();
        $daysRemaining = (int) (($expiryTime - $now) / 86400);

        return [
            'days_remaining' => max(0, $daysRemaining),
            'is_expired' => $daysRemaining < 0,
            'expires_soon' => $daysRemaining <= 30,
            'critical_expiry' => $daysRemaining <= 7,
        ];
    }

    private function extractSANs(array $certInfo): array
    {
        $sans = [];
        if (isset($certInfo['extensions']['subjectAltName'])) {
            $sanString = $certInfo['extensions']['subjectAltName'];
            $parts = explode(',', $sanString);
            foreach ($parts as $part) {
                if (str_starts_with(trim($part), 'DNS:')) {
                    $sans[] = trim(substr(trim($part), 4));
                }
            }
        }

        return $sans;
    }

    private function isWildcardCertificate(array $certInfo): bool
    {
        $cn = $certInfo['subject']['CN'] ?? '';

        return str_starts_with($cn, '*.');
    }

    private function domainCoverage(array $certInfo, string $requestedDomain): bool
    {
        $cn = $certInfo['subject']['CN'] ?? '';
        $sans = $this->extractSANs($certInfo);

        $domains = array_merge([$cn], $sans);

        foreach ($domains as $domain) {
            if ($domain === $requestedDomain) {
                return true;
            }
            if (str_starts_with($domain, '*.')) {
                $wildcardDomain = substr($domain, 2);
                if (str_ends_with($requestedDomain, '.'.$wildcardDomain) || $requestedDomain === $wildcardDomain) {
                    return true;
                }
            }
        }

        return false;
    }

    private function formatSerialNumber(string $serial): string
    {
        return strtoupper(chunk_split($serial, 2, ':'));
    }

    private function getKeyType(?int $type): string
    {
        return match ($type) {
            OPENSSL_KEYTYPE_RSA => 'RSA',
            OPENSSL_KEYTYPE_DSA => 'DSA',
            OPENSSL_KEYTYPE_EC => 'EC',
            default => 'Unknown'
        };
    }

    private function isWeakSignature(string $algorithm): bool
    {
        $weakAlgorithms = ['md5', 'sha1'];

        return in_array(strtolower($algorithm), $weakAlgorithms);
    }

    private function isWeakKey(string $keyType, ?int $keySize): bool
    {
        if ($keyType === 'RSA' && $keySize && $keySize < 2048) {
            return true;
        }
        if ($keyType === 'EC' && $keySize && $keySize < 256) {
            return true;
        }

        return false;
    }

    private function calculateSecurityScore(string $keyType, ?int $keySize, string $signatureAlg): int
    {
        $score = 100;

        // Deduct for weak key
        if ($this->isWeakKey($keyType, $keySize)) {
            $score -= 30;
        }

        // Deduct for weak signature
        if ($this->isWeakSignature($signatureAlg)) {
            $score -= 40;
        }

        // Deduct for unknown algorithms
        if ($keyType === 'Unknown') {
            $score -= 20;
        }

        return max(0, $score);
    }

    private function assessRisk(array $certInfo, array $securityAnalysis, array $expiryAnalysis, bool $isLetsEncrypt): array
    {
        $risks = [];
        $riskLevel = 'low';

        if ($expiryAnalysis['is_expired']) {
            $risks[] = 'Certificate has expired';
            $riskLevel = 'critical';
        } elseif ($expiryAnalysis['critical_expiry']) {
            $risks[] = 'Certificate expires within 7 days';
            $riskLevel = 'high';
        } elseif ($expiryAnalysis['expires_soon']) {
            $risks[] = 'Certificate expires within 30 days';
            if ($riskLevel === 'low') {
                $riskLevel = 'medium';
            }
        }

        if ($securityAnalysis['weak_key']) {
            $risks[] = 'Weak encryption key detected';
            $riskLevel = 'high';
        }

        if ($securityAnalysis['weak_signature']) {
            $risks[] = 'Weak signature algorithm detected';
            $riskLevel = 'high';
        }

        if ($securityAnalysis['security_score'] < 70) {
            $risks[] = 'Low security score';
            if ($riskLevel === 'low') {
                $riskLevel = 'medium';
            }
        }

        // Let's Encrypt specific recommendations
        if ($isLetsEncrypt && $expiryAnalysis['days_remaining'] <= 30) {
            $risks[] = 'Let\'s Encrypt certificate should auto-renew soon';
        }

        return [
            'level' => $riskLevel,
            'score' => $securityAnalysis['security_score'],
            'issues' => $risks,
            'recommendations' => $this->getRecommendations($risks, $isLetsEncrypt),
        ];
    }

    private function getRecommendations(array $risks, bool $isLetsEncrypt): array
    {
        $recommendations = [];

        if (count($risks) === 0) {
            $recommendations[] = 'Certificate configuration is secure';
        }

        foreach ($risks as $risk) {
            if (str_contains($risk, 'expires')) {
                if ($isLetsEncrypt) {
                    $recommendations[] = 'Verify Let\'s Encrypt auto-renewal is configured';
                } else {
                    $recommendations[] = 'Schedule certificate renewal immediately';
                }
            }

            if (str_contains($risk, 'Weak')) {
                $recommendations[] = 'Upgrade to stronger encryption (RSA 2048+ or EC 256+)';
            }
        }

        return array_unique($recommendations);
    }

    private function getErrorAnalysis(string $error): array
    {
        return [
            'error' => true,
            'message' => $error,
            'basic_info' => null,
            'validity' => null,
            'domains' => null,
            'security' => null,
            'certificate_authority' => null,
            'chain_info' => null,
            'risk_assessment' => [
                'level' => 'critical',
                'score' => 0,
                'issues' => ['Certificate analysis failed: '.$error],
                'recommendations' => ['Verify website URL and SSL configuration'],
            ],
        ];
    }

    /**
     * Analyze certificate and save complete data to website record.
     */
    public function analyzeAndSave(\App\Models\Website $website): array
    {
        try {
            // Run existing analysis
            $analysis = $this->analyzeCertificate($website->url);

            // Handle error cases
            if (isset($analysis['error']) && $analysis['error']) {
                \App\Support\AutomationLogger::warning("SSL certificate analysis failed for website: {$website->url}", [
                    'website_id' => $website->id,
                    'error' => $analysis['message'] ?? 'Unknown error',
                ]);

                return $analysis;
            }

            // Extract the complete certificate data structure
            $certificateData = [
                // Basic Info
                'subject' => $analysis['basic_info']['subject'] ?? null,
                'issuer' => $analysis['basic_info']['issuer'] ?? null,
                'serial_number' => $analysis['basic_info']['serial_number'] ?? null,
                'signature_algorithm' => $analysis['basic_info']['signature_algorithm'] ?? null,

                // Validity
                'valid_from' => $analysis['validity']['valid_from'] ?? null,
                'valid_until' => $analysis['validity']['valid_until'] ?? null,
                'days_remaining' => $analysis['validity']['days_remaining'] ?? null,
                'is_expired' => $analysis['validity']['is_expired'] ?? false,
                'expires_soon' => $analysis['validity']['expires_soon'] ?? false,

                // Security
                'key_algorithm' => $analysis['security']['key_algorithm'] ?? null,
                'key_size' => $analysis['security']['key_size'] ?? null,
                'security_score' => $analysis['security']['security_score'] ?? null,
                'risk_level' => $analysis['risk_assessment']['level'] ?? null,

                // Domains
                'primary_domain' => $analysis['domains']['primary_domain'] ?? null,
                'subject_alt_names' => $analysis['domains']['subject_alt_names'] ?? [],
                'covers_www' => $analysis['domains']['covers_requested_domain'] ?? false,
                'is_wildcard' => $analysis['domains']['wildcard_cert'] ?? false,

                // Chain
                'chain_length' => $analysis['chain_info']['length'] ?? 0,
                'chain_complete' => $analysis['chain_info']['chain_valid'] ?? false,
                'intermediate_issuers' => $analysis['chain_info']['intermediate_cas'] ?? [],

                // Metadata
                'status' => 'success',
                'analyzed_at' => now()->toIso8601String(),
            ];

            // Save to website record
            $website->update([
                'latest_ssl_certificate' => $certificateData,
                'ssl_certificate_analyzed_at' => now(),
            ]);

            \App\Support\AutomationLogger::info("Saved SSL certificate data for website: {$website->url}", [
                'website_id' => $website->id,
                'subject' => $certificateData['subject'],
                'days_remaining' => $certificateData['days_remaining'],
            ]);

            return $analysis;

        } catch (\Throwable $exception) {
            \App\Support\AutomationLogger::error(
                "Failed to analyze and save SSL certificate for website: {$website->url}",
                ['website_id' => $website->id],
                $exception
            );

            throw $exception;
        }
    }
}
