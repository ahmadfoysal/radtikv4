@props(['voucher', 'router'])

<div class="w-full max-w-sm bg-base-300 text-base-content break-inside-avoid p-6 relative shadow-lg">
    <!-- Background Gradient -->
    <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-secondary blur-3xl opacity-20"></div>
    <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-32 h-32 bg-primary blur-3xl opacity-20"></div>

    <div class="relative z-10 text-center">
        <div class="mb-6">
            @if ($router->logo)
                <img src="{{ asset('storage/' . $router->logo) }}" class="h-10 mx-auto brightness-0 invert">
            @else
                <h2 class="text-2xl font-bold tracking-tight">{{ $router->name }}</h2>
            @endif
            <p class="text-base-content/60 text-xs mt-1 uppercase tracking-widest">Premium Internet Access</p>
        </div>

        <div class="my-6 space-y-4">
            <div>
                <span class="block text-xs text-base-content/60 mb-1">LOGIN CODE</span>
                <span
                    class="block text-3xl font-black font-mono tracking-widest text-primary">
                    {{ $voucher->username }}
                </span>
            </div>

            @if ($voucher->password != $voucher->username)
                <div>
                    <span class="block text-xs text-base-content/60 mb-1">PASSWORD</span>
                    <span class="block text-xl font-bold font-mono text-base-content tracking-widest">
                        {{ $voucher->password }}
                    </span>
                </div>
            @endif
        </div>

        <div class="flex justify-center items-center gap-2 mt-6">
            <span class="px-3 py-1 bg-base-200 border border-base-300 text-xs font-mono text-base-content/80">
                {{ $voucher->profile->name ?? 'Plan' }}
            </span>
        </div>
    </div>
</div>
