@props(['voucher', 'router'])

@php
    // Build login URL with username and password parameters
    $loginUrl = rtrim($router->login_address ?? 'http://hotspot.local', '/');
    $qrData =
        $loginUrl . '/login?username=' . urlencode($voucher->username) . '&password=' . urlencode($voucher->username);
@endphp

{{-- Template 2: Horizontal Card with QR Code - 5 per row --}}
<div class="bg-white border-2 border-black break-inside-avoid overflow-hidden flex" style="width: 220px; height: 90px;">

    {{-- Left Section: QR Code --}}
    <div class="border-r-2 border-black flex items-center justify-center bg-white" style="width: 88px; padding: 4px;">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode($qrData) }}&color=000000&bgcolor=ffffff"
            style="width: 80px; height: 80px; display: block;" alt="QR">
    </div>

    {{-- Right Section: Info --}}
    <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">

        {{-- Header: Router Name + ID --}}
        <div class="border-b border-black flex items-center justify-between" style="padding: 3px 6px; height: 20px;">
            <div class="text-[9px] font-bold text-black truncate" style="max-width: 85px;">
                {{ $router->name }}
            </div>
            <div class="text-[9px] text-black font-bold">[{{ $voucher->id }}]</div>
        </div>

        {{-- Voucher Code - Center --}}
        <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 0 6px;">
            <div class="text-[20px] font-black font-mono text-black leading-none" style="letter-spacing: -0.5px;">
                {{ $voucher->username }}
            </div>
        </div>

        {{-- Footer: Validity + Plan --}}
        <div class="border-t border-black" style="padding: 3px 6px;">
            <div class="text-[8px] font-bold text-black leading-tight" style="margin-bottom: 2px;">
                Validity: {{ $voucher->profile->validity ?? '1d' }}
            </div>
            <div class="text-[7px] font-semibold text-black truncate">
                Plan: {{ Str::limit($voucher->profile->name ?? '1 Day', 14, '') }}
            </div>
        </div>
    </div>

</div>
