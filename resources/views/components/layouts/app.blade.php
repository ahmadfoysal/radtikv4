<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" /> {{-- mobile --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- Theme helper (no Alpine needed) --}}
    <script>
        (function() {
            const key = 'radtik-theme';
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const saved = localStorage.getItem(key);
            const theme = saved || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            // optional: also toggle .dark class if you use it anywhere
            document.documentElement.classList.toggle('dark', theme === 'dark');
            window.__toggleTheme = function() {
                const current = document.documentElement.getAttribute('data-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                document.documentElement.classList.toggle('dark', next === 'dark');
                localStorage.setItem(key, next);
                return next;
            };
        })();
    </script>
</head>

<body class="font-sans antialiased">

    {{-- NAVBAR (sticky + full-width) --}}
    <x-mary-nav sticky full-width>
        <x-slot:brand>
            {{-- Drawer toggle for "main-drawer" --}}
            <label for="main-drawer" class="lg:hidden mr-3">
                <x-mary-icon name="o-bars-3" class="cursor-pointer" />
            </label>

            {{-- Brand --}}
            <div>App</div>
        </x-slot:brand>

        {{-- Right side actions --}}
        <x-slot:actions>
            {{-- Theme toggle --}}
            <button type="button" class="btn btn-ghost btn-sm" title="Toggle theme"
                onclick="(function(){ const n = window.__toggleTheme?.(); const t = document.getElementById('radtik-theme-icon'); if(!t) return; t.setAttribute('name', n==='dark' ? 'o-sun' : 'o-moon'); })()">
                <x-mary-icon id="radtik-theme-icon" name="o-moon" class="w-5 h-5" />
                <span class="ml-1 hidden sm:inline">Theme</span>
            </button>

            <x-mary-button label="Messages" icon="o-envelope" link="#" class="btn-ghost btn-sm" responsive />
            <x-mary-button label="Notifications" icon="o-bell" link="#" class="btn-ghost btn-sm" responsive />
        </x-slot:actions>
    </x-mary-nav>

    {{-- MAIN (with-nav + full-width) --}}
    <x-mary-main with-nav full-width>
        {{-- SIDEBAR (also works as drawer on small screens) --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-200">

            {{-- User --}}
            @if ($user = auth()->user())
                <x-mary-list-item :item="$user" value="name" sub-value="email" no-separator no-hover
                    class="pt-2">
                    <x-slot:actions>
                        {{-- Logout should be POST in Laravel --}}
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-circle btn-ghost btn-xs" title="Log out">
                                <x-mary-icon name="o-power" />
                            </button>
                        </form>
                    </x-slot:actions>
                </x-mary-list-item>

                <x-mary-menu-separator />
            @endif

            {{-- Menu (activates by current route) --}}
            <x-sidebar-menu />
        </x-slot:sidebar>

        {{-- CONTENT --}}
        <x-slot:content>
            <div class="p-4 sm:p-5 lg:p-6">
                {{ $slot }}
            </div>
        </x-slot:content>
    </x-mary-main>

    {{-- TOAST --}}
    <x-mary-toast />

    @livewireScripts
    <script>
        // set initial icon according to current theme (after Livewire loads too)
        (function() {
            const el = document.getElementById('radtik-theme-icon');
            if (!el) return;
            const cur = document.documentElement.getAttribute('data-theme') || 'light';
            el.setAttribute('name', cur === 'dark' ? 'o-sun' : 'o-moon');
        })();
    </script>
</body>

</html>
