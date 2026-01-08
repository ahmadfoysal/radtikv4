@props(['voucher', 'router'])

{{-- Template 3: Receipt/Thermal Printer Style --}}
<div class="bg-white text-black p-4 font-mono text-sm break-inside-avoid mx-auto border-l-2 border-r-2 border-black border-dotted overflow-hidden"
    style="width: 280px; max-width: 280px;">

    {{-- Header --}}
    <div class="text-center pb-3 border-b-2 border-black border-dashed">
        <h2 class="font-bold text-lg uppercase tracking-wide truncate">{{ Str::limit($router->name, 18, '') }}</h2>
        <p class="text-[11px] mt-1 font-bold">━━ WiFi VOUCHER ━━</p>
        <p class="text-[10px] mt-1 text-gray-600">{{ now()->format('d M Y • H:i') }}</p>
        <p class="text-[9px] text-gray-500">ID: #{{ str_pad($voucher->id, 6, '0', STR_PAD_LEFT) }}</p>
    </div>

    {{-- Main Code Section --}}
    <div class="py-4 text-center border-b-2 border-black border-dashed">
        <p class="text-[10px] mb-1 font-bold uppercase tracking-wider">◆ Username / Code ◆</p>
        <div class="bg-gray-100 border-2 border-black my-2 py-2 px-2">
            <p class="text-xl font-bold tracking-wide break-all" style="word-break: break-all;">{{ $voucher->username }}
            </p>
        </div>

        @if ($voucher->password != $voucher->username)
            <p class="text-[10px] mt-3 mb-1 font-bold uppercase tracking-wider">◆ Password ◆</p>
            <p class="text-base font-bold border-2 border-gray-400 border-dashed py-1 px-2 inline-block break-all"
                style="max-width: 95%; word-break: break-all;">{{ $voucher->password }}</p>
        @endif
    </div>

    {{-- Details Section --}}
    <div class="py-3 border-b-2 border-black border-dashed">
        <table class="w-full text-xs">
            <tr class="border-b border-gray-300">
                <td class="py-1 font-bold" style="width: 35%;">Package:</td>
                <td class="py-1 text-right font-bold truncate" style="width: 65%;">
                    {{ Str::limit($voucher->profile->name ?? 'Standard', 15, '') }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="py-1 font-bold">Validity:</td>
                <td class="py-1 text-right font-bold">{{ $voucher->profile->validity ?? 'N/A' }}</td>
            </tr>
            <tr class="border-b border-gray-300">
                <td class="py-1 font-bold">Price:</td>
                <td class="py-1 text-right font-bold">{{ $voucher->profile->price ?? '0.00' }}</td>
            </tr>
            <tr>
                <td class="py-1 font-bold align-top">Login:</td>
                <td class="py-1 text-right text-[9px] break-all" style="word-break: break-all;">
                    {{ Str::limit($router->login_address ?? 'hotspot.local', 25, '') }}</td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <div class="mt-4 text-center">
        <p class="text-[11px] font-bold">═══════════════════</p>
        <p class="text-[10px] mt-1">*** THANK YOU ***</p>
        <p class="text-[9px] text-gray-500 mt-1">Keep this voucher safe</p>
    </div>
</div>
