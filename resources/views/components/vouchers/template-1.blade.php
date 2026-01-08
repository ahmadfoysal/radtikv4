@props(['voucher', 'router'])

{{-- Template 1: Compact Grid Style - 48 cards per A4 landscape (6x8) --}}
<div class="bg-white border border-black break-inside-avoid overflow-hidden" style="width: 165px; height: 90px;">

    {{-- Header: Router Name + ID --}}
    <div class="border-b border-black flex items-center justify-between" style="padding: 3px 6px; height: 20px;">
        <div class="text-[10px] font-bold text-black truncate" style="max-width: 120px;">
            {{ $router->name }}
        </div>
        <div class="text-[10px] text-black font-bold">[{{ $voucher->id }}]</div>
    </div>

    {{-- Voucher Code in Rectangle Box + Validity --}}
    <div class="flex flex-col items-center justify-center" style="height: 50px; padding: 6px;">
        <div class="border-2 border-black bg-white" style="padding: 3px 6px;">
            <div class="text-[15px] font-bold font-mono text-black leading-none" style="letter-spacing: -0.3px;">
                {{ $voucher->username }}
            </div>
        </div>
        <div class="text-[9px] font-bold text-black mt-1">
            {{ $voucher->profile->validity ?? '30d' }}
        </div>
    </div>

    {{-- Footer: Login Address --}}
    <div class="border-t border-black text-center"
        style="padding: 3px 6px; height: 20px; display: flex; align-items: center; justify-content: center;">
        <div class="text-[9px] font-bold text-black leading-none truncate" style="max-width: 100%;">
            {{ $router->login_address ?? 'hotspot.local' }}
        </div>
    </div>

</div>
