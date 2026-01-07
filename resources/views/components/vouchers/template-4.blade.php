@props(['voucher', 'router'])

<div
    class="w-full max-w-sm bg-base-100 shadow-sm border border-base-300 p-0 break-inside-avoid overflow-hidden flex">
    <!-- Left: QR Code -->
    <div class="bg-base-300 p-4 flex items-center justify-center w-1/3">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ $voucher->username }}&color=ffffff&bgcolor=111827"
            class="w-full h-auto object-contain" alt="QR">
    </div>

    <!-- Right: Info -->
    <div class="p-4 w-2/3 flex flex-col justify-center">
        <div class="mb-2">
            @if ($router->logo_url)
                <img src="{{ $router->logo_url }}" class="h-6 mb-1 object-contain" alt="{{ $router->name }}">
            @else
                <h3 class="font-bold text-base-content leading-tight">{{ $router->name }}</h3>
            @endif
            <span class="text-[10px] text-base-content/60 uppercase tracking-wide">Scan or enter code</span>
        </div>

        <div class="my-2">
            <div class="text-2xl font-mono font-bold text-base-content tracking-tight">{{ $voucher->username }}</div>
            @if ($voucher->password != $voucher->username)
                <div class="text-sm text-base-content/70 font-mono">Pass: {{ $voucher->password }}</div>
            @endif
        </div>

        <div class="mt-auto pt-2 border-t border-base-300 flex items-center gap-2">
            <span class="h-2 w-2 bg-success"></span>
            <span class="text-xs font-semibold text-base-content/80">{{ $voucher->profile->name ?? 'Access' }}</span>
        </div>
    </div>
</div>
