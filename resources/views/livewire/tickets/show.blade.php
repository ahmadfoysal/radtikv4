<div class="max-w-4xl mx-auto">
    <x-mary-card title="Ticket Details" separator class="rounded-2xl bg-base-200">

        {{-- Back Button --}}
        <div class="mb-4">
            <x-mary-button label="Back to Tickets" icon="o-arrow-left" class="btn-ghost btn-sm"
                href="{{ route('tickets.index') }}" wire:navigate />
        </div>

        {{-- Ticket Information --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Subject --}}
            <div class="md:col-span-2">
                <label class="text-sm font-medium opacity-70">Subject</label>
                <div class="text-lg font-semibold mt-1">{{ $ticket->subject }}</div>
            </div>

            {{-- Description --}}
            <div class="md:col-span-2">
                <label class="text-sm font-medium opacity-70">Description</label>
                <div class="mt-1 p-4 bg-base-100 rounded-lg">
                    {{ $ticket->description }}
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label class="text-sm font-medium opacity-70">Status</label>
                @if ($editMode && auth()->user()->isSuperAdmin())
                    <x-mary-select wire:model="status" :options="[
                        ['id' => 'open', 'name' => 'Open'],
                        ['id' => 'in_progress', 'name' => 'In Progress'],
                        ['id' => 'solved', 'name' => 'Solved'],
                        ['id' => 'closed', 'name' => 'Closed'],
                    ]" option-label="name" option-value="id"
                        class="mt-1" />
                @else
                    <div class="mt-1">
                        <span
                            class="badge
                            @if ($ticket->status === 'open') badge-info
                            @elseif($ticket->status === 'in_progress') badge-warning
                            @elseif($ticket->status === 'solved') badge-success
                            @else badge-ghost @endif
                        ">
                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Priority --}}
            <div>
                <label class="text-sm font-medium opacity-70">Priority</label>
                <div class="mt-1">
                    @if ($ticket->priority)
                        <span
                            class="badge
                            @if ($ticket->priority === 'high') badge-error
                            @elseif($ticket->priority === 'normal') badge-primary
                            @else badge-ghost @endif
                        ">
                            {{ ucfirst($ticket->priority) }}
                        </span>
                    @else
                        <span class="opacity-50">Not set</span>
                    @endif
                </div>
            </div>

            {{-- Owner --}}
            <div>
                <label class="text-sm font-medium opacity-70">Ticket Owner</label>
                <div class="mt-1 font-medium">{{ $ticket->owner->name }}</div>
            </div>

            {{-- Created By --}}
            <div>
                <label class="text-sm font-medium opacity-70">Created By</label>
                <div class="mt-1 font-medium">{{ $ticket->creator->name }}</div>
            </div>

            {{-- Assigned To --}}
            <div>
                <label class="text-sm font-medium opacity-70">Assigned To</label>
                @if ($editMode && auth()->user()->isSuperAdmin())
                    <x-mary-select wire:model="assigned_to" :options="$users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->toArray()"
                        option-label="name" option-value="id" placeholder="Select assignee" class="mt-1" />
                @else
                    <div class="mt-1 font-medium">
                        {{ $ticket->assignee ? $ticket->assignee->name : 'Not assigned' }}
                    </div>
                @endif
            </div>

            {{-- Created At --}}
            <div>
                <label class="text-sm font-medium opacity-70">Created At</label>
                <div class="mt-1">{{ $ticket->created_at->format('d-m-Y H:i') }}</div>
            </div>

            {{-- Solved At --}}
            @if ($ticket->solved_at)
                <div>
                    <label class="text-sm font-medium opacity-70">Solved At</label>
                    <div class="mt-1">{{ $ticket->solved_at->format('d-m-Y H:i') }}</div>
                </div>
            @endif

            {{-- Closed At --}}
            @if ($ticket->closed_at)
                <div>
                    <label class="text-sm font-medium opacity-70">Closed At</label>
                    <div class="mt-1">{{ $ticket->closed_at->format('d-m-Y H:i') }}</div>
                </div>
            @endif
        </div>

        {{-- Actions --}}
        @can('update', $ticket)
            <x-slot:actions>
                @if ($editMode)
                    <x-mary-button label="Cancel" class="btn-ghost" wire:click="toggleEditMode" />
                    <x-mary-button label="Save Changes" class="btn-primary" icon="o-check" wire:click="updateTicket"
                        spinner="updateTicket" />
                @else
                    <x-mary-button label="Edit" class="btn-outline" icon="o-pencil" wire:click="toggleEditMode" />
                    @if (!$ticket->isSolved())
                        <x-mary-button label="Mark as Solved" class="btn-success" icon="o-check-circle"
                            wire:click="markAsSolved" spinner="markAsSolved" />
                    @endif
                @endif
            </x-slot:actions>
        @endcan
    </x-mary-card>
</div>
