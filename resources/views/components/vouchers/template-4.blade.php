@props(['voucher', 'router'])

<div
    class="w-full max-w-sm bg-white rounded-xl shadow-sm border border-gray-200 p-0 break-inside-avoid overflow-hidden flex">
    <!-- Left: QR Code -->
    <div class="bg-gray-900 p-4 flex items-center justify-center w-1/3">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ $voucher->username }}&color=ffffff&bgcolor=111827"
            class="w-full h-auto object-contain rounded-md" alt="QR">
    </div>

    <!-- Right: Info -->
    <div class="p-4 w-2/3 flex flex-col justify-center">
        <div class="mb-2">
            <h3 class="font-bold text-gray-800 leading-tight">{{ $router->name }}</h3>
            <span class="text-[10px] text-gray-500 uppercase tracking-wide">Scan or enter code</span>
        </div>

        <div class="my-2">
            <div class="text-2xl font-mono font-bold text-gray-900 tracking-tight">{{ $voucher->username }}</div>
            @if ($voucher->password != $voucher->username)
                <div class="text-sm text-gray-500 font-mono">Pass: {{ $voucher->password }}</div>
            @endif
        </div>

        <div class="mt-auto pt-2 border-t border-gray-100 flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-green-500"></span>
            <span class="text-xs font-semibold text-gray-600">{{ $voucher->profile->name ?? 'Access' }}</span>
        </div>
    </div>
</div>
