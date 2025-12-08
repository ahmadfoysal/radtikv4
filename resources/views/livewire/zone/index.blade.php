<div class="max-w-7xl mx-auto space-y-6">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Form --}}
        <x-mary-card :title="$zoneId ? 'Edit Zone' : 'Create Zone'" class="rounded-2xl border border-base-300">
            <x-mary-form wire:submit="save" class="space-y-4">
                <x-mary-input label="Zone Name" placeholder="Enter zone name" wire:model.live.debounce.400ms="name"
                    required />

                <x-mary-textarea label="Description" rows="3" placeholder="Coverage info or notes"
                    wire:model.live.debounce.400ms="description" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="form-control w-full">
                        <div class="label">
                            <span class="label-text text-sm font-semibold">Color tag</span>
                        </div>
                        <input type="color" wire:model.live="color"
                            class="input input-bordered h-12 w-full cursor-pointer" />
                        @error('color')
                            <span class="mt-1 text-sm text-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <div class="flex items-end">
                        <x-mary-toggle label="Active" wire:model.live="is_active" />
                    </div>
                </div>

                <x-slot:actions>
                    @if ($zoneId)
                        <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancelEdit" />
                        <x-mary-button label="Update Zone" class="btn-primary" type="submit" spinner="save" />
                    @else
                        <x-mary-button label="Create Zone" class="btn-primary w-full" type="submit" spinner="save" />
                    @endif
                </x-slot:actions>
            </x-mary-form>
        </x-mary-card>

        {{-- List --}}
        <div class="lg:col-span-2">
            <x-mary-card title="Zones" class="rounded-2xl border border-base-300">
                <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <x-mary-input placeholder="Search zone…" icon="o-magnifying-glass"
                        wire:model.live.debounce.400ms="search" class="w-full sm:w-80" />

                    <div class="flex items-center gap-3">
                        <x-mary-select label="Per page" :options="[
                            ['id' => 5, 'name' => '5'],
                            ['id' => 10, 'name' => '10'],
                            ['id' => 25, 'name' => '25'],
                            ['id' => 50, 'name' => '50'],
                        ]" option-value="id" option-label="name" wire:model.live="perPage" class="w-32" />
                        <x-mary-button label="Clear" class="btn-ghost" icon="o-x-mark"
                            wire:click="$set('search','')" />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-base-200 text-left text-xs font-semibold uppercase tracking-wide text-base-content/70">
                                <th class="px-4 py-3">Zone</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3 text-center">Routers</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Created</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($zones as $zone)
                                <tr class="border-t border-base-200 hover:bg-base-200/50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="h-4 w-4 rounded-full border border-base-200"
                                                style="background-color: {{ $zone->color ?? '#2563eb' }}"></span>
                                            <div>
                                                <div class="font-semibold text-base-content">{{ $zone->name }}</div>
                                                <div class="text-xs uppercase tracking-wider text-base-content/50">
                                                    {{ $zone->slug }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-base-content/80">
                                        {{ $zone->description ? \Illuminate\Support\Str::limit($zone->description, 70) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-base-content">
                                        {{ $zone->routers_count }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($zone->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-error">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-base-content/70">
                                        {{ $zone->created_at?->format('M d, Y') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <x-mary-button icon="o-pencil" class="btn-ghost btn-xs"
                                                wire:click="edit({{ $zone->id }})"
                                                spinner="edit({{ $zone->id }})" />

                                            <x-mary-button icon="o-trash"
                                                class="btn-ghost btn-xs text-error hover:text-error/80"
                                                wire:click="delete({{ $zone->id }})"
                                                spinner="delete({{ $zone->id }})" wire:loading.attr="disabled"
                                                onclick="return confirm('Delete {{ $zone->name }}?')" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-base-content/60">
                                        No zones found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $zones->links() }}
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
