@props(['website'])

<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer {{ $website->has_issues ? 'ring-2 ring-orange-200 dark:ring-orange-700' : '' }}"
     wire:click="goToWebsiteDetails({{ $website->id }})">
    <div class="p-4">
        <!-- Header with name and status badges -->
        <div class="flex items-start justify-between mb-3">
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $website->name }}</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $website->url }}</p>
            </div>

            @if($website->has_issues)
                <flux:icon name="exclamation-triangle" class="h-4 w-4 text-orange-500 flex-shrink-0 ml-2" />
            @endif
        </div>

        <!-- SSL Status -->
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-zinc-600 dark:text-zinc-400">SSL Certificate</span>
            <flux:badge
                :color="match($website->ssl_status) {
                    'valid' => 'green',
                    'expiring_soon' => 'yellow',
                    'expired' => 'red',
                    'error' => 'red',
                    default => 'gray'
                }"
                size="sm"
            >
                {{ ucwords(str_replace('_', ' ', $website->ssl_status)) }}
            </flux:badge>
        </div>

        <!-- Uptime Status (if monitoring enabled) -->
        @if($website->uptime_monitoring)
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-zinc-600 dark:text-zinc-400">Uptime Status</span>
                <flux:badge
                    :color="match($website->uptime_status) {
                        'up' => 'green',
                        'down' => 'red',
                        'slow' => 'yellow',
                        'content_mismatch' => 'orange',
                        default => 'gray'
                    }"
                    size="sm"
                >
                    @if($website->uptime_status === 'content_mismatch')
                        Content Issues
                    @else
                        {{ ucwords($website->uptime_status ?? 'Unknown') }}
                    @endif
                </flux:badge>
            </div>
        @endif

        <!-- Last checked timestamp -->
        <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-700">
            <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                <span>Last SSL check</span>
                <span>{{ $website->ssl_checked_at ? $website->ssl_checked_at->diffForHumans() : 'Never' }}</span>
            </div>

            @if($website->uptime_monitoring && $website->last_uptime_check_at)
                <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    <span>Last uptime check</span>
                    <span>{{ $website->last_uptime_check_at->diffForHumans() }}</span>
                </div>
            @endif
        </div>

        <!-- Quick actions (hidden by default, can be enabled later) -->
        <div class="hidden mt-3 flex items-center space-x-2">
            <flux:button
                wire:click.stop="checkWebsite({{ $website->id }})"
                variant="ghost"
                size="sm"
                icon="arrow-path"
                class="text-xs"
            >
                Check Now
            </flux:button>
        </div>
    </div>
</div>