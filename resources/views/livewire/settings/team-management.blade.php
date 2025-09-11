<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Team Management')" :subheading="__('Manage your team and share SSL monitoring with colleagues')">
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

        <div class="space-y-8">
            @if (!$team)
                {{-- Individual Mode - Create Team --}}
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">Individual Mode</flux:heading>
                            <flux:subheading>You're currently managing SSL certificates individually</flux:subheading>
                        </div>
                        <flux:badge variant="outline" color="blue">Active</flux:badge>
                    </div>
                    
                    <flux:separator class="my-6" />
                    
                    <div>
                        <flux:heading size="md" class="mb-3">Create Team</flux:heading>
                        <flux:text class="mb-4 text-gray-600 dark:text-gray-400">
                            Create a team to share SSL monitoring with colleagues and centralize management.
                        </flux:text>
                        
                        <form wire:submit="createTeam" class="space-y-4">
                            <flux:input 
                                wire:model="teamName" 
                                :label="__('Team Name')" 
                                type="text" 
                                placeholder="SSL Monitor Team" 
                                required 
                            />

                            @if($personalWebsites->count() > 0)
                                <div>
                                    <flux:field :label="__('Transfer Websites to Team')">
                                        <div class="mt-2 space-y-2">
                                            @foreach($personalWebsites as $website)
                                                <label class="flex items-center">
                                                    <flux:checkbox 
                                                        wire:model="transferWebsites" 
                                                        value="{{ $website->id }}" 
                                                    />
                                                    <span class="ml-2 text-sm">{{ $website->name ?: $website->url }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </flux:field>
                                </div>
                            @endif

                            <div class="flex justify-end">
                                <flux:button type="submit" variant="primary">
                                    Create Team
                                </flux:button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                {{-- Team Mode - Manage Team --}}
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="lg">{{ $team->name }}</flux:heading>
                            <flux:subheading>Team Owner: {{ $team->owner->name }}</flux:subheading>
                        </div>
                        <flux:badge variant="solid" color="green">Team Mode</flux:badge>
                    </div>
                    
                    <flux:separator class="my-6" />

                    {{-- Team Members --}}
                    <div class="mb-8">
                        <flux:heading size="md" class="mb-4">Team Members</flux:heading>
                        
                        <div class="space-y-3">
                            @foreach($teamMembers as $member)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                            {{ substr($member->user->name, 0, 1) }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $member->user->name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $member->user->email }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-3">
                                        @if($team->userHasPermission($user, 'manage_team') && $member->user_id !== $user->id)
                                            <flux:select 
                                                wire:change="changeMemberRole({{ $member->user_id }}, $event.target.value)"
                                                class="text-sm"
                                            >
                                                <option value="admin" @if($member->role === 'admin') selected @endif>Admin</option>
                                                <option value="manager" @if($member->role === 'manager') selected @endif>Manager</option>
                                                <option value="viewer" @if($member->role === 'viewer') selected @endif>Viewer</option>
                                            </flux:select>
                                            
                                            <flux:button 
                                                wire:click="removeMember({{ $member->user_id }})"
                                                variant="danger" 
                                                size="sm"
                                                wire:confirm="Are you sure you want to remove this member?"
                                            >
                                                Remove
                                            </flux:button>
                                        @else
                                            <flux:badge variant="outline" :color="$member->role === 'owner' ? 'purple' : 'blue'">
                                                {{ ucfirst($member->role) }}
                                            </flux:badge>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Invite New Member --}}
                    @if($team->userHasPermission($user, 'manage_team'))
                        <div>
                            <flux:heading size="md" class="mb-4">Invite Team Member</flux:heading>
                            
                            <form wire:submit="inviteUser" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <flux:input 
                                        wire:model="inviteEmail" 
                                        :label="__('Email Address')" 
                                        type="email" 
                                        placeholder="colleague@company.com"
                                        required 
                                    />
                                    
                                    <flux:select wire:model="inviteRole" :label="__('Role')">
                                        <option value="admin">Admin</option>
                                        <option value="manager">Manager</option>
                                        <option value="viewer">Viewer</option>
                                    </flux:select>
                                </div>

                                <div class="flex justify-end">
                                    <flux:button type="submit" variant="primary">
                                        Invite Member
                                    </flux:button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>

                {{-- Role Permissions Info --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg border border-blue-200 dark:border-blue-800">
                    <flux:heading size="md" class="mb-3 text-blue-800 dark:text-blue-200">Role Permissions</flux:heading>
                    <div class="space-y-2 text-sm text-blue-700 dark:text-blue-300">
                        <div><strong>Owner:</strong> Full access - manage team, websites, and settings</div>
                        <div><strong>Admin:</strong> Manage websites and email settings (cannot manage team)</div>
                        <div><strong>Manager:</strong> Add/edit websites and view settings</div>
                        <div><strong>Viewer:</strong> View-only access to websites and settings</div>
                    </div>
                </div>
            @endif
        </div>
    </x-settings.layout>
</section>