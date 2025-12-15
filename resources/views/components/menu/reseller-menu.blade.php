<div>
    {{-- resources/views/components/sidebar-menu.blade.php --}}
    <x-mary-menu activate-by-route>

        {{-- DASHBOARD --}}
        <x-mary-menu-item title="Dashboard" icon="o-home" link="/dashboard" />

        {{-- ROUTER MANAGEMENT --}}
        <x-mary-menu-sub title="Routers" icon="o-server-stack">
            <x-mary-menu-item title="All Routers" icon="o-list-bullet" link="/routers" />
            <x-mary-menu-item title="Add New Router" icon="o-plus-circle" link="/router/add" />
            {{-- <x-mary-menu-item title="Import Routers" icon="o-plus-circle" link="/router/import" /> --}}
        </x-mary-menu-sub>
        {{-- VOUCHERS --}}
        <x-mary-menu-sub title="Vouchers" icon="o-ticket">
            <x-mary-menu-item title="Voucher List" icon="o-rectangle-stack" link="/vouchers" />
            <x-mary-menu-item title="Generate Voucher" icon="o-plus" link="/vouchers/generate" />
            <x-mary-menu-item title="Print Vouchers" icon="o-printer" link="/vouchers/bulk-manager" />
            <x-mary-menu-item title="Bulk Delete" icon="o-trash" link="/vouchers/bulk-manager" />
        </x-mary-menu-sub>

        {{-- HOTSPOT USERS --}}
        <x-mary-menu-sub title="Hotspot Users" icon="o-user-group">
            <x-mary-menu-item title="Create Single User" icon="o-user-plus" link="/hotspot/users/create" />
            <x-mary-menu-item title="Active Sessions" icon="o-signal" link="/hotspot/sessions" />
            <x-mary-menu-item title="Session Cookies" icon="o-user-minus" link="/hotspot/session-cookies" />
            <x-mary-menu-item title="Hotspot Logs" icon="o-squares-plus" link="/hotspot/logs" />
        </x-mary-menu-sub>

        {{-- REPORTS --}}
        <x-mary-menu-sub title="Reports" icon="o-document-chart-bar">
            <x-mary-menu-item title="Sales Summary" icon="o-banknotes" link="/reports/sales" />
            <x-mary-menu-item title="Voucher Logs" icon="o-chart-bar" link="/reports/vouchers" />
        </x-mary-menu-sub>

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
