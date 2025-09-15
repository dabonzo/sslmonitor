<?php

namespace App\Livewire;

use App\Jobs\CheckSslCertificateJob;
use App\Jobs\CheckWebsiteUptimeJob;
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

    public $uptime_monitoring = false;

    public $javascript_enabled = false;

    #[Validate('nullable|integer|min:100|max:599')]
    public $expected_status_code = 200;

    #[Validate('nullable|string|max:500')]
    public $expected_content = '';

    #[Validate('nullable|string|max:500')]
    public $forbidden_content = '';

    #[Validate('nullable|integer|min:1000|max:120000')]
    public $max_response_time = 30000;

    public $follow_redirects = true;

    #[Validate('nullable|integer|min:1|max:10')]
    public $max_redirects = 3;

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
            'uptime_monitoring' => $this->uptime_monitoring,
            'expected_status_code' => $this->expected_status_code,
            'expected_content' => $this->expected_content,
            'forbidden_content' => $this->forbidden_content,
            'max_response_time' => $this->max_response_time,
            'follow_redirects' => $this->follow_redirects,
            'max_redirects' => $this->max_redirects,
        ];
    }

    public function save()
    {
        $this->validateForm();

        if ($this->editingWebsiteId) {
            $this->updateWebsite();
        } else {
            $this->createWebsite();
        }
    }

    protected function validateForm()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'uptime_monitoring' => 'boolean',
            'javascript_enabled' => 'boolean',
        ];

        if ($this->uptime_monitoring) {
            $rules['expected_status_code'] = 'required|integer|min:100|max:599';
            $rules['expected_content'] = 'nullable|string|max:500';
            $rules['forbidden_content'] = 'nullable|string|max:500';
            $rules['max_response_time'] = 'required|integer|min:1000|max:120000';
            $rules['follow_redirects'] = 'boolean';

            if ($this->follow_redirects) {
                $rules['max_redirects'] = 'required|integer|min:1|max:10';
            }
        }

        $this->validate($rules);
    }

    protected function createWebsite()
    {
        $user = auth()->user();
        $team = $user->primaryTeam();

        // Create website data
        $websiteData = [
            'name' => $this->name,
            'url' => $this->url,
            'uptime_monitoring' => $this->uptime_monitoring,
            'javascript_enabled' => $this->javascript_enabled,
            'expected_status_code' => $this->expected_status_code,
            'expected_content' => $this->expected_content,
            'forbidden_content' => $this->forbidden_content,
            'max_response_time' => $this->max_response_time,
            'follow_redirects' => $this->follow_redirects,
            'max_redirects' => $this->max_redirects,
        ];

        // If user has a team, add to team; otherwise keep personal
        if ($team) {
            $websiteData['team_id'] = $team->id;
            $websiteData['added_by'] = $user->id;
        }

        $website = $user->websites()->create($websiteData);

        // Queue SSL certificate check immediately for new website (force immediate execution)
        CheckSslCertificateJob::dispatch($website, true);

        // Queue uptime check immediately for new website if uptime monitoring is enabled
        if ($website->uptime_monitoring) {
            CheckWebsiteUptimeJob::dispatch($website, true);
            $this->dispatch('uptime-check-queued');
        }

        $this->dispatch('website-added');
        $this->resetForm();
    }

    protected function updateWebsite()
    {
        $website = Website::findOrFail($this->editingWebsiteId);
        $this->authorize('update', $website);

        $previousUptimeMonitoring = $website->uptime_monitoring;

        $website->update([
            'name' => $this->name,
            'url' => $this->url,
            'uptime_monitoring' => $this->uptime_monitoring,
            'javascript_enabled' => $this->javascript_enabled,
            'expected_status_code' => $this->expected_status_code,
            'expected_content' => $this->expected_content,
            'forbidden_content' => $this->forbidden_content,
            'max_response_time' => $this->max_response_time,
            'follow_redirects' => $this->follow_redirects,
            'max_redirects' => $this->max_redirects,
        ]);

        // Queue uptime check immediately if uptime monitoring was just enabled or settings changed
        if ($this->uptime_monitoring && (! $previousUptimeMonitoring || $website->wasChanged(['javascript_enabled', 'expected_content', 'forbidden_content']))) {
            CheckWebsiteUptimeJob::dispatch($website, true);
            $this->dispatch('uptime-check-queued');
        }

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
        $this->uptime_monitoring = $website->uptime_monitoring;
        $this->javascript_enabled = $website->javascript_enabled;
        $this->expected_status_code = $website->expected_status_code;
        $this->expected_content = $website->expected_content;
        $this->forbidden_content = $website->forbidden_content;
        $this->max_response_time = $website->max_response_time;
        $this->follow_redirects = $website->follow_redirects;
        $this->max_redirects = $website->max_redirects;
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

    public function checkUptime($websiteId)
    {
        $website = Website::findOrFail($websiteId);
        $this->authorize('update', $website);

        if (! $website->uptime_monitoring) {
            return;
        }

        // Queue uptime check job (force immediate execution)
        CheckWebsiteUptimeJob::dispatch($website, true);

        $this->dispatch('uptime-check-queued');
    }

    public function resetForm()
    {
        $this->reset([
            'name', 'url', 'editingWebsiteId', 'certificatePreview',
            'uptime_monitoring', 'expected_status_code', 'expected_content',
            'forbidden_content', 'max_response_time', 'follow_redirects', 'max_redirects',
        ]);

        // Reset to defaults
        $this->uptime_monitoring = false;
        $this->expected_status_code = 200;
        $this->max_response_time = 30000;
        $this->follow_redirects = true;
        $this->max_redirects = 3;
    }

    public function render()
    {
        $user = auth()->user();
        $websites = $user->accessibleWebsitesQuery()->with(['team', 'addedBy'])->latest()->get();
        $team = $user->primaryTeam();

        return view('livewire.website-management', compact('websites', 'team'));
    }
}
