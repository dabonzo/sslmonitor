<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Email Configuration')" :subheading="__('Configure SMTP settings for SSL certificate notifications')">
        <div class="space-y-6">
    <!-- Header Actions -->
    @if($hasSettings && !$isEditing)
        <div class="flex items-center justify-end space-x-2">
            <flux:button wire:click="testEmail" variant="ghost" size="sm" icon="paper-airplane" wire:loading.attr="disabled" wire:target="testEmail">
                <span wire:loading.remove wire:target="testEmail">Test Email</span>
                <span wire:loading wire:target="testEmail">Testing...</span>
            </flux:button>
            
            <flux:button wire:click="startEditing" variant="primary" size="sm" icon="pencil">
                Edit Settings
            </flux:button>
        </div>
    @endif

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <!-- Test Result -->
    @if($testResult)
        @php
            $isSuccess = str_starts_with($testResult, 'success:');
            $message = substr($testResult, strpos($testResult, ':') + 1);
        @endphp
        
        <div class="p-4 border rounded-lg {{ $isSuccess ? 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400' : 'bg-red-50 border-red-200 text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400' }}">
            <div class="flex items-center">
                @if($isSuccess)
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                @else
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                @endif
                {{ $message }}
            </div>
        </div>
    @endif

    <!-- Current Settings Display (Read-only) -->
    @if($hasSettings && !$isEditing)
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4">Current Configuration</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-zinc-600 dark:text-zinc-400">SMTP Host</label>
                    <p class="text-sm text-zinc-900 dark:text-white">{{ $currentSettings->host }}</p>
                </div>
                
                <div>
                    <label class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Port & Encryption</label>
                    <p class="text-sm text-zinc-900 dark:text-white">{{ $currentSettings->port }} ({{ $currentSettings->encryption ?: 'None' }})</p>
                </div>
                
                <div>
                    <label class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Username</label>
                    <p class="text-sm text-zinc-900 dark:text-white">{{ $currentSettings->username ?: 'None' }}</p>
                </div>
                
                <div>
                    <label class="text-sm font-medium text-zinc-600 dark:text-zinc-400">From Address</label>
                    <p class="text-sm text-zinc-900 dark:text-white">{{ $currentSettings->from_address }} ({{ $currentSettings->from_name }})</p>
                </div>
            </div>

            @if($currentSettings->last_tested_at)
                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center text-sm {{ $currentSettings->test_passed ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        @if($currentSettings->test_passed)
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Last test successful
                        @else
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            Last test failed
                        @endif
                        - {{ $currentSettings->last_tested_at->diffForHumans() }}
                    </div>
                    @if(!$currentSettings->test_passed && $currentSettings->test_error)
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">{{ Str::limit($currentSettings->test_error, 100) }}</p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <!-- Configuration Form -->
    @if(!$hasSettings || $isEditing)
        <form wire:submit="save" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-6">
                {{ $hasSettings ? 'Edit' : 'Configure' }} Email Settings
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- SMTP Host -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>SMTP Host *</flux:label>
                        <flux:input wire:model="host" placeholder="smtp.yourserver.com" />
                        <flux:error name="host" />
                        <flux:description>The hostname or IP address of your SMTP server</flux:description>
                    </flux:field>
                </div>

                <!-- Port -->
                <div>
                    <flux:field>
                        <flux:label>Port *</flux:label>
                        <flux:input wire:model="port" type="number" min="1" max="65535" />
                        <flux:error name="port" />
                        <flux:description>Common ports: 587 (TLS), 465 (SSL), 25 (unencrypted)</flux:description>
                    </flux:field>
                </div>

                <!-- Encryption -->
                <div>
                    <flux:field>
                        <flux:label>Encryption</flux:label>
                        <flux:select wire:model="encryption">
                            <option value="">None</option>
                            <option value="tls">TLS (recommended)</option>
                            <option value="ssl">SSL</option>
                        </flux:select>
                        <flux:error name="encryption" />
                    </flux:field>
                </div>

                <!-- Username -->
                <div>
                    <flux:field>
                        <flux:label>Username</flux:label>
                        <flux:input wire:model="username" placeholder="Optional - leave blank for anonymous" />
                        <flux:error name="username" />
                    </flux:field>
                </div>

                <!-- Password -->
                <div>
                    <flux:field>
                        <flux:label>Password</flux:label>
                        <div class="relative">
                            <flux:input 
                                wire:model="password" 
                                type="{{ $showPassword ? 'text' : 'password' }}" 
                                placeholder="{{ $hasSettings ? 'Leave blank to keep current' : 'Optional' }}" 
                            />
                            <button 
                                type="button" 
                                wire:click="togglePasswordVisibility"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-700"
                            >
                                @if($showPassword)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029M6.343 6.343A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.05 10.05 0 01-1.563 3.029M6.343 6.343L19.657 19.657"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                @endif
                            </button>
                        </div>
                        <flux:error name="password" />
                    </flux:field>
                </div>

                <!-- From Address -->
                <div>
                    <flux:field>
                        <flux:label>From Email Address *</flux:label>
                        <flux:input wire:model="from_address" type="email" placeholder="noreply@yourdomain.com" />
                        <flux:error name="from_address" />
                        <flux:description>Email address that notifications will be sent from</flux:description>
                    </flux:field>
                </div>

                <!-- From Name -->
                <div>
                    <flux:field>
                        <flux:label>From Name *</flux:label>
                        <flux:input wire:model="from_name" placeholder="SSL Monitor" />
                        <flux:error name="from_name" />
                        <flux:description>Display name for outgoing emails</flux:description>
                    </flux:field>
                </div>

                <!-- Advanced Settings -->
                <div class="md:col-span-2">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Advanced Settings</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>Connection Timeout (seconds) *</flux:label>
                                <flux:input wire:model="timeout" type="number" min="5" max="300" />
                                <flux:error name="timeout" />
                            </flux:field>
                        </div>

                        <div class="flex items-center">
                            <flux:field>
                                <flux:checkbox wire:model="verify_peer" />
                                <flux:label>Verify SSL Certificates</flux:label>
                                <flux:description>Recommended for production environments</flux:description>
                            </flux:field>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-3 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                @if($hasSettings)
                    <flux:button wire:click="cancelEditing" variant="ghost">
                        Cancel
                    </flux:button>
                @endif
                
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $hasSettings ? 'Update' : 'Save' }} Settings</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
        </form>
    @endif
        </div>
    </x-settings.layout>
</section>