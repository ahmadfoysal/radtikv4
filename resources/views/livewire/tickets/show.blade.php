<div class="max-w-4xl mx-auto">
    <x-mary-card title="Ticket Details" separator class=" bg-base-100">

        {{-- Back Button --}}
        <div class="mb-3">
            <x-mary-button label="Back to Tickets" icon="o-arrow-left" class="btn-ghost btn-sm"
                href="{{ route('tickets.index') }}" wire:navigate />
        </div>

        {{-- Ticket Information - Compressed --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            {{-- Subject --}}
            <div class="col-span-2 md:col-span-3">
                <label class="text-xs font-medium opacity-70">Subject</label>
                <div class="font-semibold">{{ $ticket->subject }}</div>
            </div>

            {{-- Description --}}
            <div class="col-span-2 md:col-span-3">
                <label class="text-xs font-medium opacity-70">Description</label>
                <div class="p-2 bg-base-100 rounded text-xs">
                    {{ $ticket->description }}
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label class="text-xs font-medium opacity-70">Status</label>
                @if ($editMode && auth()->user()->isSuperAdmin())
                    <x-mary-select wire:model="status" :options="[
                        ['id' => 'open', 'name' => 'Open'],
                        ['id' => 'in_progress', 'name' => 'In Progress'],
                        ['id' => 'solved', 'name' => 'Solved'],
                        ['id' => 'closed', 'name' => 'Closed'],
                    ]" option-label="name" option-value="id" class="select-sm" />
                @else
                    <div class="mt-1">
                        <span
                            class="badge badge-sm
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
                <label class="text-xs font-medium opacity-70">Priority</label>
                <div class="mt-1">
                    @if ($ticket->priority)
                        <span
                            class="badge badge-sm
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
                <label class="text-xs font-medium opacity-70">Owner</label>
                <div class="mt-1">{{ $ticket->owner->name }}</div>
            </div>

            {{-- Assigned To --}}
            <div>
                <label class="text-xs font-medium opacity-70">Assigned To</label>
                @if ($editMode && auth()->user()->isSuperAdmin())
                    <x-mary-select wire:model="assigned_to" :options="$users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->toArray()"
                        option-label="name" option-value="id" placeholder="Select" class="select-sm" />
                @else
                    <div class="mt-1">
                        {{ $ticket->assignee ? $ticket->assignee->name : 'Not assigned' }}
                    </div>
                @endif
            </div>

            {{-- Created At --}}
            <div>
                <label class="text-xs font-medium opacity-70">Created</label>
                <div class="mt-1">{{ $ticket->created_at->format('d-m-Y H:i') }}</div>
            </div>

            {{-- Solved/Closed At --}}
            @if ($ticket->solved_at || $ticket->closed_at)
                <div>
                    <label class="text-xs font-medium opacity-70">
                        @if ($ticket->solved_at) Solved @else Closed @endif
                    </label>
                    <div class="mt-1">
                        {{ ($ticket->solved_at ?? $ticket->closed_at)->format('d-m-Y H:i') }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Actions --}}
        <x-slot:actions>
            @can('update', $ticket)
                {{-- Status Dropdown --}}
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-sm btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Status
                    </label>
                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100  w-40">
                        <li><a wire:click="changeStatus('open')">Open</a></li>
                        <li><a wire:click="changeStatus('in_progress')">In Progress</a></li>
                        <li><a wire:click="changeStatus('solved')">Solved</a></li>
                        <li><a wire:click="changeStatus('closed')">Closed</a></li>
                    </ul>
                </div>
                
                @if ($editMode)
                    <x-mary-button label="Cancel" class="btn-ghost btn-sm" wire:click="toggleEditMode" />
                    <x-mary-button label="Save" class="btn-primary btn-sm" icon="o-check" wire:click="updateTicket"
                        spinner="updateTicket" />
                @else
                    <x-mary-button label="Edit" class="btn-outline btn-sm" icon="o-pencil" wire:click="toggleEditMode" />
                @endif
            @endcan
            
            @can('delete', $ticket)
                <x-mary-button label="Delete" class="btn-error btn-sm" icon="o-trash" 
                    wire:click="deleteTicket" 
                    wire:confirm="Are you sure you want to delete this ticket? This action cannot be undone."
                    spinner="deleteTicket" />
            @endcan
        </x-slot:actions>
    </x-mary-card>

    {{-- Conversation Timeline --}}
    <x-mary-card title="Conversation Timeline" separator class="mt-6 bg-base-100">
        {{-- Timeline --}}
        <div class="max-h-96 overflow-y-auto mb-6" id="messageThread">
            @forelse ($ticket->messages as $message)
                <div class="flex gap-3 mb-4">
                    {{-- Timeline dot --}}
                    <div class="flex flex-col items-center">
                        <div class="avatar placeholder">
                            <div class="w-8 h-8 {{ $message->user_id === auth()->id() ? 'bg-primary text-primary-content' : 'bg-base-300' }}">
                                <span class="text-xs">{{ substr($message->user->name, 0, 1) }}</span>
                            </div>
                        </div>
                        @if (!$loop->last)
                            <div class="w-0.5 h-full bg-base-300 mt-1"></div>
                        @endif
                    </div>
                    
                    {{-- Message content --}}
                    <div class="flex-1 pb-4">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-semibold text-sm">{{ $message->user->name }}</span>
                            <span class="text-xs opacity-60">{{ $message->created_at->format('d-m-Y H:i') }}</span>
                        </div>
                        <div class="p-3 {{ $message->user_id === auth()->id() ? 'bg-primary/10' : 'bg-base-100' }}">
                            {{ $message->message }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center opacity-50 py-8">
                    No messages yet. Start the conversation!
                </div>
            @endforelse
        </div>

        {{-- Message Input Form --}}
        <div class="space-y-3 pt-4 border-t border-base-300">
            <x-mary-textarea wire:model="messageText" placeholder="Type your message here..." rows="3" />
            <div class="flex justify-end">
                <x-mary-button label="Send Message" icon="o-paper-airplane" class="btn-primary btn-sm" 
                    wire:click="sendMessage" spinner="sendMessage" />
            </div>
        </div>

        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('messageSent', () => {
                    const messageThread = document.getElementById('messageThread');
                    if (messageThread) {
                        messageThread.scrollTop = messageThread.scrollHeight;
                    }
                });
            });
        </script>
    </x-mary-card>
</div>
