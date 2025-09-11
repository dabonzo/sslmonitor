<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <div class="flex items-center space-x-4 mb-3">
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $website->name }}</h1>
                <flux:badge :color="$statusColor">
                    {{ $statusText }}
                </flux:badge>
            </div>
            <p class="text-lg text-zinc-600 dark:text-zinc-400">{{ $website->url }}</p>
        </div>
        
        <div class="flex items-center space-x-3">
            <flux:button wire:click="refreshData" variant="ghost" size="sm" icon="arrow-path">
                Refresh
            </flux:button>
            
            <flux:button wire:click="editWebsite" variant="ghost" size="sm" icon="pencil">
                Edit
            </flux:button>
            
            <flux:button 
                wire:click="deleteWebsite" 
                variant="ghost" 
                size="sm" 
                icon="trash"
                wire:confirm="Are you sure you want to delete this website? This action cannot be undone."
            >
                Delete
            </flux:button>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
            <p class="text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    @if(session()->has('info'))
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
            <p class="text-blue-800 dark:text-blue-200">{{ session('info') }}</p>
        </div>
    @endif

    <!-- Current SSL Status -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Current SSL Status</h2>
        </div>
        
        <div class="p-6">
            @if($latestSslCheck)
                <!-- Status Overview Card -->
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-6 mb-8">
                    <div class="flex items-center space-x-3 mb-4">
                        @if($latestSslCheck->status === 'valid')
                            <flux:icon name="shield-check" class="h-6 w-6 text-green-500" />
                        @elseif($latestSslCheck->status === 'expiring_soon')
                            <flux:icon name="exclamation-triangle" class="h-6 w-6 text-yellow-500" />
                        @elseif($latestSslCheck->status === 'expired')
                            <flux:icon name="shield-exclamation" class="h-6 w-6 text-red-500" />
                        @else
                            <flux:icon name="x-circle" class="h-6 w-6 text-red-500" />
                        @endif
                        <h3 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $statusText }}</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @if($expiryText)
                            <div>
                                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Time Until Expiry</p>
                                <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $expiryText }}</p>
                            </div>
                        @endif
                        
                        @if($latestSslCheck->expires_at)
                            <div>
                                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Expiration Date</p>
                                <p class="text-lg font-semibold text-zinc-900 dark:text-white">
                                    Expires on {{ $latestSslCheck->expires_at->format('M j, Y') }}
                                </p>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $latestSslCheck->expires_at->format('g:i A') }}
                                </p>
                            </div>
                        @endif
                        
                        <div>
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Last Checked</p>
                            <p class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $latestSslCheck->checked_at->diffForHumans() }}
                            </p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $latestSslCheck->checked_at->format('M j, Y \a\t g:i A') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                    <!-- Certificate Details -->
                    @if($latestSslCheck->status !== 'error')
                        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">SSL Certificate Details</h3>
                            <div class="space-y-4">
                                @if($latestSslCheck->issuer)
                                    <div>
                                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Issuer</p>
                                        <p class="text-sm text-zinc-900 dark:text-white">{{ $latestSslCheck->issuer }}</p>
                                    </div>
                                @endif
                                
                                @if($latestSslCheck->subject)
                                    <div>
                                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Subject</p>
                                        <p class="text-sm text-zinc-900 dark:text-white">{{ $latestSslCheck->subject }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Technical Details -->
                        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Technical Details</h3>
                            <div class="space-y-4">
                                @if($latestSslCheck->serial_number)
                                    <div>
                                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Serial Number</p>
                                        <p class="text-sm font-mono text-zinc-900 dark:text-white break-all bg-zinc-50 dark:bg-zinc-800 px-3 py-2 rounded">{{ $latestSslCheck->serial_number }}</p>
                                    </div>
                                @endif
                                
                                @if($latestSslCheck->signature_algorithm)
                                    <div>
                                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Signature Algorithm</p>
                                        <p class="text-sm text-zinc-900 dark:text-white">{{ $latestSslCheck->signature_algorithm }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <!-- Error Message -->
                        <div class="md:col-span-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-3">Error Details</h3>
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $latestSslCheck->error_message }}</p>
                        </div>
                    @endif
                </div>
            @else
                <!-- No SSL checks yet -->
                <div class="text-center py-8">
                    <flux:icon name="shield-exclamation" class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">No SSL checks performed yet</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-4">Check the SSL certificate to see the current status.</p>
                </div>
            @endif
            
            <!-- Manual Check Button -->
            <div class="mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Manual SSL Check</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Trigger an immediate SSL certificate check for this website</p>
                    </div>
                    <flux:button 
                        wire:click="checkSslCertificate" 
                        variant="primary" 
                        icon="shield-check"
                        :disabled="$isCheckingNow"
                    >
                        @if($isCheckingNow)
                            <flux:icon name="arrow-path" class="h-4 w-4 animate-spin mr-2" />
                            Checking SSL certificate...
                        @else
                            Check SSL Certificate Now
                        @endif
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- SSL Check History -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">SSL Check History</h2>
        </div>

        @if($sslChecks->isNotEmpty())
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($sslChecks as $check)
                    <div class="p-4 hover:bg-gray-100 dark:hover:bg-gray-800/70 transition-colors cursor-pointer">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4 flex-1">
                                <div class="flex-shrink-0 mt-1">
                                    @if($check->status === 'valid')
                                        <flux:icon name="shield-check" class="h-5 w-5 text-green-500" />
                                    @elseif($check->status === 'expiring_soon')
                                        <flux:icon name="exclamation-triangle" class="h-5 w-5 text-yellow-500" />
                                    @elseif($check->status === 'expired')
                                        <flux:icon name="shield-exclamation" class="h-5 w-5 text-red-500" />
                                    @else
                                        <flux:icon name="x-circle" class="h-5 w-5 text-red-500" />
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <flux:badge 
                                            :color="match($check->status) {
                                                'valid' => 'green',
                                                'expiring_soon' => 'yellow', 
                                                'expired' => 'red',
                                                'error' => 'red',
                                                default => 'gray'
                                            }"
                                        >
                                            {{ ucwords(str_replace('_', ' ', $check->status)) }}
                                        </flux:badge>
                                        
                                        @if($check->days_until_expiry !== null && $check->status !== 'error')
                                            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                                                @if($check->days_until_expiry > 0)
                                                    {{ $check->days_until_expiry }} days until expiry
                                                @elseif($check->days_until_expiry === 0)
                                                    Expires today
                                                @else
                                                    Expired {{ abs($check->days_until_expiry) }} days ago
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @if($check->error_message)
                                        <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $check->error_message }}</p>
                                    @endif
                                    
                                    @if($check->expires_at && $check->status !== 'error')
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                                            Certificate expires {{ $check->expires_at->format('M j, Y \a\t g:i A') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-6">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $check->checked_at->format('M j, Y') }}
                                </p>
                                <p class="text-sm text-zinc-600 dark:text-zinc-200">
                                    {{ $check->checked_at->format('g:i A') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-4 text-center text-zinc-500 dark:text-zinc-400">
                <p>No SSL check history available.</p>
            </div>
        @endif
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let pollingInterval = null;
        
        // Listen for the start-polling event
        Livewire.on('start-polling', () => {
            // Poll every 2 seconds for updates
            pollingInterval = setInterval(() => {
                @this.pollForUpdates();
            }, 2000);
        });
        
        // Listen for the stop-polling event
        Livewire.on('stop-polling', () => {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        });
        
        // Listen for SSL check completed event
        Livewire.on('ssl-check-completed', () => {
            // Could add additional UI feedback here if needed
            console.log('SSL check completed and data updated');
        });
    });
    </script>
</div>