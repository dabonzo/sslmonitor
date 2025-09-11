<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Websites</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Monitor SSL certificates for your websites</p>
        </div>
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
                        placeholder="https://example.com"
                        type="url" 
                    />
                    @error('url')
                        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>
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
            <div class="flex space-x-2">
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
                                <div>
                                    <h3 class="text-sm font-medium text-zinc-900 dark:text-white">{{ $website->name }}</h3>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $website->url }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2">
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