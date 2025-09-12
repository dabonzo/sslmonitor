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
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden">
                                    <div class="flex items-center min-w-0 flex-1">
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium flex-shrink-0">
                                            {{ substr($member->user->name, 0, 1) }}
                                        </div>
                                        <div class="ml-3 min-w-0 flex-1">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {{ $member->user->name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                {{ $member->user->email }}
                                            </div>
                                        </div>
                                        <span class="ml-3 flex-shrink-0 px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full dark:bg-green-900/30 dark:text-green-400">Active</span>
                                    </div>
                                    
                                    <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                                        @if($team->userHasPermission($user, 'manage_team') && $member->user_id !== $user->id)
                                            <select 
                                                wire:change="changeMemberRole({{ $member->user_id }}, $event.target.value)"
                                                class="px-3 py-1 text-xs font-medium border border-gray-300 rounded-md bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <option value="admin" @if($member->role === 'admin') selected @endif>Admin</option>
                                                <option value="manager" @if($member->role === 'manager') selected @endif>Manager</option>
                                                <option value="viewer" @if($member->role === 'viewer') selected @endif>Viewer</option>
                                            </select>
                                            
                                            <button 
                                                wire:click="removeMember({{ $member->user_id }})"
                                                wire:confirm="Are you sure you want to remove this member?"
                                                class="px-3 py-1 text-xs font-medium text-white bg-red-600 border border-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                            >
                                                Remove
                                            </button>
                                        @else
                                            <span class="px-3 py-1 text-xs font-medium border rounded-md {{ $member->role === 'owner' ? 'border-purple-300 text-purple-700 bg-purple-50 dark:border-purple-600 dark:text-purple-300 dark:bg-purple-900/30' : 'border-blue-300 text-blue-700 bg-blue-50 dark:border-blue-600 dark:text-blue-300 dark:bg-blue-900/30' }}">
                                                {{ ucfirst($member->role) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Pending Invitations --}}
                    @if($pendingInvitations->count() > 0 && $team->userHasPermission($user, 'manage_team'))
                        <div class="mb-8">
                            <flux:heading size="md" class="mb-4">Pending Invitations</flux:heading>
                            
                            <div class="space-y-3">
                                @foreach($pendingInvitations as $invitation)
                                    <div class="flex items-center justify-between p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg overflow-hidden">
                                        <div class="flex items-center min-w-0 flex-1">
                                            <div class="w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center text-white text-sm font-medium flex-shrink-0">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                                </svg>
                                            </div>
                                            <div class="ml-3 min-w-0 flex-1">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                    {{ $invitation->email }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                    Invited {{ $invitation->created_at->diffForHumans() }} • Expires {{ $invitation->expires_at->format('M j, g:i A') }}
                                                </div>
                                            </div>
                                            <flux:badge variant="outline" color="amber" size="sm" class="ml-3 flex-shrink-0">Pending</flux:badge>
                                        </div>
                                        
                                        <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                                            <flux:badge variant="outline" color="blue" class="px-3 py-1 text-xs">
                                                {{ ucfirst($invitation->role) }}
                                            </flux:badge>
                                            
                                            <button 
                                                wire:click="resendInvitation({{ $invitation->id }})"
                                                class="px-3 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600"
                                            >
                                                Resend
                                            </button>
                                            
                                            <button 
                                                wire:click="cancelInvitation({{ $invitation->id }})"
                                                wire:confirm="Are you sure you want to cancel this invitation?"
                                                class="px-3 py-1 text-xs font-medium text-white bg-red-600 border border-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

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