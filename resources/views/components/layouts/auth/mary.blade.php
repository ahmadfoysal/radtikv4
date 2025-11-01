{{-- resources/views/layouts/auth/mary.blade.php --}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ session('theme', 'light') }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title ?? config('app.name') }}</title>

    {{-- Vite assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire 3 --}}
    @livewireStyles

    {{-- Favicon / meta (optional) --}}
    @stack('head')
</head>

<body class="min-h-screen bg-base-200 text-base-content antialiased">

    <div class="min-h-screen grid place-items-center px-4 py-8">
        <div class="w-full max-w-md">
            {{-- Brand / Logo --}}
            {{-- <a href="{{ route('home') }}" class="mb-6 flex items-center justify-center gap-2" wire:navigate>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-base-100 shadow-sm">
                    <x-app-logo-icon class="size-7 text-base-content" />
                </span>
                <span class="sr-only">{{ config('app.name') }}</span>
            </a> --}}

            {{-- Auth Card --}}
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body gap-6">
                    @isset($header)
                        <div class="text-center">
                            <h1 class="text-xl font-semibold leading-tight">
                                {{ $header }}
                            </h1>
                            @isset($subheader)
                                <p class="mt-1 text-sm opacity-70">{{ $subheader }}</p>
                            @endisset
                        </div>
                    @endisset

                    {{-- Your form / content goes here --}}
                    {{ $slot }}

                    {{-- Actions slot (optional) --}}
                    @isset($actions)
                        <div class="mt-2">
                            {{ $actions }}
                        </div>
                    @endisset
                </div>
            </div>

            {{-- Secondary links (optional) --}}
            <div class="mt-6 flex items-center justify-between text-sm opacity-80">
                <div>
                    @isset($secondary)
                        {{ $secondary }}
                    @else
                        {{-- Example placeholder:
                        <a href="{{ route('password.request') }}" class="link link-hover">Forgot password?</a>
                        --}}
                    @endisset
                </div>
                <div class="flex items-center gap-2">
                    {{-- Theme toggle example (optional) --}}
                    <button id="theme-toggle" class="btn btn-xs">
                        Toggle theme
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Livewire --}}
    @livewireScripts

    {{-- Simple theme toggle (optional) --}}
    <script>
        const btn = document.getElementById('theme-toggle');
        if (btn) {
            btn.addEventListener('click', () => {
                const html = document.documentElement;
                const next = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
                html.setAttribute('data-theme', next);
                try {
                    localStorage.setItem('theme', next);
                } catch (e) {}
            });
            // hydrate from localStorage
            try {
                const saved = localStorage.getItem('theme');
                if (saved) document.documentElement.setAttribute('data-theme', saved);
            } catch (e) {}
        }
    </script>

    @stack('scripts')
</body>

</html>
