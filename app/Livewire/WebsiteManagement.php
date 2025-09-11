<?php

namespace App\Livewire;

use App\Jobs\CheckSslCertificateJob;
use App\Models\Website;
use App\Services\SslCertificateChecker;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Validate;
use Livewire\Component;

class WebsiteManagement extends Component
{
    use AuthorizesRequests;

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|url|max:255')]
    public $url = '';

    public $editingWebsiteId = null;

    public $certificatePreview = null;

    public $isCheckingCertificate = false;

    protected SslCertificateChecker $sslChecker;

    public function boot()
    {
        $this->sslChecker = new SslCertificateChecker;
    }

    public function updatedUrl($value)
    {
        // Auto-add https:// if no protocol is specified
        if ($value && ! str_starts_with($value, 'http://') && ! str_starts_with($value, 'https://')) {
            $this->url = 'https://'.$value;
        }
    }

    public function getFormProperty()
    {
        return (object) [
            'name' => $this->name,
            'url' => $this->url,
        ];
    }

    public function save()
    {
        $this->validate();

        if ($this->editingWebsiteId) {
            $this->updateWebsite();
        } else {
            $this->createWebsite();
        }
    }

    protected function createWebsite()
    {
        $user = auth()->user();
        $team = $user->primaryTeam();

        // Create website data
        $websiteData = [
            'name' => $this->name,
            'url' => $this->url,
        ];

        // If user has a team, add to team; otherwise keep personal
        if ($team) {
            $websiteData['team_id'] = $team->id;
            $websiteData['added_by'] = $user->id;
        }

        $website = $user->websites()->create($websiteData);

        // Queue SSL certificate check immediately for new website
        CheckSslCertificateJob::dispatch($website);

        $this->dispatch('website-added');
        $this->resetForm();
    }

    protected function updateWebsite()
    {
        $website = Website::findOrFail($this->editingWebsiteId);
        $this->authorize('update', $website);

        $website->update([
            'name' => $this->name,
            'url' => $this->url,
        ]);

        $this->dispatch('website-updated');
        $this->resetForm();
    }

    public function edit($websiteId)
    {
        $website = Website::findOrFail($websiteId);
        $this->authorize('update', $website);

        $this->editingWebsiteId = $websiteId;
        $this->name = $website->name;
        $this->url = $website->url;
        $this->certificatePreview = null;
    }

    public function delete($websiteId)
    {
        $website = Website::findOrFail($websiteId);
        $this->authorize('delete', $website);

        $website->delete();
        $this->dispatch('website-deleted');
    }

    public function checkCertificate()
    {
        if (! $this->url) {
            return;
        }

        $this->isCheckingCertificate = true;

        try {
            // Create a temporary website model for checking
            $tempWebsite = new Website(['url' => $this->url]);
            $this->certificatePreview = $this->sslChecker->checkCertificate($tempWebsite);
        } catch (\Exception $e) {
            $this->certificatePreview = [
                'status' => 'error',
                'error_message' => 'Failed to check certificate: '.$e->getMessage(),
                'expires_at' => null,
                'issuer' => null,
                'subject' => null,
                'is_valid' => false,
                'days_until_expiry' => null,
            ];
        }

        $this->isCheckingCertificate = false;
    }

    public function resetForm()
    {
        $this->reset(['name', 'url', 'editingWebsiteId', 'certificatePreview']);
    }

    public function render()
    {
        $user = auth()->user();
        $websites = $user->accessibleWebsitesQuery()->with(['team', 'addedBy'])->latest()->get();
        $team = $user->primaryTeam();

        return view('livewire.website-management', compact('websites', 'team'));
    }
}
