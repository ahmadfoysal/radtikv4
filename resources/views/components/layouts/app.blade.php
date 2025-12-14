<!DOCTYPE html>
<html lang="en"> <!-- removed data-theme="light" -->

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- THEME INIT: must run BEFORE CSS to avoid flash + persist choice -->
    @php
        $defaultTheme = config('theme.default_theme', 'dark');
        // Normalize to light or dark for user toggle (admin can set other themes via config)
        $userTheme = in_array($defaultTheme, ['light', 'dark']) ? $defaultTheme : 'dark';
    @endphp
    <script>
        (function() {
            const KEY = 'radtik-theme';
            const saved = localStorage.getItem(KEY);
            // Get default theme - normalize to light/dark for user toggle
            const defaultTheme = @json($userTheme);
            const initial = saved || defaultTheme;
            
            // Ensure we only use 'light' or 'dark' for user toggle
            const normalizedTheme = (initial === 'light' || initial === 'dark') ? initial : 'dark';
            
            // Apply theme immediately
            const html = document.documentElement;
            html.setAttribute('data-theme', normalizedTheme);
            
            // Update dark class for Tailwind dark mode
            if (normalizedTheme === 'dark') {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }

            // Simple light/dark toggle function
            window.__toggleTheme = function() {
                const current = html.getAttribute('data-theme') || 'dark';
                const target = current === 'dark' ? 'light' : 'dark';
                
                // Apply theme
                html.setAttribute('data-theme', target);
                
                // Update dark class
                if (target === 'dark') {
                    html.classList.add('dark');
                } else {
                    html.classList.remove('dark');
                }
                
                // Save to localStorage
                try {
                    localStorage.setItem(KEY, target);
                } catch (e) {
                    console.warn('Failed to save theme:', e);
                }
                
                // Dispatch event for any listeners (icon will be updated by event listener)
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: target } }));
                
                // Update icon to show current theme (not target)
                const icon = document.getElementById('radtik-theme-icon');
                if (icon) {
                    icon.setAttribute('name', target === 'dark' ? 'o-moon' : 'o-sun');
                }
                
                return target;
            };
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-sans antialiased bg-base-200 min-h-screen">
    {{-- NAVBAR --}}
    <x-mary-nav sticky full-width>
        <x-slot:brand>
            <label for="main-drawer" class="lg:hidden mr-3">
                <x-mary-icon name="o-bars-3" class="cursor-pointer" />
            </label>
            <div>App</div>
        </x-slot:brand>

        <x-slot:actions>
            {{-- User Balance --}}
            @auth
                <div class="flex items-center gap-2 px-3 py-2 bg-base-100 border border-base-300">
                    <x-mary-icon name="o-banknotes" class="w-5 h-5 text-primary" />
                    <span class="font-semibold text-sm sm:text-base">BDT {{ number_format(auth()->user()->balance, 2) }}</span>
                </div>
            @endauth

            {{-- Theme toggle (Light/Dark only) --}}
            <button type="button" class="btn btn-ghost btn-sm" title="Toggle theme" id="theme-toggle-btn">
                <x-mary-icon id="radtik-theme-icon" name="o-sun" class="w-5 h-5" />
                <span class="ml-1 hidden sm:inline">Theme</span>
            </button>

            <x-mary-button label="Messages" icon="o-envelope" link="#" class="btn-ghost btn-sm" responsive />
            <x-mary-button label="Notifications" icon="o-bell" link="#" class="btn-ghost btn-sm" responsive />
        </x-slot:actions>
    </x-mary-nav>

    {{-- MAIN --}}
    <x-mary-main with-nav full-width>
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 border-r border-base-300 flex flex-col">
            <div class="flex-1">
                @auth
                    @if (auth()->user()->isSuperAdmin())
                        <x-menu.superadmin-menu />
                    @elseif (auth()->user()->isAdmin())
                        <x-menu.admin-menu />
                    @elseif (auth()->user()->isReseller())
                        <x-menu.reseller-menu />
                    @endif
                @endauth
            </div>

            {{-- Logout button at bottom --}}
            @auth
                <div class="mt-auto pt-4 border-t border-base-300">
                    <form method="POST" action="{{ route('tyro-login.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-block justify-start gap-2">
                            <x-mary-icon name="o-power" class="w-5 h-5" />
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            @endauth
        </x-slot:sidebar>

        <x-slot:content>
            <div class="px-0 py-4 sm:px-4 lg:px-6 bg-base-200 min-h-screen">
                {{ $slot }}
            </div>
        </x-slot:content>
    </x-mary-main>

    <x-mary-toast />

    @livewireScripts
    <script>
        // Function to update theme icon based on current theme
        function updateThemeIcon() {
            const cur = document.documentElement.getAttribute('data-theme') || 'dark';
            const i = document.getElementById('radtik-theme-icon');
            if (i) {
                // Show sun icon in light mode, moon icon in dark mode
                i.setAttribute('name', cur === 'dark' ? 'o-moon' : 'o-sun');
            }
        }

        // Set initial icon and attach toggle button once DOM exists
        document.addEventListener('DOMContentLoaded', function() {
            updateThemeIcon();
            
            // Attach click handler to toggle button
            const toggleBtn = document.getElementById('theme-toggle-btn');
            if (toggleBtn && window.__toggleTheme) {
                toggleBtn.addEventListener('click', function() {
                    window.__toggleTheme();
                });
            }
        });

        // Update icon on Livewire navigation (for SPA-like navigation)
        document.addEventListener('livewire:navigated', function() {
            updateThemeIcon();
        });

        // Also listen for any theme changes
        window.addEventListener('theme-changed', function() {
            updateThemeIcon();
        });

        // Update icon immediately if DOM is already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', updateThemeIcon);
        } else {
            updateThemeIcon();
        }
    </script>
</body>

</html>
