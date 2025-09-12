<?php

namespace App\Services;

use App\Models\DowntimeIncident;
use App\Models\UptimeCheck;
use App\Models\Website;
use Carbon\Carbon;

class UptimeStatusCalculator
{
    public function calculateStatus(Website $website): string
    {
        $latestCheck = $website->uptimeChecks()
            ->latest('checked_at')
            ->first();

        if (! $latestCheck) {
            return 'unknown';
        }

        // Consider checks older than 1 hour as stale
        $staleThreshold = Carbon::now()->subHour();
        if ($latestCheck->checked_at->lt($staleThreshold)) {
            return 'unknown';
        }

        return $latestCheck->status;
    }

    public function calculateUptimePercentage(Website $website, int $days = 30): float
    {
        $startDate = Carbon::now()->subDays($days);

        $totalChecks = $website->uptimeChecks()
            ->where('checked_at', '>=', $startDate)
            ->count();

        if ($totalChecks === 0) {
            return 0.0;
        }

        $upChecks = $website->uptimeChecks()
            ->where('checked_at', '>=', $startDate)
            ->where('status', 'up')
            ->count();

        return round(($upChecks / $totalChecks) * 100, 1);
    }

    public function detectDowntimeIncident(Website $website): ?DowntimeIncident
    {
        $latestChecks = $website->uptimeChecks()
            ->latest('checked_at')
            ->limit(2)
            ->get();

        if ($latestChecks->count() < 2) {
            return null;
        }

        $currentCheck = $latestChecks[0];
        $previousCheck = $latestChecks[1];

        $isCurrentDown = in_array($currentCheck->status, ['down', 'slow', 'content_mismatch']);
        $wasPreviousUp = $previousCheck->status === 'up';

        // Check for existing ongoing incident
        $ongoingIncident = $website->downtimeIncidents()
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        // If currently up and there's an ongoing incident, resolve it
        if ($currentCheck->status === 'up' && $ongoingIncident) {
            $ongoingIncident->resolve(true);

            return null;
        }

        // If currently down
        if ($isCurrentDown) {
            // If there's already an ongoing incident, return it (continue existing)
            if ($ongoingIncident) {
                return $ongoingIncident;
            }

            // If transitioning from up to down, create new incident
            if ($wasPreviousUp) {
                return $this->createDowntimeIncident($website, $currentCheck);
            }
        }

        return null;
    }

    private function createDowntimeIncident(Website $website, UptimeCheck $check): DowntimeIncident
    {
        $incidentType = match ($check->status) {
            'content_mismatch' => 'content_mismatch',
            'slow' => 'timeout',
            default => 'http_error',
        };

        return DowntimeIncident::create([
            'website_id' => $website->id,
            'started_at' => $check->checked_at,
            'incident_type' => $incidentType,
            'error_details' => $check->error_message ?? $check->content_check_error,
        ]);
    }
}
