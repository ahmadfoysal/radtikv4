@props(['voucher', 'router'])

{{-- Template 5: Vintage Ticket Style --}}
<div class="bg-white border-4 border-black border-double break-inside-avoid overflow-hidden"
    style="width: 320px; padding: 4px;">
    <div class="border-2 border-black bg-white relative overflow-hidden" style="padding: 16px; min-height: 380px;">

        {{-- Perforated Edge Effect --}}
        <div class="absolute bg-white border-2 border-black rounded-full"
            style="top: 50%; left: -12px; width: 24px; height: 24px; transform: translateY(-50%);"></div>
        <div class="absolute bg-white border-2 border-black rounded-full"
            style="top: 50%; right: -12px; width: 24px; height: 24px; transform: translateY(-50%);"></div>

        {{-- Header --}}
        <div class="text-center border-b-2 border-black" style="padding-bottom: 10px; margin-bottom: 12px;">
            <div class="inline-block bg-black text-white" style="padding: 4px 12px;">
                <h2 class="font-serif font-bold text-white uppercase tracking-wide truncate"
                    style="font-size: 16px; max-width: 240px;">
                    {{ Str::limit($router->name, 18, '') }}
                </h2>
            </div>
            <p class="font-serif italic text-gray-700 text-xs" style="margin-top: 8px;">═══ Internet Access Pass ═══</p>
            <p class="text-[9px] text-gray-500 font-mono" style="margin-top: 4px;">Voucher
                #{{ str_pad($voucher->id, 6, '0', STR_PAD_LEFT) }}</p>
        </div>

        {{-- Main Content --}}
        <div style="margin-bottom: 12px;">
            {{-- Access Code --}}
            <div class="text-center" style="margin-bottom: 10px;">
                <span class="block text-[10px] font-bold text-gray-600 uppercase tracking-widest"
                    style="margin-bottom: 4px;">◇ Access Code ◇</span>
                <div class="border-4 border-black bg-white inline-block" style="padding: 6px 10px; max-width: 260px;">
                    <span class="block font-mono font-bold text-black tracking-wide break-all"
                        style="font-size: 20px; word-break: break-all;">
                        {{ $voucher->username }}
                    </span>
                </div>
            </div>

            @if ($voucher->password != $voucher->username)
                <div class="text-center" style="margin-top: 10px;">
                    <span class="block text-[10px] font-bold text-gray-600 uppercase tracking-widest"
                        style="margin-bottom: 4px;">◇ Password ◇</span>
                    <span
                        class="block font-mono font-bold text-black border-2 border-gray-400 border-dashed inline-block break-all"
                        style="font-size: 16px; padding: 4px 8px; max-width: 260px; word-break: break-all;">
                        {{ $voucher->password }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Details Grid --}}
        <div class="border-t-2 border-b-2 border-black"
            style="padding-top: 10px; padding-bottom: 10px; margin-top: 10px; margin-bottom: 10px;">
            <table class="w-full text-xs font-serif">
                <tr class="border-b border-gray-300">
                    <td class="font-bold text-gray-700 uppercase text-[10px]"
                        style="padding-top: 4px; padding-bottom: 4px; width: 35%;">Package:</td>
                    <td class="text-right font-bold text-black truncate"
                        style="padding-top: 4px; padding-bottom: 4px; width: 65%;">
                        {{ Str::limit($voucher->profile->name ?? 'Standard', 14, '') }}</td>
                </tr>
                <tr class="border-b border-gray-300">
                    <td class="font-bold text-gray-700 uppercase text-[10px]"
                        style="padding-top: 4px; padding-bottom: 4px;">Valid For:</td>
                    <td class="text-right font-bold text-black" style="padding-top: 4px; padding-bottom: 4px;">
                        {{ $voucher->profile->validity ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="font-bold text-gray-700 uppercase text-[10px]"
                        style="padding-top: 4px; padding-bottom: 4px;">Price:</td>
                    <td class="text-right font-bold text-black" style="padding-top: 4px; padding-bottom: 4px;">
                        {{ $voucher->profile->price ?? 'Free' }}</td>
                </tr>
            </table>
        </div>

        {{-- Footer --}}
        <div class="text-center" style="margin-top: 12px;">
            <div class="text-[10px] font-bold text-gray-600 uppercase tracking-wide" style="margin-bottom: 4px;">Login
                Portal</div>
            <div class="font-mono font-semibold text-black bg-gray-100 border border-gray-400 inline-block truncate"
                style="font-size: 10px; padding: 4px 8px; max-width: 260px;">
                {{ Str::limit($router->login_address ?? 'hotspot.local', 30, '') }}
            </div>
            <p class="text-[9px] text-gray-500 font-serif italic" style="margin-top: 8px;">Keep this ticket for your
                records</p>
        </div>
    </div>
</div>
