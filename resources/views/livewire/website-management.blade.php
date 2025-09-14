<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-3 mb-1">
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Websites</h1>
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
            <p class="text-zinc-600 dark:text-zinc-400">
                @if($team)
                    Manage SSL certificates for your personal and team websites
                @else
                    Monitor SSL certificates for your websites
                @endif
            </p>
        </div>
        
        @if($team)
            <flux:button :href="route('settings.team')" variant="ghost" size="sm" icon="users" wire:navigate>
                Team Settings
            </flux:button>
        @endif
    </div>

    <!-- Add/Edit Website Form -->
    <div class="p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm">
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white">Website Name</label>
                    <flux:input wire:model="name" placeholder="e.g., My Website" />
                    @error('name')
                        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white">Website URL</label>
                    <flux:input 
                        wire:model.live.debounce.500ms="url" 
                        placeholder="example.com (https:// will be added automatically)"
                        type="url" 
                    />
                    @error('url')
                        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Uptime Monitoring Settings -->
            <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center space-x-2 mb-4">
                    <flux:switch wire:model.live="uptime_monitoring" />
                    <label class="text-sm font-medium text-zinc-900 dark:text-white">Enable Uptime Monitoring</label>
                </div>

                @if($uptime_monitoring)
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mt-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-zinc-900 dark:text-white">Expected Status Code</label>
                            <flux:input wire:model="expected_status_code" type="number" min="100" max="599" placeholder="200" />
                            @error('expected_status_code')
                                <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-zinc-900 dark:text-white">Max Response Time (ms)</label>
                            <flux:input wire:model="max_response_time" type="number" min="1000" max="120000" placeholder="30000" />
                            @error('max_response_time')
                                <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-zinc-900 dark:text-white">Expected Content</label>
                            <flux:input wire:model="expected_content" placeholder="Text that should be present on the page" />
                            @error('expected_content')
                                <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-zinc-900 dark:text-white">Forbidden Content</label>
                            <flux:input wire:model="forbidden_content" placeholder="Text that should NOT be present on the page" />
                            @error('forbidden_content')
                                <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center space-x-2 mb-3">
                            <flux:switch wire:model.live="follow_redirects" />
                            <label class="text-sm font-medium text-zinc-900 dark:text-white">Follow Redirects</label>
                        </div>

                        <div class="flex items-center space-x-2 mb-3">
                            <flux:switch wire:model.live="javascript_enabled" />
                            <label class="text-sm font-medium text-zinc-900 dark:text-white">
                                JavaScript Rendering
                                <span class="text-xs text-zinc-500 dark:text-zinc-400 ml-1">(Enable for dynamic content)</span>
                            </label>
                        </div>

                        @if($follow_redirects)
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-zinc-900 dark:text-white">Max Redirects</label>
                                <flux:input wire:model="max_redirects" type="number" min="1" max="10" placeholder="3" class="w-24" />
                                @error('max_redirects')
                                    <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- SSL Certificate Preview -->
            @if($url && !$isCheckingCertificate && !$certificatePreview)
                <div class="mt-4">
                    <flux:button 
                        wire:click="checkCertificate" 
                        variant="ghost" 
                        size="sm"
                        icon="shield-check"
                    >
                        Check SSL Certificate
                    </flux:button>
                </div>
            @endif

            @if($isCheckingCertificate)
                <div class="mt-4 flex items-center space-x-2 text-sm text-gray-600">
                    <flux:icon name="arrow-path" class="h-4 w-4 animate-spin" />
                    <span>Checking SSL certificate...</span>
                </div>
            @endif

            @if($certificatePreview && !$isCheckingCertificate)
                <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center space-x-2 mb-2">
                        <flux:icon name="shield-check" class="h-5 w-5" />
                        <h3 class="text-sm font-medium text-zinc-900 dark:text-white">SSL Certificate Preview</h3>
                        <flux:badge 
                            :color="match($certificatePreview['status']) {
                                'valid' => 'green',
                                'expiring_soon' => 'yellow', 
                                'expired' => 'red',
                                'invalid' => 'red',
                                'error' => 'gray',
                                default => 'gray'
                            }"
                        >
                            {{ ucwords(str_replace('_', ' ', $certificatePreview['status'])) }}
                        </flux:badge>
                    </div>
                    
                    @if($certificatePreview['status'] === 'error')
                        <p class="text-red-600 dark:text-red-400">
                            {{ $certificatePreview['error_message'] }}
                        </p>
                    @else
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium">Issuer:</span>
                                <span class="text-gray-600 dark:text-gray-400">{{ $certificatePreview['issuer'] ?? 'Unknown' }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Subject:</span>
                                <span class="text-gray-600 dark:text-gray-400">{{ $certificatePreview['subject'] ?? 'Unknown' }}</span>
                            </div>
                            @if($certificatePreview['expires_at'])
                                <div>
                                    <span class="font-medium">Expires:</span>
                                    <span class="text-gray-600 dark:text-gray-400">{{ $certificatePreview['expires_at']->format('M j, Y') }}</span>
                                </div>
                                <div>
                                    <span class="font-medium">Days Until Expiry:</span>
                                    <span class="text-gray-600 dark:text-gray-400">{{ $certificatePreview['days_until_expiry'] }}</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <!-- Form Actions -->
            <div class="flex space-x-2 mt-6">
                <flux:button type="submit" variant="primary">
                    {{ $editingWebsiteId ? 'Update Website' : 'Add Website' }}
                </flux:button>
                
                @if($editingWebsiteId)
                    <flux:button wire:click="resetForm" variant="ghost">
                        Cancel
                    </flux:button>
                @endif
            </div>
        </form>
    </div>

    <!-- Websites List -->
    @if($websites->count() > 0)
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Your Websites</h2>
            </div>
            
            <div class="divide-y">
                @foreach($websites as $website)
                    <div class="flex items-center justify-between p-4">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <h3 class="text-sm font-medium text-zinc-900 dark:text-white">{{ $website->name }}</h3>
                                        @if($website->isTeamWebsite())
                                            <flux:badge variant="outline" color="green" size="xs">Team</flux:badge>
                                        @else
                                            <flux:badge variant="outline" color="blue" size="xs">Personal</flux:badge>
                                        @endif

                                        <!-- Uptime Status -->
                                        @if($website->uptime_monitoring)
                                            <flux:badge 
                                                :color="match($website->uptime_status) {
                                                    'up' => 'green',
                                                    'down' => 'red',
                                                    'slow' => 'yellow',
                                                    'content_mismatch' => 'orange',
                                                    'unknown' => 'gray',
                                                    default => 'gray'
                                                }" 
                                                size="xs"
                                            >
                                                {{ match($website->uptime_status) {
                                                    'up' => 'Up',
                                                    'down' => 'Down',
                                                    'slow' => 'Slow',
                                                    'content_mismatch' => 'Content Issue',
                                                    'unknown' => 'Unknown',
                                                    default => 'Unknown'
                                                } }}
                                            </flux:badge>
                                        @else
                                            <flux:badge variant="outline" color="zinc" size="xs">SSL Only</flux:badge>
                                        @endif
                                    </div>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $website->url }}</p>
                                    
                                    <!-- Uptime Check Info -->
                                    @if($website->uptime_monitoring)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                            @if($website->last_uptime_check_at)
                                                <span>Last checked: {{ $website->last_uptime_check_at->diffForHumans() }}</span>
                                            @else
                                                <span>Never checked</span>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if($website->isTeamWebsite() && $website->addedBy)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                            Added by {{ $website->addedBy->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            @if($website->uptime_monitoring)
                                <flux:button 
                                    wire:click="checkUptime({{ $website->id }})" 
                                    variant="ghost" 
                                    size="sm"
                                    icon="arrow-path"
                                >
                                    Check Uptime
                                </flux:button>
                            @endif
                            
                            <flux:button 
                                href="{{ route('websites.show', $website) }}" 
                                variant="ghost" 
                                size="sm"
                                icon="eye"
                            >
                                View Details
                            </flux:button>
                            
                            <flux:button 
                                wire:click="edit({{ $website->id }})" 
                                variant="ghost" 
                                size="sm"
                                icon="pencil"
                            >
                                Edit
                            </flux:button>
                            
                            <flux:button 
                                wire:click="delete({{ $website->id }})" 
                                variant="ghost" 
                                size="sm"
                                icon="trash"
                                wire:confirm="Are you sure you want to delete this website?"
                            >
                                Delete
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <flux:icon name="globe-alt" class="h-12 w-12 mx-auto text-gray-400" />
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mt-4">No websites yet</h2>
            <p class="text-zinc-600 dark:text-zinc-400 mt-2">Add your first website to start monitoring SSL certificates.</p>
        </div>
    @endif
</div>