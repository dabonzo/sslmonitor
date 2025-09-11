<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">SSL Dashboard</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Monitor your SSL certificate status</p>
        </div>
        
        <flux:button wire:click="refresh" variant="ghost" size="sm" icon="arrow-path">
            Refresh
        </flux:button>
    </div>

    @if($statusCounts['total'] === 0)
        <!-- Empty State -->
        <div class="text-center py-12">
            <flux:icon name="shield-exclamation" class="h-16 w-16 mx-auto text-gray-400 mb-4" />
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">No SSL certificates to monitor yet</h2>
            <p class="text-zinc-600 dark:text-zinc-400 mb-4">Add your first website to start monitoring SSL certificates.</p>
            <flux:button :href="route('websites')" variant="primary" icon="plus" wire:navigate>
                Add Website
            </flux:button>
        </div>
    @else
        <!-- SSL Status Overview Cards -->
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">SSL Certificate Status</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <!-- Valid Certificates -->
                <div class="p-4 bg-white dark:bg-zinc-900 border border-green-200 dark:border-green-700 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                            <flux:icon name="shield-check" class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Valid</p>
                            <p class="text-2xl font-semibold text-green-600 dark:text-green-400">{{ $statusCounts['valid'] }}</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $statusPercentages['valid'] }}% of total</span>
                    </div>
                </div>

                <!-- Expiring Soon -->
                <div class="p-4 bg-white dark:bg-zinc-900 border border-yellow-200 dark:border-yellow-700 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                            <flux:icon name="exclamation-triangle" class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Expiring Soon</p>
                            <p class="text-2xl font-semibold text-yellow-600 dark:text-yellow-400">{{ $statusCounts['expiring_soon'] }}</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $statusPercentages['expiring_soon'] }}% of total</span>
                    </div>
                </div>

                <!-- Expired -->
                <div class="p-4 bg-white dark:bg-zinc-900 border border-red-200 dark:border-red-700 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                            <flux:icon name="shield-exclamation" class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Expired</p>
                            <p class="text-2xl font-semibold text-red-600 dark:text-red-400">{{ $statusCounts['expired'] }}</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $statusPercentages['expired'] }}% of total</span>
                    </div>
                </div>

                <!-- Errors -->
                <div class="p-4 bg-white dark:bg-zinc-900 border border-red-200 dark:border-red-700 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                            <flux:icon name="x-circle" class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Errors</p>
                            <p class="text-2xl font-semibold text-red-600 dark:text-red-400">{{ $statusCounts['error'] }}</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $statusPercentages['error'] }}% of total</span>
                    </div>
                </div>

                <!-- Pending -->
                <div class="p-4 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            <flux:icon name="clock" class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Pending</p>
                            <p class="text-2xl font-semibold text-gray-600 dark:text-gray-400">{{ $statusCounts['pending'] }}</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $statusPercentages['pending'] }}% of total</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Critical Issues Section -->
        @if($criticalIssues->isNotEmpty())
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <div class="flex items-center mb-3">
                    <flux:icon name="exclamation-triangle" class="h-5 w-5 text-red-600 dark:text-red-400 mr-2" />
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Critical Issues</h3>
                </div>
                <div class="space-y-2">
                    @foreach($criticalIssues as $check)
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center space-x-2">
                                <span class="font-medium text-red-700 dark:text-red-300">{{ $check->website->name }}</span>
                                <span class="text-red-600 dark:text-red-400">•</span>
                                <span class="text-red-600 dark:text-red-400">
                                    {{ $check->status === 'expired' ? 'SSL certificate expired' : 'SSL check failed' }}
                                </span>
                            </div>
                            <span class="text-xs text-red-500 dark:text-red-400">
                                {{ $check->checked_at->diffForHumans() }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Recent SSL Checks -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Recent SSL Checks</h2>
                    <flux:button :href="route('websites')" variant="ghost" size="sm" icon="cog" wire:navigate>
                        Manage Websites
                    </flux:button>
                </div>
            </div>

            @if($recentChecks->isNotEmpty())
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($recentChecks as $check)
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
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $check->website->name }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $check->website->url }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
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
                                    </div>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                        {{ $check->checked_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-4 text-center text-zinc-500 dark:text-zinc-400">
                    <p>No SSL checks performed yet.</p>
                </div>
            @endif
        </div>
    @endif
</div>