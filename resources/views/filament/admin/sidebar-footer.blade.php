@php
    $user = filament()->auth()->user();
@endphp

<div class="fi-sidebar-footer border-t border-gray-200 dark:border-white/10">
    <div
        class="flex items-center gap-x-3 px-4 py-3"
        x-show="$store.sidebar.isOpen"
        x-cloak
    >
        <x-filament-panels::avatar.user size="sm" :user="$user" />

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                {{ filament()->getUserName($user) }}
            </p>
            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                {{ $user->is_super_admin ? 'Super admin' : 'Platform user' }}
            </p>
        </div>

        <form action="{{ filament()->getLogoutUrl() }}" method="post">
            @csrf

            <button
                type="submit"
                class="flex-shrink-0 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-300"
                title="{{ __('filament-panels::widgets/account-widget.actions.logout.label') }}"
            >
                <x-filament::icon icon="heroicon-o-arrow-left-on-rectangle" class="h-4 w-4" />
            </button>
        </form>
    </div>

    <div
        class="flex justify-center py-3"
        x-show="! $store.sidebar.isOpen"
        x-cloak
    >
        <x-filament-panels::avatar.user size="sm" :user="$user" />
    </div>
</div>
