<div>
    {{-- Header --}}
    <x-mary-header title="Contact Messages" subtitle="View and manage contact form submissions" separator>
        <x-slot:actions>
            <x-mary-input icon="o-magnifying-glass" placeholder="Search messages..."
                wire:model.live.debounce.300ms="search" clearable />
        </x-slot:actions>
    </x-mary-header>

    {{-- Messages Table --}}
    <x-mary-card>
        @if ($messages->isEmpty())
            <div class="text-center py-12">
                <x-mary-icon name="o-envelope" class="w-16 h-16 mx-auto text-base-content/30 mb-4" />
                <p class="text-base-content/70">No contact messages yet</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>WhatsApp</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>IP Address</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($messages as $message)
                            <tr>
                                <td class="text-sm">
                                    <div>{{ $message->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-base-content/60">
                                        {{ $message->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="font-medium">{{ $message->name }}</td>
                                <td>
                                    <a href="mailto:{{ $message->email }}" class="link link-primary">
                                        {{ $message->email }}
                                    </a>
                                </td>
                                <td>
                                    @if ($message->whatsapp)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $message->whatsapp) }}"
                                            target="_blank" class="link link-success">
                                            {{ $message->whatsapp }}
                                        </a>
                                    @else
                                        <span class="text-base-content/40">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="max-w-xs truncate" title="{{ $message->subject }}">
                                        {{ $message->subject }}
                                    </div>
                                </td>
                                <td>
                                    <div class="max-w-md truncate text-sm text-base-content/70"
                                        title="{{ $message->message }}">
                                        {{ $message->message }}
                                    </div>
                                </td>
                                <td class="text-sm text-base-content/60">{{ $message->ip_address }}</td>
                                <td class="text-right">
                                    <x-mary-button icon="o-trash" wire:click="confirmDelete({{ $message->id }})"
                                        class="btn-ghost btn-sm text-error" tooltip="Delete message" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $messages->links() }}
            </div>
        @endif
    </x-mary-card>

    {{-- Delete Confirmation Modal --}}
    <x-mary-modal wire:model="showDeleteModal" title="Delete Message"
        subtitle="Are you sure you want to delete this message?" separator>
        <div class="flex justify-end gap-3 mt-6">
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" />
        </div>
    </x-mary-modal>
</div>
