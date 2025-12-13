<div class="max-w-7xl mx-auto">
    <x-mary-card title="Payment Gateway Settings" separator class=" bg-base-100">
        <p class="text-sm text-base-content/70 mb-6">
            Configure payment gateway credentials and manage their status. Only active gateways will be available for users.
        </p>

        @foreach($gateways as $gateway)
            <div class="mb-6 border border-base-300 p-6 bg-base-100" wire:key="gateway-{{ $gateway['id'] }}">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">{{ $gateway['name'] }}</h3>
                        <p class="text-sm text-base-content/60">{{ $gateway['class'] }}</p>
                    </div>
                    <div>
                        <x-mary-toggle 
                            wire:model.live="gateways.{{ $loop->index }}.is_active"
                            wire:change="toggleActive({{ $gateway['id'] }})"
                            :label="$gateway['is_active'] ? 'Active' : 'Inactive'"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach($gateway['data'] as $key => $value)
                        <div>
                            <x-mary-input 
                                label="{{ ucfirst(str_replace('_', ' ', $key)) }}" 
                                wire:model.live.debounce.500ms="gateways.{{ $loop->parent->index }}.data.{{ $key }}"
                                placeholder="Enter {{ str_replace('_', ' ', $key) }}"
                                :type="str_contains($key, 'password') || str_contains($key, 'secret') || str_contains($key, 'key') ? 'password' : 'text'"
                            />
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <x-mary-button 
                        label="Save {{ $gateway['name'] }} Credentials" 
                        wire:click="saveCredentials({{ $gateway['id'] }}, {{ json_encode($gateway['data']) }})"
                        class="btn-primary btn-sm"
                        icon="o-check"
                        spinner="saveCredentials({{ $gateway['id'] }}, *)"
                    />
                </div>
            </div>
        @endforeach

        @if(empty($gateways))
            <div class="text-center py-8">
                <p class="text-base-content/70">No payment gateways configured. Please run the seeder.</p>
            </div>
        @endif
    </x-mary-card>
</div>
