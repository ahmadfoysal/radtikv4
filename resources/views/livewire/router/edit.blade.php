@php
    use Illuminate\Support\Facades\Storage;
@endphp
<x-mary-card title="Edit Router" separator class="max-w-4xl mx-auto bg-base-100">
    <x-mary-form wire:submit="update">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-mary-input label="Name" wire:model.live.debounce.500ms="name" placeholder="Office Core Router" />
            </div>

            <div>
                <x-mary-input label="IP / Host" wire:model.live.debounce.500ms="address"
                    placeholder="192.168.0.1 or router.example.com" />
            </div>

            <div>
                <x-mary-input label="API Port" type="number" min="1" max="65535"
                    wire:model.live.debounce.500ms="port" />
            </div>

            <div>
                <x-mary-input label="Login Address" wire:model.live.debounce.500ms="login_address"
                    placeholder="http://router.local or 10.0.0.1" />
            </div>

            <div>
                <x-mary-input label="Username" wire:model.live.debounce.500ms="username" />
            </div>

            <div>
                <x-mary-password label="Password" type="password" wire:model.live.debounce.500ms="password" right />
            </div>

            <div>
                <x-mary-select label="Voucher Template" wire:model.live="voucher_template_id" :options="$voucherTemplates->map(fn($t) => ['id' => $t->id, 'name' => $t->name])->toArray()"
                    option-label="name" option-value="id" placeholder="Select a voucher template" />
            </div>

            <div>
                @php
                    $packageOptions = $packages->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name . ' (' . ucfirst($p->billing_cycle) . ')',
                    ]);

                    if (! empty($storedPackage['id'] ?? null) && ! $packageOptions->contains(fn($opt) => $opt['id'] === $storedPackage['id'])) {
                        $packageOptions->prepend([
                            'id' => $storedPackage['id'],
                            'name' => ($storedPackage['name'] ?? 'Saved Package') . ' (saved)',
                        ]);
                    }
                @endphp

                <x-mary-select label="Subscription Package" wire:model.live="package_id"
                    :options="$packageOptions->toArray()" option-label="name" option-value="id"
                    placeholder="Select a package (optional)" />
            </div>

            <div>
                <x-mary-input label="Monthly Expense" type="number" min="0" step="0.01"
                    wire:model.live.debounce.500ms="monthly_expense" placeholder="0.00" />
            </div>

            <div>
                <x-mary-file label="Logo" wire:model="logo" accept="image/*" />
                @error('logo')
                    <div class="text-error text-sm mt-1">{{ $message }}</div>
                @enderror
                <div class="mt-2 flex items-center gap-4">
                    @if ($logo)
                        <div>
                            <p class="text-xs text-base-content/60 mb-1">New Logo Preview:</p>
                            <img src="{{ $logo->temporaryUrl() }}" alt="New logo preview" class="h-20 w-20 object-contain border border-base-300" />
                        </div>
                    @endif
                    @if ($router->logo && !$logo)
                        <div>
                            <p class="text-xs text-base-content/60 mb-1">Current Logo:</p>
                            <img src="{{ Storage::url($router->logo) }}" alt="Current logo" class="h-20 w-20 object-contain border border-base-300" />
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" type="button" wire:click="cancel" />
            <x-mary-button label="Update Router" class="btn-primary" type="submit" spinner="update" />
        </x-slot:actions>
    </x-mary-form>
</x-mary-card>
