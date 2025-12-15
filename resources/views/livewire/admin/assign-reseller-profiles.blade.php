<div class="max-w-5xl mx-auto space-y-6">
    <x-mary-card title="Assign Profiles" separator class=" bg-base-100">
        <p class="text-sm text-base-content/70 mb-6">
            Select one of your resellers and choose which profiles they can work with. Assignments are limited to
            profiles that belong to you.
        </p>

        <x-mary-form wire:submit.prevent="saveAssignments" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="lg:col-span-1">
                    {{-- Notice custom search-function --}}
                    <x-mary-choices wire:key="reseller-select" label="Reseller" placeholder="Search reseller..."
                        wire:model.live="resellerId" :options="$resellerOptions" option-label="name" option-sub-label="email"
                        option-value="id" search-function="searchResellers" no-result-text="No reseller found" single
                        searchable clearable no-progress />
                </div>

                <div class="lg:col-span-1">
                    @if ($resellerId)
                        <x-mary-choices wire:key="profile-select-{{ $resellerId }}" label="Profiles"
                            placeholder="Search profiles..." wire:model.live="selectedProfileIds" :options="$profileOptions"
                            option-label="name" option-sub-label="detail" option-value="id"
                            search-function="searchProfiles" no-result-text="No profile found" multiple searchable
                            clearable no-progress />
                        <p class="mt-2 text-xs text-base-content/70">
                            Assign multiple profiles at once. Removing a profile from the selection detaches it from the
                            reseller.
                        </p>
                    @else
                        <div
                            class=" border border-dashed border-base-300 p-4 bg-base-100/60 text-sm text-base-content/70">
                            Select a reseller on the left to load profiles you can assign.
                        </div>
                    @endif
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button type="button" label="Reset" class="btn-ghost" icon="o-arrow-path"
                    wire:click="$set('selectedProfileIds', [])" />
                <x-mary-button type="submit" label="Save Assignments" class="btn-primary" icon="o-check"
                    spinner="saveAssignments" :disabled="!$resellerId" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-card>

    <x-mary-card title="Current Assignments" class=" bg-base-100">
        @if (!$resellerId)
            <p class="text-sm text-base-content/70">Select a reseller to view assigned profiles.</p>
        @else
            <div class="space-y-4">
                @forelse($assignedProfiles as $profile)
                    <div class=" border border-base-300 bg-base-100/80 p-4" wire:key="assigned-{{ $profile['id'] }}">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-semibold text-base-content">{{ $profile['name'] }}</p>
                                <p class="text-sm text-base-content/70">
                                    {{ $profile['rate_limit'] ?? 'No rate limit set' }}</p>
                            </div>
                            <div class="text-right text-xs text-base-content/60">
                                <p>{{ $profile['assigned_at'] ?? '-' }}</p>
                                @if ($profile['assigned_by'])
                                    <p>By {{ $profile['assigned_by'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-base-content/70">No profiles assigned yet for this reseller.</p>
                @endforelse
            </div>
        @endif
    </x-mary-card>
</div>
