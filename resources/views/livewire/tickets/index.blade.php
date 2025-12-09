<div class="max-w-7xl mx-auto">
    <x-mary-card title="Support Tickets" class="rounded-xl shadow-sm border border-base-300">

        {{-- Toolbar --}}
        <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-end sm:justify-between gap-4 mb-6">
            {{-- Status Filter --}}
            <div class="flex items-center gap-2">
                <div class="join">
                    <button class="btn btn-sm join-item {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-ghost' }}"
                        wire:click="$set('statusFilter','all')">
                        All
                    </button>
                    <button class="btn btn-sm join-item {{ $statusFilter === 'open' ? 'btn-primary' : 'btn-ghost' }}"
                        wire:click="$set('statusFilter','open')">
                        Open
                    </button>
                    <button
                        class="btn btn-sm join-item {{ $statusFilter === 'in_progress' ? 'btn-primary' : 'btn-ghost' }}"
                        wire:click="$set('statusFilter','in_progress')">
                        In Progress
                    </button>
                    <button class="btn btn-sm join-item {{ $statusFilter === 'solved' ? 'btn-primary' : 'btn-ghost' }}"
                        wire:click="$set('statusFilter','solved')">
                        Solved
                    </button>
                    <button class="btn btn-sm join-item {{ $statusFilter === 'closed' ? 'btn-primary' : 'btn-ghost' }}"
                        wire:click="$set('statusFilter','closed')">
                        Closed
                    </button>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-2 w-full sm:w-auto">
                <x-mary-button label="New Ticket" icon="o-plus" class="btn-primary"
                    wire:click="openCreateModal" />
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border border-base-200 rounded-lg overflow-hidden">
                <thead>
                    <tr class="bg-base-200 text-base-content/80">
                        <th class="px-4 py-3 text-left">Subject</th>
                        <th class="px-4 py-3 text-left">Owner</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Priority</th>
                        <th class="px-4 py-3 text-left">Created</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr class="hover:bg-base-200/40 border-t border-base-200">
                            <td class="px-4 py-3 text-left font-medium">{{ $ticket->subject }}</td>
                            <td class="px-4 py-3 text-left">{{ $ticket->owner->name }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="badge badge-sm
                                    @if ($ticket->status === 'open') badge-info
                                    @elseif($ticket->status === 'in_progress') badge-warning
                                    @elseif($ticket->status === 'solved') badge-success
                                    @else badge-ghost @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($ticket->priority)
                                    <span
                                        class="badge badge-sm
                                        @if ($ticket->priority === 'high') badge-error
                                        @elseif($ticket->priority === 'normal') badge-primary
                                        @else badge-ghost @endif
                                    ">
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $ticket->created_at->format('d-m-Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('tickets.show', $ticket) }}" wire:navigate
                                        class="text-primary hover:text-primary/80 transition-colors" title="View">
                                        <x-mary-icon name="o-eye" class="w-5 h-5" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-base-content/70">
                                No tickets found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $tickets->links() }}
        </div>
    </x-mary-card>

    {{-- Create Modal --}}
    <x-mary-modal wire:model="showCreateModal" title="Create New Ticket" separator>
        <div class="space-y-4">
            <x-mary-input label="Subject" wire:model="subject" icon="o-document-text" />

            <x-mary-textarea label="Description" wire:model="description" rows="5" />

            <x-mary-select label="Priority" wire:model="priority" :options="[
                ['id' => 'low', 'name' => 'Low'],
                ['id' => 'normal', 'name' => 'Normal'],
                ['id' => 'high', 'name' => 'High'],
            ]" option-label="name"
                option-value="id" icon="o-flag" />

            @if (auth()->user()->isSuperAdmin())
                <x-mary-select label="Ticket Owner" wire:model="owner_id" :options="$users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->toArray()"
                    option-label="name" option-value="id" placeholder="Select owner" icon="o-user" />

                <x-mary-select label="Assign To" wire:model="assigned_to" :options="$users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->toArray()"
                    option-label="name" option-value="id" placeholder="Assign to (optional)" icon="o-user-circle" />
            @endif
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" wire:click="closeCreateModal" />
            <x-mary-button label="Create Ticket" class="btn-primary" icon="o-plus" wire:click="create"
                spinner="create" />
        </x-slot:actions>
    </x-mary-modal>
</div>
