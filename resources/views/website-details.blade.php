<x-layouts.app :title="'Website Details - ' . $website->name">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Website Details
                </h2>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                    {{ $website->url }}
                </p>
            </div>
            <flux:button variant="ghost" href="{{ route('websites') }}">
                ← Back to Websites
            </flux:button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:website-details :website="$website" />
        </div>
    </div>
</x-layouts.app>