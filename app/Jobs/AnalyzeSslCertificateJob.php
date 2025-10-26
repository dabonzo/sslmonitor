<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\SslCertificateAnalysisService;
use App\Support\AutomationLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeSslCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Website $website
    ) {}

    public function handle(SslCertificateAnalysisService $service): void
    {
        try {
            AutomationLogger::info("Starting SSL certificate analysis for: {$this->website->url}", [
                'website_id' => $this->website->id,
            ]);

            $service->analyzeAndSave($this->website);

            AutomationLogger::info("Completed SSL certificate analysis for: {$this->website->url}", [
                'website_id' => $this->website->id,
            ]);

        } catch (\Throwable $exception) {
            AutomationLogger::error(
                "Failed to analyze SSL certificate for: {$this->website->url}",
                ['website_id' => $this->website->id],
                $exception
            );

            throw $exception;
        }
    }

    public function retryUntil(): \Carbon\Carbon
    {
        return now()->addMinutes(5);
    }

    public function failed(\Throwable $exception): void
    {
        AutomationLogger::jobFailed(self::class, $exception, [
            'website_id' => $this->website->id,
            'website_url' => $this->website->url,
        ]);
    }
}
