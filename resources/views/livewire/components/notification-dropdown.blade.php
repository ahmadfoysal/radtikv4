<div class="relative" x-data="{ open: @entangle('showDropdown') }">
    {{-- Notification Bell Button --}}
    <button @click="open = !open" class="btn btn-ghost btn-sm relative">
        <x-mary-icon name="o-bell" class="w-5 h-5" />
        <span class="ml-1 hidden sm:inline">Notifications</span>

        {{-- Badge for unread count --}}
        @if ($unreadCount > 0)
            <span class="absolute -top-1 -right-1 badge badge-sm badge-error">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown Menu --}}
    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-96 bg-base-100 rounded-box shadow-xl border border-base-300 z-50"
        style="display: none;">

        {{-- Header --}}
        <div class="flex items-center justify-between p-4 border-b border-base-300">
            <h3 class="font-bold text-lg">Notifications</h3>
            @if ($unreadCount > 0)
                <button wire:click="markAllAsRead" class="btn btn-ghost btn-xs">
                    Mark all as read
                </button>
            @endif
        </div>

        {{-- Notification List --}}
        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <div wire:key="notification-{{ $notification['id'] }}"
                    class="p-4 border-b border-base-300 hover:bg-base-200 transition-colors cursor-pointer {{ is_null($notification['read_at']) ? 'bg-base-100' : 'opacity-70' }}"
                    wire:click="markAsRead('{{ $notification['id'] }}')">

                    <div class="flex gap-3">
                        {{-- Icon --}}
                        <div class="flex-shrink-0">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center
                                {{ $notification['color'] === 'success' ? 'bg-success/20 text-success' : '' }}
                                {{ $notification['color'] === 'info' ? 'bg-info/20 text-info' : '' }}
                                {{ $notification['color'] === 'warning' ? 'bg-warning/20 text-warning' : '' }}
                                {{ $notification['color'] === 'primary' ? 'bg-primary/20 text-primary' : '' }}">
                                <x-mary-icon :name="$notification['icon']" class="w-5 h-5" />
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <h4 class="font-semibold text-sm">{{ $notification['subject'] }}</h4>
                                @if (is_null($notification['read_at']))
                                    <div class="w-2 h-2 rounded-full bg-primary flex-shrink-0 mt-1"></div>
                                @endif
                            </div>
                            <p class="text-sm text-base-content/70 mt-1 line-clamp-2">
                                {{ $notification['short_description'] }}
                            </p>
                            <p class="text-xs text-base-content/50 mt-2">
                                {{ $notification['created_at']->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-base-content/50">
                    <x-mary-icon name="o-bell-slash" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p>No notifications yet</p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        <div class="p-3 border-t border-base-300 text-center">
            <a href="{{ route('notifications.index') }}" class="btn btn-ghost btn-sm btn-block" @click="open = false">
                View All Notifications
            </a>
        </div>
    </div>
</div>
