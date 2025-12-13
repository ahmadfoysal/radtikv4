@props(['voucher', 'router'])

{{-- Compact Grid Layout (Target: 45-50 cards per A4 page) --}}
<div
    class="w-full h-[65px] bg-white border border-black p-1 break-inside-avoid flex flex-col justify-between relative overflow-hidden">

    {{-- Header: Router Name --}}
    <div class="flex justify-between items-center border-b border-black pb-0.5">
        <span class="text-[9px] font-bold truncate w-20 leading-none uppercase">{{ $router->name }}</span>
        <span class="text-[8px] font-mono bg-black text-white px-1 leading-none">
            {{ $loop->iteration ?? '#' }}
        </span>
    </div>

    {{-- Main Code --}}
    <div class="text-center my-0.5">
        <p class="text-sm font-black font-mono tracking-wider leading-none text-black">
            {{ $voucher->username }}
        </p>
    </div>

    {{-- Footer: Validity & Price/Profile --}}
    <div class="flex justify-between items-center border-t border-black pt-0.5 text-[8px] font-bold leading-none">
        <span>{{ $voucher->profile->validity ?? '30d' }}</span>
        <span class="truncate max-w-[50px] text-right">{{ $voucher->profile->name }}</span>
    </div>

</div>
