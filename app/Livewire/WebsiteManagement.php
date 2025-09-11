<?php

namespace App\Livewire;

use App\Http\Requests\StoreWebsiteRequest;
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
        $this->sslChecker = new SslCertificateChecker();
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
        auth()->user()->websites()->create([
            'name' => $this->name,
            'url' => $this->url,
        ]);

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
        if (!$this->url) {
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
                'error_message' => 'Failed to check certificate: ' . $e->getMessage(),
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
        $websites = auth()->user()->websites()->latest()->get();

        return view('livewire.website-management', compact('websites'));
    }
}
