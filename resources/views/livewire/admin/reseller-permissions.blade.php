<x-mary-card title="Reseller Permissions" separator class="bg-base-100">
    <div class="space-y-6">
        <p class="text-sm text-base-content/70">
            Select a reseller and assign router management permissions. Permissions control what actions resellers can perform on their assigned routers.
        </p>

        {{-- Reseller Selection --}}
        <div class="space-y-4">
            <div>
                <x-mary-choices
                    label="Select Reseller"
                    wire:model.live="resellerId"
                    :options="$resellerOptions"
                    placeholder="Search reseller..."
                    single
                    searchable clearable />
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

        {{-- Permissions List --}}
        @if($resellerId)
            <div class="space-y-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold">Router, Voucher & Hotspot Permissions</p>
                    @php
                        $allSelected = $this->areAllPermissionsSelected();
                    @endphp
                    <x-mary-button 
                        :label="$allSelected ? 'Deselect All' : 'Select All'" 
                        :icon="$allSelected ? 'o-x-circle' : 'o-check-circle'"
                        class="btn-sm btn-outline" 
                        wire:click="selectAllPermissions" />
                </div>
                <div class="space-y-6 max-h-96 overflow-y-auto">
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
                            <p class="text-xs font-semibold text-base-content/70 mb-3 uppercase">
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
                            <div class="flex flex-wrap gap-3">
                                @foreach($permissions as $permission)
                                    <label class="flex items-center gap-2 px-3 py-2 border border-base-300 cursor-pointer transition-colors">
                                        <input 
                                            type="checkbox" 
                                            wire:model="selectedPermissions"
                                            value="{{ $permission['name'] }}"
                                            class="checkbox checkbox-primary checkbox-sm" />
                                        <span class="text-sm font-medium whitespace-nowrap">{{ str_replace('_', ' ', ucwords($permission['name'], '_')) }}</span>
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

