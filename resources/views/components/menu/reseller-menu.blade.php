<div>
    {{-- resources/views/components/sidebar-menu.blade.php --}}
    <x-mary-menu activate-by-route>

        {{-- DASHBOARD --}}
        <x-mary-menu-item title="Dashboard" icon="o-home" link="/dashboard" />

        {{-- ROUTER MANAGEMENT --}}
        @if (auth()->user()->can('view_router') || auth()->user()->can('add_router'))
            <x-mary-menu-sub title="Routers" icon="o-server-stack">
                @can('view_router')
                    <x-mary-menu-item title="All Routers" icon="o-list-bullet" link="/routers" />
                @endcan
                @can('add_router')
                    <x-mary-menu-item title="Add New Router" icon="o-plus-circle" link="/router/add" />
                @endcan
                {{-- <x-mary-menu-item title="Import Routers" icon="o-plus-circle" link="/router/import" /> --}}
            </x-mary-menu-sub>
        @endif

        {{-- VOUCHERS --}}
        @if (auth()->user()->can('view_vouchers') ||
                auth()->user()->can('generate_vouchers') ||
                auth()->user()->can('print_vouchers') ||
                auth()->user()->can('bulk_delete_vouchers'))
            <x-mary-menu-sub title="Vouchers" icon="o-ticket">
                @can('view_vouchers')
                    <x-mary-menu-item title="Voucher List" icon="o-rectangle-stack" link="/vouchers" />
                @endcan
                @can('generate_vouchers')
                    <x-mary-menu-item title="Generate Voucher" icon="o-plus" link="/vouchers/generate" />
                @endcan
                @can('view_vouchers')
                    <x-mary-menu-item title="Voucher Logs" icon="o-clipboard-document-list" link="/vouchers/logs" />
                @endcan
                @can('print_vouchers')
                    <x-mary-menu-item title="Print Vouchers" icon="o-printer" link="/vouchers/bulk-manager" />
                @endcan
                @can('bulk_delete_vouchers')
                    <x-mary-menu-item title="Bulk Delete" icon="o-trash" link="/vouchers/bulk-manager" />
                @endcan
            </x-mary-menu-sub>
        @endif

        {{-- HOTSPOT USERS --}}
        @if (auth()->user()->can('view_hotspot_users') ||
                auth()->user()->can('create_single_user') ||
                auth()->user()->can('view_active_sessions') ||
                auth()->user()->can('view_session_cookies') ||
                auth()->user()->can('view_hotspot_logs'))
            <x-mary-menu-sub title="Hotspot Users" icon="o-user-group">
                @can('create_single_user')
                    <x-mary-menu-item title="Create Single User" icon="o-user-plus" link="/hotspot/users/create" />
                @endcan
                @can('view_active_sessions')
                    <x-mary-menu-item title="Active Sessions" icon="o-signal" link="/hotspot/sessions" />
                @endcan
                @can('view_session_cookies')
                    <x-mary-menu-item title="Session Cookies" icon="o-user-minus" link="/hotspot/session-cookies" />
                @endcan
                @can('view_hotspot_logs')
                    <x-mary-menu-item title="Hotspot Logs" icon="o-squares-plus" link="/hotspot/logs" />
                @endcan
            </x-mary-menu-sub>
        @endif

        {{-- BILLING & REPORTS --}}
        @if (auth()->user()->can('view_voucher_logs'))
            <x-mary-menu-sub title="Billing & Reports" icon="o-document-chart-bar">
                <x-mary-menu-item title="Sales Summary" icon="o-chart-bar" link="/billing/sales-summary" />
                <x-mary-menu-item title="Voucher Logs" icon="o-clipboard-document-list" link="/vouchers/logs" />
            </x-mary-menu-sub>
        @endif

        {{-- ADMIN SETTINGS --}}
        <x-mary-menu-sub title="Profile & Password" icon="o-cog-6-tooth">
            <x-mary-menu-item title="Profile & Security" icon="o-shield-check" link="/settings/profile" wire:navigate />
        </x-mary-menu-sub>

        {{-- SUPPORT --}}
        <x-mary-menu-sub title="Help & Support" icon="o-lifebuoy">
            <x-mary-menu-item title="Documentation" icon="o-book-open" link="/docs" />
            <x-mary-menu-item title="Knowledge Base" icon="o-light-bulb" link="/knowledgebase" />
            <x-mary-menu-item title="Contact Support" icon="o-chat-bubble-left-right" link="/support/contact" />
        </x-mary-menu-sub>

    </x-mary-menu>

</div>
