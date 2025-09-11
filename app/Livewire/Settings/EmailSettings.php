<?php

namespace App\Livewire\Settings;

use App\Models\EmailSettings as EmailSettingsModel;
use Livewire\Component;

class EmailSettings extends Component
{
    // Form fields
    public string $host = '';
    public int $port = 587;
    public string $encryption = 'tls';
    public string $username = '';
    public string $password = '';
    public string $from_address = '';
    public string $from_name = 'SSL Monitor';
    public int $timeout = 30;
    public bool $verify_peer = true;

    // UI state
    public bool $isEditing = false;
    public bool $isTesting = false;
    public string $testResult = '';
    public bool $showPassword = false;

    protected array $rules = [
        'host' => 'required|string|max:255',
        'port' => 'required|integer|min:1|max:65535',
        'encryption' => 'nullable|in:tls,ssl',
        'username' => 'nullable|string|max:255',
        'password' => 'nullable|string',
        'from_address' => 'required|email|max:255',
        'from_name' => 'required|string|max:255',
        'timeout' => 'required|integer|min:5|max:300',
        'verify_peer' => 'boolean',
    ];

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        $settings = EmailSettingsModel::active();
        
        if ($settings) {
            $this->host = $settings->host;
            $this->port = $settings->port;
            $this->encryption = $settings->encryption ?? '';
            $this->username = $settings->username ?? '';
            $this->password = ''; // Don't show existing password
            $this->from_address = $settings->from_address;
            $this->from_name = $settings->from_name;
            $this->timeout = $settings->timeout;
            $this->verify_peer = $settings->verify_peer;
        }
    }

    public function startEditing(): void
    {
        $this->isEditing = true;
        $this->testResult = '';
    }

    public function cancelEditing(): void
    {
        $this->isEditing = false;
        $this->loadSettings();
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate();

        try {
            // Deactivate existing settings
            EmailSettingsModel::where('is_active', true)->update(['is_active' => false]);

            // Create new settings
            $settings = EmailSettingsModel::create([
                'host' => $this->host,
                'port' => $this->port,
                'encryption' => $this->encryption ?: null,
                'username' => $this->username ?: null,
                'password' => $this->password ?: null,
                'from_address' => $this->from_address,
                'from_name' => $this->from_name,
                'timeout' => $this->timeout,
                'verify_peer' => $this->verify_peer,
                'is_active' => true,
            ]);

            $this->isEditing = false;
            $this->password = ''; // Clear password field
            
            session()->flash('success', 'Email settings saved successfully! You can now test the configuration.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save email settings: ' . $e->getMessage());
        }
    }

    public function testEmail(): void
    {
        $this->isTesting = true;
        $this->testResult = '';

        $settings = EmailSettingsModel::active();
        
        if (!$settings) {
            $this->testResult = 'error:No active email configuration found. Please save your settings first.';
            $this->isTesting = false;
            return;
        }

        try {
            $success = $settings->test();
            
            if ($success) {
                $this->testResult = 'success:Test email sent successfully! Check your inbox at ' . $settings->from_address;
            } else {
                $this->testResult = 'error:Test failed: ' . ($settings->test_error ?? 'Unknown error');
            }
            
        } catch (\Exception $e) {
            $this->testResult = 'error:Test failed: ' . $e->getMessage();
        }

        $this->isTesting = false;
    }

    public function togglePasswordVisibility(): void
    {
        $this->showPassword = !$this->showPassword;
    }

    public function render()
    {
        return view('livewire.settings.email-settings', [
            'hasSettings' => EmailSettingsModel::active() !== null,
            'currentSettings' => EmailSettingsModel::active(),
        ]);
    }
}
