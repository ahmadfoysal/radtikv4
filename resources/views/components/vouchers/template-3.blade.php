@props(['voucher', 'router'])

<div
    class="w-full max-w-[300px] bg-base-100 text-base-content p-4 font-mono text-sm break-inside-avoid mx-auto border-x border-dashed border-base-300">
    <div class="text-center pb-4 border-b-2 border-black border-dashed">
        @if ($router->logo_url)
            <img src="{{ $router->logo_url }}" class="h-12 mx-auto grayscale mb-2">
        @else
            <h2 class="font-bold text-xl uppercase">{{ $router->name }}</h2>
        @endif
        <p class="text-xs">WiFi Voucher</p>
        <p class="text-[10px] mt-1">{{ now()->format('d-M-Y H:i') }}</p>
    </div>

    <div class="py-6 text-center">
        <p class="text-xs mb-1">USERNAME / CODE</p>
        <p class="text-2xl font-bold tracking-wider my-2">{{ $voucher->username }}</p>

        @if ($voucher->password != $voucher->username)
            <p class="text-xs mt-3 mb-1">PASSWORD</p>
            <p class="text-xl font-bold">{{ $voucher->password }}</p>
        @endif
    </div>

    <div class="border-t-2 border-black border-dashed pt-4">
        <div class="flex justify-between text-xs mb-1">
            <span>Package:</span>
            <span class="font-bold">{{ $voucher->profile->name ?? '-' }}</span>
        </div>
        <div class="flex justify-between text-xs mb-1">
            <span>Validity:</span>
            <span>{{ $voucher->profile->validity ?? '-' }}</span>
        </div>
        <div class="flex justify-between text-xs">
            <span>Price:</span>
            <span class="font-bold">{{ $voucher->profile->price ?? '0.00' }}</span>
        </div>
    </div>

    <div class="mt-6 text-center text-[10px]">
        *** THANK YOU ***
    </div>
</div>
