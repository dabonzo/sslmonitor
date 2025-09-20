<?php

namespace App\Livewire;

use App\Jobs\CheckSslCertificateJob;
use App\Models\Website;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class WebsiteDetails extends Component
{
    use AuthorizesRequests, WithPagination;

    public Website $website;

    public bool $isCheckingNow = false;

    public function mount(Website $website): void
    {
        $this->authorize('view', $website);
        $this->website = $website;
    }

    public function getSslChecksProperty(): Collection
    {
        return $this->website->sslChecks()
            ->latest('checked_at')
            ->limit(20)
            ->get();
    }

    public function getLatestSslCheckProperty()
    {
        return $this->website->sslChecks()
            ->latest('checked_at')
            ->first();
    }

    public function checkSslCertificate(): void
    {
        $this->isCheckingNow = true;

        try {
            // Check if we've checked recently
            $recentCheck = $this->website->sslChecks()
                ->where('checked_at', '>', now()->subHour())
                ->latest('checked_at')
                ->first();

            if ($recentCheck) {
                session()->flash('info', 'SSL certificate was checked recently. Please wait before checking again.');
                $this->isCheckingNow = false;

                return;
            }

            CheckSslCertificateJob::dispatch($this->website);

            session()->flash('success', 'SSL check queued successfully! Results will appear shortly.');
            $this->dispatch('ssl-check-triggered');

            // Start polling for updates
            $this->dispatch('start-polling');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to queue SSL check: '.$e->getMessage());
            $this->isCheckingNow = false;
        }
    }

    public function refreshData(): void
    {
        // Refresh the website model to get latest data
        $this->website = $this->website->fresh();
        $this->dispatch('data-refreshed');
    }

    public function pollForUpdates(): void
    {
        // Refresh the website model to get latest data
        $this->website = $this->website->fresh();

        // If we were checking and now have a new result, stop checking
        if ($this->isCheckingNow && $this->latestSslCheck && $this->latestSslCheck->checked_at->gt(now()->subMinutes(1))) {
            $this->isCheckingNow = false;
            $this->dispatch('stop-polling');
            $this->dispatch('ssl-check-completed');
        }
    }

    public function editWebsite(): void
    {
        $this->authorize('update', $this->website);
        $this->redirectRoute('websites', ['edit' => $this->website->id]);
    }

    public function deleteWebsite(): void
    {
        $this->authorize('delete', $this->website);

        $websiteName = $this->website->name;
        $this->website->delete();

        session()->flash('success', "Website '{$websiteName}' has been deleted successfully.");
        $this->redirectRoute('websites');
    }

    public function getStatusColorProperty(): string
    {
        $latestCheck = $this->latestSslCheck;

        if (! $latestCheck) {
            return 'gray';
        }

        return match ($latestCheck->status) {
            'valid' => 'green',
            'expiring_soon' => 'yellow',
            'expired' => 'red',
            'error' => 'red',
            default => 'gray',
        };
    }

    public function getStatusTextProperty(): string
    {
        $latestCheck = $this->latestSslCheck;

        if (! $latestCheck) {
            return 'Not checked yet';
        }

        return match ($latestCheck->status) {
            'valid' => 'Valid',
            'expiring_soon' => 'Expiring Soon',
            'expired' => 'Certificate expired',
            'error' => 'Error',
            default => 'Unknown',
        };
    }

    public function getExpiryTextProperty(): ?string
    {
        $latestCheck = $this->latestSslCheck;

        if (! $latestCheck || ! $latestCheck->expires_at) {
            return null;
        }

        $daysUntilExpiry = $latestCheck->days_until_expiry;

        if ($daysUntilExpiry > 0) {
            return "{$daysUntilExpiry} days until expiry";
        } elseif ($daysUntilExpiry === 0) {
            return 'Expires today';
        } else {
            $daysExpired = abs($daysUntilExpiry);

            return "Expired {$daysExpired} days ago";
        }
    }

    public function render()
    {
        return view('livewire.website-details', [
            'sslChecks' => $this->sslChecks,
            'latestSslCheck' => $this->latestSslCheck,
            'statusColor' => $this->statusColor,
            'statusText' => $this->statusText,
            'expiryText' => $this->expiryText,
        ]);
    }
}
