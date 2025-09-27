<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutomationLogger
{
    /**
     * Log queue-related activities
     */
    public static function queue(string $message, array $context = [], string $level = 'info'): void
    {
        Log::channel('queue')->{$level}("[QUEUE] {$message}", $context);
    }

    /**
     * Log immediate check activities
     */
    public static function immediateCheck(string $message, array $context = [], string $level = 'info'): void
    {
        Log::channel('immediate-checks')->{$level}("[IMMEDIATE] {$message}", $context);
    }

    /**
     * Log scheduler activities
     */
    public static function scheduler(string $message, array $context = [], string $level = 'info'): void
    {
        Log::channel('scheduler')->{$level}("[SCHEDULER] {$message}", $context);
    }

    /**
     * Log SSL monitoring activities
     */
    public static function ssl(string $message, array $context = [], string $level = 'info'): void
    {
        Log::channel('ssl-monitoring')->{$level}("[SSL] {$message}", $context);
    }

    /**
     * Log uptime monitoring activities
     */
    public static function uptime(string $message, array $context = [], string $level = 'info'): void
    {
        Log::channel('uptime-monitoring')->{$level}("[UPTIME] {$message}", $context);
    }

    /**
     * Log critical errors
     */
    public static function error(string $message, array $context = [], ?\Throwable $exception = null): void
    {
        $context = array_merge($context, [
            'timestamp' => Carbon::now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ]);

        if ($exception) {
            $context['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        Log::channel('errors')->error("[ERROR] {$message}", $context);

        // Also log to main channel for visibility
        Log::error("[AUTOMATION ERROR] {$message}", $context);
    }

    /**
     * Log job performance metrics
     */
    public static function performance(string $jobName, float $executionTime, array $metrics = []): void
    {
        $context = array_merge([
            'job' => $jobName,
            'execution_time_ms' => round($executionTime * 1000, 2),
            'memory_usage' => memory_get_usage(true),
            'timestamp' => Carbon::now()->toISOString(),
        ], $metrics);

        Log::channel('queue')->info("[PERFORMANCE] Job completed", $context);
    }

    /**
     * Log job start with context
     */
    public static function jobStart(string $jobClass, array $context = []): void
    {
        $message = "Starting job: {$jobClass}";
        $context['started_at'] = Carbon::now()->toISOString();

        self::queue($message, $context);
    }

    /**
     * Log job completion with metrics
     */
    public static function jobComplete(string $jobClass, float $startTime, array $context = []): void
    {
        $executionTime = microtime(true) - $startTime;
        $message = "Completed job: {$jobClass}";

        $context = array_merge($context, [
            'completed_at' => Carbon::now()->toISOString(),
            'execution_time_ms' => round($executionTime * 1000, 2),
        ]);

        self::queue($message, $context);
        self::performance($jobClass, $executionTime, $context);
    }

    /**
     * Log job failure with detailed context
     */
    public static function jobFailed(string $jobClass, \Throwable $exception, array $context = []): void
    {
        $message = "Job failed: {$jobClass}";

        $context = array_merge($context, [
            'failed_at' => Carbon::now()->toISOString(),
            'error_message' => $exception->getMessage(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
        ]);

        self::error($message, $context, $exception);
    }

    /**
     * Log website check details
     */
    public static function websiteCheck(string $url, string $checkType, array $result): void
    {
        $message = "Website check completed: {$url} ({$checkType})";
        $context = [
            'url' => $url,
            'check_type' => $checkType,
            'result' => $result,
            'checked_at' => Carbon::now()->toISOString(),
        ];

        if ($checkType === 'ssl') {
            self::ssl($message, $context);
        } else {
            self::uptime($message, $context);
        }
    }

    /**
     * Debug helper for development
     */
    public static function debug(string $message, array $context = []): void
    {
        if (config('app.debug')) {
            Log::debug("[AUTOMATION DEBUG] {$message}", $context);
        }
    }
}