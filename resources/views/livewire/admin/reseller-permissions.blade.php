<x-mary-card title="Reseller Permissions" separator class="bg-base-100">
    <div class="space-y-6">
        <p class="text-sm text-base-content/70">
            Select a reseller and assign router management permissions. Permissions control what actions resellers can perform on their assigned routers.
        </p>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Left: Reseller Selection --}}
            <div class="space-y-4">
                <div>
                    <x-mary-select 
                        label="Select Reseller" 
                        icon="o-user-group" 
                        wire:model.live="resellerId"
                        :options="$resellerOptions"
                        option-label="name"
                        option-value="id"
                        placeholder="Choose a reseller"
                        wire:search="searchResellers" />
                </div>

                @if($resellerId)
                    <div class="p-4 bg-base-200 border border-base-300">
                        <p class="text-sm font-semibold mb-2">Selected Reseller</p>
                        @php
                            $selectedReseller = collect($resellerOptions)->firstWhere('id', $resellerId);
                        @endphp
                        @if($selectedReseller)
                            <p class="text-sm">{{ $selectedReseller['name'] }}</p>
                            <p class="text-xs text-base-content/60">{{ $selectedReseller['email'] ?? 'N/A' }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Right: Permissions List --}}
            <div class="space-y-4">
                @if($resellerId)
                    <div>
                        <p class="text-sm font-semibold mb-3">Router, Voucher & Hotspot Permissions</p>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            @php
                                $groupedPermissions = collect($routerPermissions)->groupBy(function($permission) {
                                    $name = $permission['name'];
                                    if (str_contains($name, 'router')) {
                                        return 'router';
                                    } elseif (str_contains($name, 'voucher') || str_contains($name, 'generate')) {
                                        return 'voucher';
                                    } elseif (str_contains($name, 'session') || str_contains($name, 'hotspot') || str_contains($name, 'user')) {
                                        return 'hotspot';
                                    }
                                    return 'other';
                                });
                            @endphp

                            @foreach($groupedPermissions as $group => $permissions)
                                <div>
                                    <p class="text-xs font-semibold text-base-content/70 mb-2 uppercase">
                                        @if($group === 'router')
                                            Router Management
                                        @elseif($group === 'voucher')
                                            Voucher Management
                                        @elseif($group === 'hotspot')
                                            Hotspot User Management
                                        @else
                                            Other Permissions
                                        @endif
                                    </p>
                                    <div class="space-y-2">
                                        @foreach($permissions as $permission)
                                            <label class="flex items-start gap-3 p-3 border border-base-300 bg-base-200 hover:bg-base-300 cursor-pointer transition-colors">
                                                <input 
                                                    type="checkbox" 
                                                    wire:model="selectedPermissions"
                                                    value="{{ $permission['name'] }}"
                                                    class="checkbox checkbox-primary checkbox-sm mt-1" />
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium">{{ str_replace('_', ' ', ucwords($permission['name'], '_')) }}</p>
                                                    @if(isset($permission['description']))
                                                        <p class="text-xs text-base-content/60 mt-1">{{ $permission['description'] }}</p>
                                                    @endif
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            @if($routerPermissions === [])
                                <p class="text-sm text-base-content/70 text-center py-4">No permissions found.</p>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="p-8 text-center border border-base-300 bg-base-200">
                        <x-mary-icon name="o-shield-check" class="w-12 h-12 mx-auto mb-3 opacity-50" />
                        <p class="text-sm text-base-content/70">Select a reseller to manage permissions</p>
                    </div>
                @endif
            </div>
        </div>

        @if($resellerId)
            <div class="flex items-center justify-between pt-4 border-t border-base-300">
                <div class="text-sm text-base-content/60">
                    <span class="font-semibold">{{ count($selectedPermissions) }}</span> permission(s) selected
                </div>
                <div class="flex gap-2">
                    <x-mary-button 
                        label="Cancel" 
                        class="btn-ghost" 
                        wire:click="$set('resellerId', null)" />
                    <x-mary-button 
                        label="Save Permissions" 
                        icon="o-check" 
                        class="btn-primary" 
                        wire:click="savePermissions" 
                        spinner="savePermissions" />
                </div>
            </div>
        @endif
    </div>
</x-mary-card>

