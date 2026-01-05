<div>
    <x-mary-header title="Notifications" separator>
        <x-slot:actions>
            @if ($stats['unread'] > 0)
                <x-mary-button label="Mark All as Read" icon="o-check" wire:click="markAllAsRead" class="btn-sm" />
            @endif
        </x-slot:actions>
    </x-mary-header>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-mary-card>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center">
                    <x-mary-icon name="o-bell" class="w-6 h-6 text-primary" />
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $stats['total'] }}</div>
                    <div class="text-sm text-base-content/70">Total Notifications</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-error/20 flex items-center justify-center">
                    <x-mary-icon name="o-envelope" class="w-6 h-6 text-error" />
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $stats['unread'] }}</div>
                    <div class="text-sm text-base-content/70">Unread</div>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-success/20 flex items-center justify-center">
                    <x-mary-icon name="o-envelope-open" class="w-6 h-6 text-success" />
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $stats['read'] }}</div>
                    <div class="text-sm text-base-content/70">Read</div>
                </div>
            </div>
        </x-mary-card>
    </div>

    <x-mary-card>
        {{-- Filter Tabs --}}
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
            <div class="tabs tabs-boxed">
                <button wire:click="$set('filter', 'all')" class="tab {{ $filter === 'all' ? 'tab-active' : '' }}">
                    All
                </button>
                <button wire:click="$set('filter', 'unread')"
                    class="tab {{ $filter === 'unread' ? 'tab-active' : '' }}">
                    Unread ({{ $stats['unread'] }})
                </button>
                <button wire:click="$set('filter', 'read')" class="tab {{ $filter === 'read' ? 'tab-active' : '' }}">
                    Read
                </button>
            </div>

            {{-- Bulk Actions --}}
            @if (count($selectedNotifications) > 0)
                <div class="flex gap-2">
                    <x-mary-button label="Mark as Read" icon="o-check" wire:click="markSelectedAsRead" class="btn-sm" />
                    <x-mary-button label="Delete" icon="o-trash" wire:click="deleteSelected"
                        wire:confirm="Are you sure you want to delete the selected notifications?"
                        class="btn-sm btn-error" />
                </div>
            @endif
        </div>

        {{-- Select All Checkbox --}}
        @if ($notifications->count() > 0)
            <div class="mb-4 flex items-center gap-2 pb-4 border-b border-base-300">
                <input type="checkbox" wire:model.live="selectAll" class="checkbox checkbox-sm" />
                <span class="text-sm">Select All</span>
                @if (count($selectedNotifications) > 0)
                    <span class="text-sm text-base-content/70">({{ count($selectedNotifications) }} selected)</span>
                @endif
            </div>
        @endif

        {{-- Notifications List --}}
        <div class="space-y-4">
            @forelse($notifications as $notification)
                <div wire:key="notification-{{ $notification->id }}"
                    class="p-4 rounded-box border border-base-300 hover:border-primary transition-all
                     {{ is_null($notification->read_at) ? 'bg-primary/5 border-primary/30' : 'bg-base-100' }}">

                    <div class="flex gap-4">
                        {{-- Checkbox --}}
                        <div class="flex-shrink-0 pt-1">
                            <input type="checkbox" wire:model.live="selectedNotifications"
                                value="{{ $notification->id }}" class="checkbox checkbox-sm" />
                        </div>

                        {{-- Icon --}}
                        <div class="flex-shrink-0">
                            <div
                                class="w-12 h-12 rounded-full flex items-center justify-center
                                {{ $notification->color === 'success' ? 'bg-success/20 text-success' : '' }}
                                {{ $notification->color === 'info' ? 'bg-info/20 text-info' : '' }}
                                {{ $notification->color === 'warning' ? 'bg-warning/20 text-warning' : '' }}
                                {{ $notification->color === 'primary' ? 'bg-primary/20 text-primary' : '' }}">
                                <x-mary-icon :name="$notification->icon" class="w-6 h-6" />
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-bold text-base">{{ $notification->subject }}</h3>
                                        @if (is_null($notification->read_at))
                                            <span class="badge badge-primary badge-sm">New</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-base-content/80 mb-2">
                                        {{ $notification->description }}
                                    </p>
                                    <p class="text-xs text-base-content/50">
                                        {{ $notification->created_at->diffForHumans() }} •
                                        {{ $notification->created_at->format('M d, Y h:i A') }}
                                    </p>
                                </div>

                                {{-- Actions --}}
                                <div class="dropdown dropdown-end">
                                    <label tabindex="0" class="btn btn-ghost btn-sm btn-circle">
                                        <x-mary-icon name="o-ellipsis-vertical" class="w-5 h-5" />
                                    </label>
                                    <ul tabindex="0"
                                        class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-52 border border-base-300">
                                        @if (is_null($notification->read_at))
                                            <li>
                                                <a wire:click="markAsRead('{{ $notification->id }}')">
                                                    <x-mary-icon name="o-check" class="w-4 h-4" />
                                                    Mark as Read
                                                </a>
                                            </li>
                                        @else
                                            <li>
                                                <a wire:click="markAsUnread('{{ $notification->id }}')">
                                                    <x-mary-icon name="o-envelope" class="w-4 h-4" />
                                                    Mark as Unread
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <x-mary-icon name="o-bell-slash" class="w-16 h-16 mx-auto mb-4 opacity-30" />
                    <h3 class="text-lg font-semibold mb-2">No notifications</h3>
                    <p class="text-sm text-base-content/70">
                        @if ($filter === 'unread')
                            You don't have any unread notifications
                        @elseif($filter === 'read')
                            You don't have any read notifications
                        @else
                            You don't have any notifications yet
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($notifications->hasPages())
            <div class="mt-6 pt-6 border-t border-base-300">
                {{ $notifications->links() }}
            </div>
        @endif
    </x-mary-card>
</div>
