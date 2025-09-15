<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-3 mb-1">
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">SSL Dashboard</h1>
                @if($team)
                    <flux:badge variant="solid" color="green" size="sm">
                        {{ $team->name }}
                    </flux:badge>
                @else
                    <flux:badge variant="outline" color="blue" size="sm">
                        Individual
                    </flux:badge>
                @endif
            </div>
            <div class="flex items-center space-x-4 text-sm text-zinc-600 dark:text-zinc-400">
                <span>Monitor your SSL certificate status</span>
                @if($team)
                    <span>•</span>
                    <span>{{ $personalWebsitesCount }} personal, {{ $teamWebsitesCount }} team websites</span>
                @endif
            </div>
        </div>
        
        <div class="flex items-center space-x-2">
            @if($team)
                <flux:button :href="route('settings.team')" variant="ghost" size="sm" icon="users" wire:navigate>
                    Team
                </flux:button>
            @endif
            <flux:button wire:click="refresh" variant="ghost" size="sm" icon="arrow-path">
                Refresh
            </flux:button>
        </div>
    </div>

    @if($uptimeOverview['has_websites'])
        <!-- Website Overview Summary -->
        <div class="bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Website Overview</h2>
                    <div class="flex items-center space-x-6 mt-2 text-sm">
                        <div class="flex items-center">
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $uptimeOverview['total_websites'] }} websites</span>
                            <span class="text-zinc-500 dark:text-zinc-400 ml-1">total</span>
                        </div>
                        
                        @if($uptimeOverview['has_uptime_monitoring'])
                            <div class="flex items-center">
                                <span class="font-medium text-blue-600 dark:text-blue-400">{{ $uptimeOverview['monitored_websites'] }} monitored</span>
                                <span class="text-zinc-500 dark:text-zinc-400 ml-1">for uptime</span>
                            </div>
                            
                            @if($uptimeOverview['ssl_only_websites'] > 0)
                                <div class="flex items-center">
                                    <span class="font-medium text-zinc-600 dark:text-zinc-400">{{ $uptimeOverview['ssl_only_websites'] }} SSL only</span>
                                </div>
                            @endif
                        @else
                            <div class="flex items-center">
                                <span class="font-medium text-amber-600 dark:text-amber-400">SSL only</span>
                                <span class="text-zinc-500 dark:text-zinc-400 ml-1">monitoring</span>
                            </div>
                        @endif
                    </div>
                </div>
                
                @if($uptimeOverview['has_uptime_monitoring'])
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($uptimeAvailability, 1) }}%</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">uptime availability</div>
                    </div>
                @endif
            </div>
        </div>
    @endif

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

        @if($uptimeStatusCounts['total_monitored'] > 0)
            <!-- Uptime Monitoring Overview -->
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Uptime Monitoring</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                    <!-- Up Status -->
                    <div class="p-4 bg-white dark:bg-zinc-900 border border-green-200 dark:border-green-700 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                                <flux:icon name="check-circle" class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Up</p>
                                <p class="text-2xl font-semibold text-green-600 dark:text-green-400">{{ $uptimeStatusCounts['up'] }}</p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $uptimeStatusPercentages['up'] }}% of monitored</span>
                        </div>
                    </div>

                    <!-- Down Status -->
                    <div class="p-4 bg-white dark:bg-zinc-900 border border-red-200 dark:border-red-700 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                                <flux:icon name="x-circle" class="h-6 w-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Down</p>
                                <p class="text-2xl font-semibold text-red-600 dark:text-red-400">{{ $uptimeStatusCounts['down'] }}</p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $uptimeStatusPercentages['down'] }}% of monitored</span>
                        </div>
                    </div>

                    <!-- Slow Status -->
                    <div class="p-4 bg-white dark:bg-zinc-900 border border-yellow-200 dark:border-yellow-700 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                                <flux:icon name="exclamation-triangle" class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Slow</p>
                                <p class="text-2xl font-semibold text-yellow-600 dark:text-yellow-400">{{ $uptimeStatusCounts['slow'] }}</p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $uptimeStatusPercentages['slow'] }}% of monitored</span>
                        </div>
                    </div>

                    <!-- Content Mismatch -->
                    <div class="p-4 bg-white dark:bg-zinc-900 border border-orange-200 dark:border-orange-700 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                                <flux:icon name="document-text" class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Content Issues</p>
                                <p class="text-2xl font-semibold text-orange-600 dark:text-orange-400">{{ $uptimeStatusCounts['content_mismatch'] }}</p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $uptimeStatusPercentages['content_mismatch'] }}% of monitored</span>
                        </div>
                    </div>

                    <!-- Unknown Status -->
                    <div class="p-4 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                <flux:icon name="question-mark-circle" class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Unknown</p>
                                <p class="text-2xl font-semibold text-gray-600 dark:text-gray-400">{{ $uptimeStatusCounts['unknown'] }}</p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $uptimeStatusPercentages['unknown'] }}% of monitored</span>
                        </div>
                    </div>

                    <!-- Overall Availability -->
                    <div class="p-4 bg-white dark:bg-zinc-900 border border-blue-200 dark:border-blue-700 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                <flux:icon name="chart-bar" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Availability</p>
                                <p class="text-2xl font-semibold text-blue-600 dark:text-blue-400">{{ number_format($uptimeAvailability, 1) }}%</p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $uptimeStatusCounts['total_monitored'] }} monitored</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Website Overview Cards -->
        @if($websiteCards->isNotEmpty())
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Your Websites</h2>
                    <flux:button :href="route('websites')" variant="ghost" size="sm" icon="cog" wire:navigate>
                        Manage All
                    </flux:button>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($websiteCards as $website)
                        <x-website-card :website="$website" />
                    @endforeach
                </div>

                @if($websiteCards->count() >= 8)
                    <div class="mt-4 text-center">
                        <flux:button :href="route('websites')" variant="ghost" size="sm" wire:navigate>
                            View All Websites
                        </flux:button>
                    </div>
                @endif
            </div>
        @endif

        <!-- Uptime Issues Section -->
        @if($uptimeCriticalIssues->isNotEmpty())
            <div class="p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-lg">
                <div class="flex items-center mb-3">
                    <flux:icon name="exclamation-triangle" class="h-5 w-5 text-orange-600 dark:text-orange-400 mr-2" />
                    <h3 class="text-sm font-medium text-orange-800 dark:text-orange-200">Uptime Issues</h3>
                </div>
                <div class="space-y-2">
                    @foreach($uptimeCriticalIssues as $website)
                        <div class="flex items-center justify-between text-sm p-2 hover:bg-orange-100 dark:hover:bg-orange-900/20 rounded transition-colors cursor-pointer"
                             wire:click="goToWebsiteDetails({{ $website->id }})">
                            <div class="flex items-center space-x-2">
                                <span class="font-medium text-orange-700 dark:text-orange-300">{{ $website->name }}</span>
                                <span class="text-orange-600 dark:text-orange-400">•</span>
                                <span class="text-orange-600 dark:text-orange-400">
                                    {{ $website->uptime_status === 'down' ? 'Website is down' : 'Content mismatch detected' }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-orange-500 dark:text-orange-400">
                                    {{ $website->last_uptime_check_at ? $website->last_uptime_check_at->diffForHumans() : 'Never checked' }}
                                </span>
                                <flux:icon name="chevron-right" class="h-3 w-3 text-orange-400" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Critical Issues Section -->
        @if($criticalIssues->isNotEmpty())
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <div class="flex items-center mb-3">
                    <flux:icon name="exclamation-triangle" class="h-5 w-5 text-red-600 dark:text-red-400 mr-2" />
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Critical Issues</h3>
                </div>
                <div class="space-y-2">
                    @foreach($criticalIssues as $check)
                        <div class="flex items-center justify-between text-sm p-2 hover:bg-red-100 dark:hover:bg-red-900/20 rounded transition-colors cursor-pointer"
                             wire:click="goToWebsiteDetails({{ $check->website->id }})">
                            <div class="flex items-center space-x-2">
                                <span class="font-medium text-red-700 dark:text-red-300">{{ $check->website->name }}</span>
                                <span class="text-red-600 dark:text-red-400">•</span>
                                <span class="text-red-600 dark:text-red-400">
                                    {{ $check->status === 'expired' ? 'SSL certificate expired' : 'SSL check failed' }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-red-500 dark:text-red-400">
                                    {{ $check->checked_at->diffForHumans() }}
                                </span>
                                <flux:icon name="chevron-right" class="h-3 w-3 text-red-400" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Recent Activity -->
        @if($recentChecks->isNotEmpty())
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Recent Activity</h2>
                        <flux:button :href="route('websites')" variant="ghost" size="sm" icon="cog" wire:navigate>
                            View All
                        </flux:button>
                    </div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Latest SSL check results</p>
                </div>

                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($recentChecks as $check)
                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer"
                             wire:click="goToWebsiteDetails({{ $check->website->id }})">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3 flex-1">
                                    @if($check->status === 'valid')
                                        <flux:icon name="shield-check" class="h-4 w-4 text-green-500" />
                                    @elseif($check->status === 'expiring_soon')
                                        <flux:icon name="exclamation-triangle" class="h-4 w-4 text-yellow-500" />
                                    @elseif($check->status === 'expired')
                                        <flux:icon name="shield-exclamation" class="h-4 w-4 text-red-500" />
                                    @else
                                        <flux:icon name="x-circle" class="h-4 w-4 text-red-500" />
                                    @endif

                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $check->website->name }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ ucwords(str_replace('_', ' ', $check->status)) }} • {{ $check->checked_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                <flux:icon name="chevron-right" class="h-4 w-4 text-zinc-400" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>