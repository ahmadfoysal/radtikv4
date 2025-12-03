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
            <x-mary-menu-item title="Router Groups" icon="o-rectangle-group" link="/routers/groups" />
            <x-mary-menu-item title="Router Logs" icon="o-clipboard-document-list" link="/routers/logs" />
        </x-mary-menu-sub>

        {{-- HOTSPOT USERS --}}
        <x-mary-menu-sub title="Hotspot Users" icon="o-user-group">
            <x-mary-menu-item title="User List" icon="o-identification" link="/hotspot/users" />
            <x-mary-menu-item title="Create User" icon="o-user-plus" link="/hotspot/users/create" />
            <x-mary-menu-item title="Active Sessions" icon="o-signal" link="/hotspot/sessions" />
            <x-mary-menu-item title="Disconnected Users" icon="o-user-minus" link="/hotspot/disconnected" />
            <x-mary-menu-item title="Bulk Generator" icon="o-squares-plus" link="/hotspot/users/bulk" />
        </x-mary-menu-sub>

        {{-- VOUCHERS --}}
        <x-mary-menu-sub title="Vouchers" icon="o-ticket">
            <x-mary-menu-item title="Voucher List" icon="o-rectangle-stack" link="/vouchers" />
            <x-mary-menu-item title="Generate Voucher" icon="o-plus" link="/vouchers/generate" />
            <x-mary-menu-item title="Print Vouchers" icon="o-printer" link="/vouchers/bulk-manager" />
            <x-mary-menu-item title="Bulk Delete" icon="o-trash" link="/vouchers/bulk-manager" />

        </x-mary-menu-sub>

        <x-mary-menu-sub title="Profile" icon="o-rectangle-group">
            <x-mary-menu-item title="Profile List" icon="o-list-bullet" link="/profiles" />
            <x-mary-menu-item title="Add Profile" icon="o-plus" link="/profile/add" />
        </x-mary-menu-sub>

        {{-- BANDWIDTH & MONITORING --}}
        <x-mary-menu-sub title="Bandwidth & Monitor" icon="o-chart-bar">
            <x-mary-menu-item title="Live Bandwidth" icon="o-chart-bar" link="/bandwidth/live" />
            <x-mary-menu-item title="History (Last 30 Days)" icon="o-clock" link="/bandwidth/history" />
            <x-mary-menu-item title="Router Health" icon="o-heart" link="/monitor/health" />
            <x-mary-menu-item title="Ping & Latency" icon="o-rss" link="/monitor/ping" />
        </x-mary-menu-sub>

        {{-- RADIUS MANAGEMENT --}}
        <x-mary-menu-sub title="RADIUS" icon="o-cloud">

            {{-- Profiles --}}
            <x-mary-menu-item title="Radius Profiles" icon="o-rectangle-group" link="/radius/profiles" wire:navigate />

            <x-mary-menu-item title="Add Profile" icon="o-plus" link="/radius/profile/add" wire:navigate />



            {{-- Radius Servers --}}
            <x-mary-menu-item title="Radius Servers" icon="o-server" link="/radius/servers" wire:navigate />

            <x-mary-menu-item title="Add Radius Server" icon="o-server-stack" link="/radius/servers/add"
                wire:navigate />



            {{-- Optional but VERY useful items --}}
            <x-mary-menu-item title="RADIUS Logs" icon="o-document-magnifying-glass" link="/radius/logs"
                wire:navigate />

            <x-mary-menu-item title="Online Users" icon="o-users" link="/radius/online-users" wire:navigate />

            <x-mary-menu-item title="Radacct Summary" icon="o-chart-bar" link="/radius/radacct" wire:navigate />

        </x-mary-menu-sub>


        {{-- RESELLERS --}}
        <x-mary-menu-sub title="Resellers" icon="o-user-group">
            <x-mary-menu-item title="All Resellers" icon="o-list-bullet" link="/users" />
            <x-mary-menu-item title="Commission Reports" icon="o-banknotes" link="/resellers/commission" />
            <x-mary-menu-item title="Voucher Sales" icon="o-ticket" link="/resellers/vouchers" />
            <x-mary-menu-item title="Add Reseller" icon="o-user-plus" link="/user/add" />
        </x-mary-menu-sub>

        {{-- BILLING & TRANSACTIONS --}}
        <x-mary-menu-sub title="Billing" icon="o-credit-card">
            <x-mary-menu-item title="Balance & Top-up" icon="o-banknotes" link="/billing/balance" />
            <x-mary-menu-item title="Transactions" icon="o-receipt-percent" link="/billing/transactions" />
            <x-mary-menu-item title="Plans & Packages" icon="o-cube" link="/billing/plans" />
            <x-mary-menu-item title="Payment Methods" icon="o-wallet" link="/billing/methods" />
        </x-mary-menu-sub>

        {{-- AUTOMATION & BACKUP --}}
        <x-mary-menu-sub title="Automation" icon="o-arrow-path-rounded-square">
            <x-mary-menu-item title="Auto Backup" icon="o-cloud-arrow-up" link="/automation/backup" />
            <x-mary-menu-item title="Auto Reboot" icon="o-arrow-uturn-left" link="/automation/reboot" />
            <x-mary-menu-item title="Notifications" icon="o-bell-alert" link="/automation/notifications" />
        </x-mary-menu-sub>

        {{-- REPORTS --}}
        <x-mary-menu-sub title="Reports" icon="o-document-chart-bar">
            <x-mary-menu-item title="User Activity" icon="o-user-circle" link="/reports/users" />
            <x-mary-menu-item title="Bandwidth Usage" icon="o-chart-pie" link="/reports/bandwidth" />
            <x-mary-menu-item title="Sales Summary" icon="o-banknotes" link="/reports/sales" />
            <x-mary-menu-item title="System Logs" icon="o-document-text" link="/reports/logs" />
        </x-mary-menu-sub>

        {{-- ADMIN SETTINGS --}}
        <x-mary-menu-sub title="Admin Settings" icon="o-cog-6-tooth">
            <x-mary-menu-item title="General Settings" icon="o-adjustments-horizontal" link="/settings/general" />
            <x-mary-menu-item title="Email & SMTP" icon="o-envelope-open" link="/settings/email" />
            <x-mary-menu-item title="API Keys" icon="o-key" link="/settings/api" />
            <x-mary-menu-item title="Roles & Permissions" icon="o-lock-closed" link="/settings/roles" />
        </x-mary-menu-sub>

        {{-- SUPPORT --}}
        <x-mary-menu-sub title="Support" icon="o-lifebuoy">
            <x-mary-menu-item title="Documentation" icon="o-book-open" link="/docs" />
            <x-mary-menu-item title="Knowledge Base" icon="o-light-bulb" link="/help" />
            <x-mary-menu-item title="Contact Support" icon="o-chat-bubble-left-right" link="/support/contact" />
        </x-mary-menu-sub>

    </x-mary-menu>

</div>
