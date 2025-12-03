@props(['voucher', 'router'])

<div class="w-full max-w-sm bg-amber-50 border-2 border-amber-800 border-dashed rounded-lg p-1 break-inside-avoid">
    <div class="border border-amber-800/30 rounded p-4 h-full relative">
        <!-- Ticket Cutout Circles -->
        <div class="absolute top-1/2 -left-2 w-4 h-4 bg-white rounded-full border-r border-amber-800"></div>
        <div class="absolute top-1/2 -right-2 w-4 h-4 bg-white rounded-full border-l border-amber-800"></div>

        <div class="text-center border-b border-amber-800/20 pb-3 mb-3">
            <h2 class="font-serif font-bold text-amber-900 text-xl tracking-wide uppercase">{{ $router->name }}</h2>
            <p class="font-serif italic text-amber-700/70 text-xs">Admit One Device</p>
        </div>

        <div class="flex justify-between items-center gap-4">
            <div class="text-center flex-1">
                <span class="block text-[10px] font-bold text-amber-800/50 uppercase mb-1">User Code</span>
                <span
                    class="block font-mono text-2xl font-bold text-amber-900 border-2 border-amber-900/10 bg-white/50 rounded py-1">
                    {{ $voucher->username }}
                </span>
            </div>

            @if ($voucher->password != $voucher->username)
                <div class="text-center flex-1">
                    <span class="block text-[10px] font-bold text-amber-800/50 uppercase mb-1">Password</span>
                    <span class="block font-mono text-xl font-bold text-amber-900 py-1">
                        {{ $voucher->password }}
                    </span>
                </div>
            @endif
        </div>

        <div class="mt-4 flex justify-center gap-4 text-xs font-serif text-amber-800">
            <div class="flex items-center gap-1">
                <x-mary-icon name="o-star" class="w-3 h-3" />
                <span>{{ $voucher->profile->name ?? 'Standard' }}</span>
            </div>
            <div class="flex items-center gap-1">
                <x-mary-icon name="o-clock" class="w-3 h-3" />
                <span>{{ $voucher->profile->validity ?? 'Unknown' }}</span>
            </div>
        </div>
    </div>
</div>
