@if (session('impersonator_id'))
    <div class="bg-warning text-warning-content p-3 mb-4">
        <div class="flex items-center justify-between max-w-7xl mx-auto">
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
                <span class="font-semibold">Impersonating {{ auth()->user()->name }}</span>
            </div>
            <x-mary-button label="Stop Impersonation" class="btn-sm btn-error" wire:click="stopImpersonation"
                icon="o-arrow-left-on-rectangle" />
        </div>
    </div>
@endif
