<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $website->name }}</h1>
                <flux:badge :color="$statusColor">
                    {{ $statusText }}
                </flux:badge>
            </div>
            <p class="text-zinc-600 dark:text-zinc-400">{{ $website->url }}</p>
        </div>
        
        <div class="flex items-center space-x-2">
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

    <!-- Current SSL Status -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Current SSL Status</h2>
        </div>
        
        <div class="p-6">
            @if($latestSslCheck)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Status Overview -->
                    <div class="space-y-3">
                        <div class="flex items-center space-x-2">
                            @if($latestSslCheck->status === 'valid')
                                <flux:icon name="shield-check" class="h-5 w-5 text-green-500" />
                            @elseif($latestSslCheck->status === 'expiring_soon')
                                <flux:icon name="exclamation-triangle" class="h-5 w-5 text-yellow-500" />
                            @elseif($latestSslCheck->status === 'expired')
                                <flux:icon name="shield-exclamation" class="h-5 w-5 text-red-500" />
                            @else
                                <flux:icon name="x-circle" class="h-5 w-5 text-red-500" />
                            @endif
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $statusText }}</span>
                        </div>
                        
                        @if($expiryText)
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $expiryText }}</p>
                        @endif
                        
                        @if($latestSslCheck->expires_at)
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                Expires on {{ $latestSslCheck->expires_at->format('M j, Y \a\t g:i A') }}
                            </p>
                        @endif
                        
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Last checked {{ $latestSslCheck->checked_at->diffForHumans() }}
                        </p>
                    </div>

                    <!-- Certificate Details -->
                    @if($latestSslCheck->status !== 'error')
                        <div class="space-y-3">
                            <h3 class="font-medium text-zinc-900 dark:text-white">SSL Certificate Details</h3>
                            
                            @if($latestSslCheck->issuer)
                                <div>
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Issuer:</span>
                                    <p class="text-sm text-zinc-900 dark:text-white">{{ $latestSslCheck->issuer }}</p>
                                </div>
                            @endif
                            
                            @if($latestSslCheck->subject)
                                <div>
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Subject:</span>
                                    <p class="text-sm text-zinc-900 dark:text-white">{{ $latestSslCheck->subject }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Technical Details -->
                        <div class="space-y-3">
                            <h3 class="font-medium text-zinc-900 dark:text-white">Technical Details</h3>
                            
                            @if($latestSslCheck->serial_number)
                                <div>
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Serial Number:</span>
                                    <p class="text-sm font-mono text-zinc-900 dark:text-white break-all">{{ $latestSslCheck->serial_number }}</p>
                                </div>
                            @endif
                            
                            @if($latestSslCheck->signature_algorithm)
                                <div>
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Signature Algorithm:</span>
                                    <p class="text-sm text-zinc-900 dark:text-white">{{ $latestSslCheck->signature_algorithm }}</p>
                                </div>
                            @endif
                        </div>
                    @else
                        <!-- Error Message -->
                        <div class="md:col-span-2 space-y-3">
                            <h3 class="font-medium text-red-600 dark:text-red-400">Error Details</h3>
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
            <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button 
                    wire:click="checkSslCertificate" 
                    variant="primary" 
                    icon="shield-check"
                    :disabled="$isCheckingNow"
                >
                    @if($isCheckingNow)
                        Checking SSL certificate...
                    @else
                        Check SSL Certificate
                    @endif
                </flux:button>
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
                    <div class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
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
                                <div>
                                    <div class="flex items-center space-x-2">
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
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">
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
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                            Certificate expires {{ $check->expires_at->format('M j, Y') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-zinc-900 dark:text-white">
                                    {{ $check->checked_at->format('M j, Y') }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
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