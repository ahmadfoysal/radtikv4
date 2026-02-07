<div>
    {{-- resources/views/components/sidebar-menu.blade.php --}}
    <x-mary-menu activate-by-route>

        {{-- DASHBOARD --}}
        <x-mary-menu-item title="Dashboard" icon="o-home" link="/dashboard" />

        {{-- ROUTER MANAGEMENT --}}
        <x-mary-menu-sub title="Routers" icon="o-server-stack">
            <x-mary-menu-item title="All Routers" icon="o-list-bullet" link="/routers" />
            <x-mary-menu-item title="Add New Router" icon="o-plus-circle" link="/router/add" />
            <x-mary-menu-item title="Import Routers" icon="o-plus-circle" link="/router/import" />
        </x-mary-menu-sub>

        {{-- PROFILE MANAGEMENT --}}
        <x-mary-menu-sub title="Profile" icon="o-rectangle-group">
            <x-mary-menu-item title="Profile List" icon="o-list-bullet" link="/profiles" />
            <x-mary-menu-item title="Add Profile" icon="o-plus" link="/profile/add" />
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


        {{-- RESELLERS --}}
        <x-mary-menu-sub title="Resellers" icon="o-user-group">
            <x-mary-menu-item title="All Resellers" icon="o-list-bullet" link="/users" />
            <x-mary-menu-item title="Add Reseller" icon="o-user-plus" link="/user/add" />
            <x-mary-menu-item title="Assign Router" icon="o-server" link="/reseller/assign-router" />
            <x-mary-menu-item title="Assign Profile" icon="o-rectangle-group" link="/reseller/assign-profile" />
            <x-mary-menu-item title="Reseller Permissions" icon="o-cube" link="/resellers/permissions" />
        </x-mary-menu-sub>

        {{-- SUBSCRIPTION MANAGEMENT --}}
        <x-mary-menu-sub title="Subscription" icon="o-cube">
            <x-mary-menu-item title="My Subscription" icon="o-check-badge" link="/subscription" />
            <x-mary-menu-item title="Subscription History" icon="o-clock" link="/subscription/history" />
        </x-mary-menu-sub>

        {{-- BILLING & TRANSACTIONS --}}
        <x-mary-menu-sub title="Billing" icon="o-credit-card">
            <x-mary-menu-item title="Invoices" icon="o-document-text" link="/billing/invoices" />
            <x-mary-menu-item title="Balance & Top-up" icon="o-banknotes" link="/billing/add-balance" />
        </x-mary-menu-sub>

        {{-- REPORTS --}}
        <x-mary-menu-sub title="Reports" icon="o-document-chart-bar">
            <x-mary-menu-item title="Voucher Logs" icon="o-clipboard-document-list" link="/vouchers/logs" />
            <x-mary-menu-item title="Sales Summary" icon="o-chart-bar" link="/billing/sales-summary" />
        </x-mary-menu-sub>

        {{-- ADMIN SETTINGS --}}
        <x-mary-menu-sub title="Admin Settings" icon="o-cog-6-tooth">
            <x-mary-menu-item title="General Settings" icon="o-adjustments-horizontal" link="/admin/general-settings"
                wire:navigate />
            {{-- <x-mary-menu-item title="Theme Settings" icon="o-paint-brush" link="/admin/theme-settings" wire:navigate />
            <x-mary-menu-item title="Notification & Email" icon="o-envelope-open" link="/settings/email" /> --}}
            <x-mary-menu-item title="Profile & Security" icon="o-shield-check" link="/settings/profile" wire:navigate />
            <x-mary-menu-item title="Zone Management" icon="o-globe-alt" link="/zones" />
            <x-mary-menu-item title="RADIUS Servers" icon="o-server" link="/radius" />
            <x-mary-menu-item title="RADIUS Setup Guide" icon="o-book-open" link="/radius/setup-guide" />
        </x-mary-menu-sub>
        {{-- SYSTEM LOGS --}}
        <x-mary-menu-item title="System Logs" icon="o-document-text" link="/reports/logs" />


        {{-- SUPPORT --}}
        <x-mary-menu-sub title="Help & Support" icon="o-lifebuoy">
            <x-mary-menu-item title="Documentation" icon="o-book-open" link="/docs" />
            <x-mary-menu-item title="Knowledge Base" icon="o-light-bulb" link="/knowledgebase" />
            <x-mary-menu-item title="Contact Support" icon="o-chat-bubble-left-right" link="/support/contact" />
        </x-mary-menu-sub>

    </x-mary-menu>

</div>
