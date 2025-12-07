<!DOCTYPE html>
<html lang="en"> <!-- removed data-theme="light" -->

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- THEME INIT: must run BEFORE CSS to avoid flash + persist choice -->
    <script>
        (function() {
            const KEY = 'radtik-theme';
            const saved = localStorage.getItem(KEY);
            // Default to DARK when nothing saved
            const initial = saved || 'dark';
            document.documentElement.setAttribute('data-theme', initial);
            // If you use Tailwind dark variant anywhere, keep .dark in sync
            document.documentElement.classList.toggle('dark', initial === 'dark');

            // Global toggler (usable from any button onclick)
            window.__toggleTheme = function(next) {
                const current = document.documentElement.getAttribute('data-theme') || 'dark';
                const target = next ?? (current === 'dark' ? 'light' : 'dark');
                document.documentElement.setAttribute('data-theme', target);
                document.documentElement.classList.toggle('dark', target === 'dark');
                localStorage.setItem(KEY, target);
                // Optional: live-update the icon if present
                const i = document.getElementById('radtik-theme-icon');
                if (i) i.setAttribute('name', target === 'dark' ? 'o-sun' : 'o-moon');
                return target;
            };
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-sans antialiased">
    {{-- NAVBAR --}}
    <x-mary-nav sticky full-width>
        <x-slot:brand>
            <label for="main-drawer" class="lg:hidden mr-3">
                <x-mary-icon name="o-bars-3" class="cursor-pointer" />
            </label>
            <div>App</div>
        </x-slot:brand>

        <x-slot:actions>
            {{-- Theme toggle --}}
            <button type="button" class="btn btn-ghost btn-sm" title="Toggle theme" onclick="window.__toggleTheme?.()">
                <x-mary-icon id="radtik-theme-icon" name="o-moon" class="w-5 h-5" />
                <span class="ml-1 hidden sm:inline">Theme</span>
            </button>

            <x-mary-button label="Messages" icon="o-envelope" link="#" class="btn-ghost btn-sm" responsive />
            <x-mary-button label="Notifications" icon="o-bell" link="#" class="btn-ghost btn-sm" responsive />
        </x-slot:actions>
    </x-mary-nav>

    {{-- MAIN --}}
    <x-mary-main with-nav full-width>
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-200">
            @if ($user = auth()->user())
                <x-mary-list-item :item="$user" value="name" sub-value="email" no-separator no-hover
                    class="pt-2">
                    <x-slot:actions>
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

            <x-sidebar-menu />
        </x-slot:sidebar>

        <x-slot:content>
            <div class="px-0 py-4 sm:px-4 lg:px-6">
                {{ $slot }}
            </div>
        </x-slot:content>
    </x-mary-main>

    <x-mary-toast />

    @livewireScripts
    <script>
        // Set initial icon to match current theme once DOM exists
        document.addEventListener('DOMContentLoaded', function() {
            const cur = document.documentElement.getAttribute('data-theme') || 'dark';
            const i = document.getElementById('radtik-theme-icon');
            if (i) i.setAttribute('name', cur === 'dark' ? 'o-sun' : 'o-moon');
        });
    </script>
</body>

</html>
