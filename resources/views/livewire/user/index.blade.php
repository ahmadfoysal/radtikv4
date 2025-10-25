<div class="max-w-7xl mx-auto">
    <x-mary-card>
        <x-slot:title>Users</x-slot:title>

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-end gap-4 mb-4">
            <x-mary-input label="Search users" placeholder="Search by name, email, phone, address…"
                icon="o-magnifying-glass" wire:model.live.debounce.300ms="search" class="w-72" />

            <x-mary-select label="Per page" :options="[
                ['id' => 10, 'name' => '10'],
                ['id' => 25, 'name' => '25'],
                ['id' => 50, 'name' => '50'],
                ['id' => 100, 'name' => '100'],
            ]" option-value="id" option-label="name"
                wire:model.live="perPage" class="w-32" />

            <div class="ml-auto flex gap-2">
                <x-mary-button label="Clear" icon="o-x-mark" class="btn-ghost" wire:click="$set('search','')" />
                <x-mary-button label="New User" icon="o-plus" class="btn-primary" wire:navigate
                    href="{{ route('users.create') }}" />
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto rounded-lg">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-base-200 text-base-content/80">
                        <th class="px-4 py-3 cursor-pointer" wire:click="sortBy('name')">
                            <div class="flex items-center gap-2">
                                Name
                                <x-mary-icon :name="$this->getSortIcon('name')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 cursor-pointer" wire:click="sortBy('email')">
                            <div class="flex items-center gap-2">
                                Email
                                <x-mary-icon :name="$this->getSortIcon('email')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3 cursor-pointer" wire:click="sortBy('phone')">
                            <div class="flex items-center gap-2">
                                Phone
                                <x-mary-icon :name="$this->getSortIcon('phone')" class="w-4 h-4" />
                            </div>
                        </th>
                        <th class="px-4 py-3">Address</th>
                        <th class="px-4 py-3 cursor-pointer" wire:click="sortBy('created_at')">
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
                        <tr class="hover:bg-base-200/50">
                            <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">{{ $user->phone }}</td>
                            <td class="px-4 py-3">{{ $user->address }}</td>
                            <td class="px-4 py-3">
                                {{ $user->created_at?->format('Y-m-d') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">

                                    <x-mary-button size="sm" label="Edit" class="btn-ghost" wire:navigate
                                        href="{{ route('users.edit', $user) }}" />
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

        {{-- Footer / Pagination --}}
        <div class="mt-4 flex items-center justify-between gap-4">
            <div class="text-sm text-base-content/70">
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
