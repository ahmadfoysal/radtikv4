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
                                <td>
                                    <div class="flex items-center gap-2">
                                        <x-mary-button icon="o-eye" wire:click="viewDetail({{ $message->id }})"
                                            class="btn-ghost btn-sm text-info" tooltip="View details" />
                                        <x-mary-button icon="o-trash" wire:click="confirmDelete({{ $message->id }})"
                                            class="btn-ghost btn-sm text-error" tooltip="Delete message" />
                                    </div>
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

    {{-- Message Detail Modal --}}
    @if ($showDetailModal && $selectedMessage)
        <x-mary-modal wire:model="showDetailModal" :title="'Message from ' . $selectedMessage->name" subtitle="Full message details" separator
            max-width="2xl">
            <div class="space-y-6">
                {{-- Sender Information --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-base-content/70">Name</label>
                        <p class="text-lg font-medium">{{ $selectedMessage->name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-base-content/70">Email</label>
                        <a href="mailto:{{ $selectedMessage->email }}" class="text-lg link link-primary">
                            {{ $selectedMessage->email }}
                        </a>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-base-content/70">WhatsApp</label>
                        @if ($selectedMessage->whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $selectedMessage->whatsapp) }}"
                                target="_blank" class="text-lg link link-success">
                                {{ $selectedMessage->whatsapp }}
                            </a>
                        @else
                            <p class="text-base-content/50">Not provided</p>
                        @endif
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-base-content/70">Date</label>
                        <p class="text-lg">{{ $selectedMessage->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                </div>

                {{-- Subject --}}
                <div>
                    <label class="text-sm font-semibold text-base-content/70 block mb-2">Subject</label>
                    <div class="bg-base-200 p-4 rounded-lg">
                        <p class="text-lg font-medium">{{ $selectedMessage->subject }}</p>
                    </div>
                </div>

                {{-- Full Message --}}
                <div>
                    <label class="text-sm font-semibold text-base-content/70 block mb-2">Message</label>
                    <div class="bg-base-200 p-4 rounded-lg">
                        <p class="whitespace-pre-wrap text-base leading-relaxed">{{ $selectedMessage->message }}</p>
                    </div>
                </div>

                {{-- Technical Information --}}
                <div class="border-t pt-4">
                    <label class="text-sm font-semibold text-base-content/70 block mb-3">Technical Information</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-base-content/60">IP Address:</span>
                            <p class="font-mono text-base-content/80">{{ $selectedMessage->ip_address }}</p>
                        </div>
                        <div>
                            <span class="text-base-content/60">User Agent:</span>
                            <p class="font-mono text-xs text-base-content/80 break-all">
                                {{ $selectedMessage->user_agent }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Actions --}}
            <x-slot:actions>
                <x-mary-button label="Close" @click="$wire.closeDetailModal()" />
                <x-mary-button label="Delete" class="btn-error" wire:click="delete(); closeDetailModal()" />
            </x-slot:actions>
        </x-mary-modal>
    @endif

    {{-- Delete Confirmation Modal --}}
    <x-mary-modal wire:model="showDeleteModal" title="Delete Message"
        subtitle="Are you sure you want to delete this message?" separator>
        <div class="flex justify-end gap-3 mt-6">
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" />
        </div>
    </x-mary-modal>
</div>
