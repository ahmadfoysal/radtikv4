<div class="max-w-7xl mx-auto">
    <x-mary-card title="User List" class="rounded-xl shadow-sm border border-base-300">

        {{-- Toolbar --}}
        <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-end sm:justify-end gap-4 mb-6">
            {{-- Search --}}
            <x-mary-input placeholder="Search by name, email, phone, address…" icon="o-magnifying-glass"
                wire:model.live.debounce.300ms="search" class="w-full sm:w-72" />

            {{-- Per page --}}
            <x-mary-select :options="[
                ['id' => 10, 'name' => '10'],
                ['id' => 25, 'name' => '25'],
                ['id' => 50, 'name' => '50'],
                ['id' => 100, 'name' => '100'],
            ]" option-value="id" option-label="name" wire:model.live="perPage"
                class="w-32 sm:text-right" />

            {{-- Buttons --}}
            <div class="flex justify-end gap-2 w-full sm:w-auto">
                <x-mary-button label="Clear" icon="o-x-mark" class="btn-ghost" wire:click="$set('search','')" />
                <x-mary-button label="New User" icon="o-plus" class="btn-primary" wire:navigate
                    href="{{ route('users.create') }}" />
            </div>
        </div>


        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border border-base-200 rounded-lg overflow-hidden">
                <thead>
                    <tr class="bg-base-200 text-base-content/80 text-center">
                        <th class="px-4 py-3 cursor-pointer text-left" wire:click="sortBy('name')">
                            <div class="flex items-center gap-2">
                                Name
                                <x-mary-icon :name="$this->getSortIcon('name')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 cursor-pointer text-left" wire:click="sortBy('email')">
                            <div class="flex items-center gap-2">
                                Email
                                <x-mary-icon :name="$this->getSortIcon('email')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 cursor-pointer text-left" wire:click="sortBy('phone')">
                            <div class="flex items-center gap-2">
                                Phone
                                <x-mary-icon :name="$this->getSortIcon('phone')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left">Address</th>
                        <th class="px-4 py-3 cursor-pointer text-left" wire:click="sortBy('created_at')">
                            <div class="flex items-center gap-2">
                                Created
                                <x-mary-icon :name="$this->getSortIcon('created_at')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $user)
                        <tr class="hover:bg-base-200/40 border-t border-base-200 text-center">
                            <td class="px-4 py-3 text-left font-medium">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-left">{{ $user->email }}</td>
                            <td class="px-4 py-3">{{ $user->phone }}</td>
                            <td class="px-4 py-3 text-left">{{ $user->address }}</td>
                            <td class="px-4 py-3">{{ $user->created_at?->format('d-m-Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    {{-- Edit Icon --}}
                                    <a href="{{ route('users.edit', $user) }}" wire:navigate
                                        class="text-primary hover:text-primary/80 transition-colors" title="Edit">
                                        <x-mary-icon name="o-pencil-square" class="w-5 h-5" />
                                    </a>

                                    {{-- Delete Icon --}}
                                    <button wire:click="delete({{ $user->id }})" wire:loading.attr="disabled"
                                        class="relative text-error hover:text-error/80 transition-colors" title="Delete"
                                        onclick="return confirm('Are you sure you want to delete {{ $user->name }}?')">
                                        {{-- Trash icon (visible when not deleting) --}}
                                        <x-mary-icon name="o-trash" class="w-5 h-5" wire:loading.remove
                                            wire:target="delete({{ $user->id }})" />

                                        {{-- MaryUI loader (visible while deleting this user) --}}
                                        <x-mary-loading wire:loading wire:target="delete({{ $user->id }})"
                                            class="w-5 h-5 text-error" />
                                    </button>


                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-base-content/70">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div class="mt-6 flex flex-col sm:flex-row items-center justify-center sm:justify-between gap-3">
            <div class="text-sm text-base-content/70 text-center sm:text-left">
                Showing
                <span class="font-medium">{{ $users->firstItem() }}</span>
                to
                <span class="font-medium">{{ $users->lastItem() }}</span>
                of
                <span class="font-medium">{{ $users->total() }}</span>
                results
            </div>
            <div>
                {{ $users->links() }}
            </div>
        </div>
    </x-mary-card>
</div>
