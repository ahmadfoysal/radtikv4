@props(['voucher', 'router'])

{{-- Template 4: Business Card Style with QR --}}
<div class="bg-white border-2 border-black break-inside-avoid overflow-hidden flex shadow-sm"
    style="width: 340px; height: 180px; max-width: 340px;">

    {{-- Left: QR Code Section --}}
    <div class="bg-black flex items-center justify-center border-r-2 border-black" style="width: 120px; padding: 8px;">
        <div class="bg-white" style="padding: 4px; width: 100%;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($voucher->username) }}&color=000000&bgcolor=ffffff"
                style="width: 100%; height: auto; display: block;" alt="QR Code">
        </div>
    </div>

    {{-- Right: Information Section --}}
    <div class="bg-white flex flex-col justify-between overflow-hidden" style="width: 220px; padding: 12px;">
        {{-- Header --}}
        <div class="border-b-2 border-gray-300" style="padding-bottom: 6px; margin-bottom: 8px;">
            <h3 class="font-bold text-black text-sm leading-tight uppercase tracking-wide truncate">
                {{ Str::limit($router->name, 15, '') }}
            </h3>
            <span class="text-[9px] text-gray-600 uppercase tracking-wide font-bold">WiFi Access Card</span>
        </div>

        {{-- Voucher Details --}}
        <div class="flex-1" style="margin-top: 4px; margin-bottom: 4px;">
            <div style="margin-bottom: 8px;">
                <div class="text-[9px] text-gray-600 font-bold uppercase tracking-wide" style="margin-bottom: 2px;">
                    Login Code</div>
                <div class="font-mono font-bold text-black bg-gray-100 border-2 border-black overflow-hidden"
                    style="font-size: 16px; padding: 4px 6px; word-break: break-all;">
                    {{ Str::limit($voucher->username, 14, '') }}
                </div>
            </div>

            @if ($voucher->password != $voucher->username)
                <div>
                    <div class="text-[9px] text-gray-600 font-bold uppercase tracking-wide" style="margin-bottom: 2px;">
                        Password</div>
                    <div class="text-xs text-black font-mono font-semibold border border-gray-400 border-dashed overflow-hidden"
                        style="padding: 2px 6px; word-break: break-all;">
                        {{ Str::limit($voucher->password, 14, '') }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Footer Info --}}
        <div class="border-t border-gray-300" style="padding-top: 6px; margin-top: auto;">
            <div class="flex justify-between items-center text-[10px]" style="margin-bottom: 3px;">
                <span class="text-gray-600 font-bold">Package:</span>
                <span class="font-bold text-black truncate"
                    style="max-width: 100px;">{{ Str::limit($voucher->profile->name ?? 'Standard', 10, '') }}</span>
            </div>
            <div class="flex justify-between items-center text-[10px]" style="margin-bottom: 3px;">
                <span class="text-gray-600 font-bold">Validity:</span>
                <span class="font-bold text-black">{{ $voucher->profile->validity ?? 'N/A' }}</span>
            </div>
            <div class="text-[9px] text-gray-500 font-mono truncate" style="margin-top: 2px;">
                {{ Str::limit($router->login_address ?? 'hotspot.local', 28, '') }}
            </div>
        </div>
    </div>
</div>
