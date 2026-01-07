@props(['voucher', 'router'])

{{-- Compact WiFi Card (48 per A4 landscape) --}}
<div class="w-full bg-white border border-black break-inside-avoid relative" style="height: 58px;">
    
    {{-- Header: Router Name & Serial --}}
    <div class="bg-black text-white px-1 py-0.5 flex justify-between items-center">
        <div class="text-[7px] font-bold uppercase tracking-wide truncate max-w-[85px]">
            {{ Str::limit($router->name, 20, '') }}
        </div>
        <span class="text-[6px] font-mono">{{ str_pad($voucher->id, 5, '0', STR_PAD_LEFT) }}</span>
    </div>

    {{-- Voucher Code --}}
    <div class="px-1 py-1 text-center mb-1">
        <div class="text-[6px] text-gray-500 uppercase tracking-wider font-semibold mb-0.5">CODE</div>
        <div class="bg-gray-50 border border-black px-1 py-0.5 inline-block">
            <div class="text-xs font-black font-mono tracking-wider text-black leading-none">
                {{ $voucher->username }}
            </div>
        </div>
    </div>

    {{-- Footer: Login & Validity --}}
    <div class="absolute bottom-0 left-0 right-0 bg-black text-white px-1 py-0.5 flex justify-between items-center text-[6px] font-mono">
        <div class="truncate mr-1">
            @if($router->login_address)
                {{ Str::limit($router->login_address, 18, '') }}
            @else
                {{ Str::limit($voucher->profile->name, 15, '') }}
            @endif
        </div>
        <div class="whitespace-nowrap font-bold">
            {{ $voucher->profile->validity ?? '30d' }}
        </div>
    </div>

</div>
