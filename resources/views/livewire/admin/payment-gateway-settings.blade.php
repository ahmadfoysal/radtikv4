<div class="max-w-7xl mx-auto">
    <x-mary-card title="Payment Gateway Settings" separator class="bg-base-100">
        <p class="text-sm text-base-content/70 mb-6">
            <span class="font-semibold text-warning">Superadmin Access:</span> Configure payment gateway credentials and
            manage their status. Only active gateways will be available for users to make payments.
        </p>

        @foreach ($gateways as $gateway)
            <div class="mb-6 border border-base-300 rounded-lg p-6 bg-base-100" wire:key="gateway-{{ $gateway['id'] }}">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">{{ $gateway['name'] }}</h3>
                        <p class="text-sm text-base-content/60">{{ $gateway['class'] }}</p>
                    </div>
                    <div>
                        <x-mary-toggle wire:model.live="gateways.{{ $loop->index }}.is_active"
                            wire:change="toggleActive({{ $gateway['id'] }})" :label="$gateway['is_active'] ? 'Active' : 'Inactive'"
                            class="{{ $gateway['is_active'] ? 'toggle-success' : 'toggle-error' }}" />
                    </div>
                </div>

                @if (!empty($gateway['data']))
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($gateway['data'] as $key => $value)
                            <div>
                                <x-mary-input label="{{ ucfirst(str_replace('_', ' ', $key)) }}"
                                    wire:model.live.debounce.500ms="gateways.{{ $loop->parent->index }}.data.{{ $key }}"
                                    placeholder="Enter {{ str_replace('_', ' ', $key) }}" :type="str_contains($key, 'password') ||
                                    str_contains($key, 'secret') ||
                                    str_contains($key, 'key')
                                        ? 'password'
                                        : 'text'" />
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <x-mary-button label="Save {{ $gateway['name'] }} Credentials"
                            wire:click="saveCredentials({{ $gateway['id'] }})" class="btn-primary btn-sm"
                            icon="o-check" spinner="saveCredentials({{ $gateway['id'] }})" />
                    </div>
                @else
                    <div class="p-4 bg-warning/10 border border-warning rounded-lg">
                        <p class="text-sm text-warning">No configuration fields available for this gateway.</p>
                    </div>
                @endif
            </div>
        @endforeach

        @if (empty($gateways))
            <div class="text-center py-8">
                <p class="text-base-content/70 mb-4">No payment gateways configured. Please run the seeder to initialize
                    gateway configurations.</p>
                <div class="p-4 bg-info/10 border border-info rounded-lg">
                    <p class="text-sm text-info font-medium">Run the following command:</p>
                    <code class="text-xs bg-base-200 px-2 py-1 rounded mt-2 inline-block">php artisan db:seed
                        --class=PaymentGatewaySeeder</code>
                </div>
            </div>
        @endif
    </x-mary-card>
</div>
