<?php

namespace App\Services;

class UptimeCheckResult
{
    public function __construct(
        public string $status,
        public ?int $httpStatusCode = null,
        public ?int $responseTime = null,
        public ?int $responseSize = null,
        public ?bool $contentCheckPassed = null,
        public ?string $contentCheckError = null,
        public ?string $errorMessage = null,
        public ?string $finalUrl = null,
    ) {}

    public function isUp(): bool
    {
        return $this->status === 'up';
    }

    public function isDown(): bool
    {
        return $this->status === 'down';
    }

    public function isSlow(): bool
    {
        return $this->status === 'slow';
    }

    public function hasContentMismatch(): bool
    {
        return $this->status === 'content_mismatch';
    }
}