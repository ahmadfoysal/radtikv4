<div>
    {{-- resources/views/components/sidebar-menu.blade.php --}}
    <x-mary-menu activate-by-route>

        {{-- DASHBOARD --}}
        <x-mary-menu-item title="Dashboard" icon="o-home" link="/dashboard" />


        {{-- USER MANAGEMENT --}}
        <x-mary-menu-sub title="User Management" icon="o-users">
            <x-mary-menu-item title="All Users" icon="o-user-group" link="/users" wire:navigate />
            <x-mary-menu-item title="Add User" icon="o-user-plus" link="/user/add" wire:navigate />
        </x-mary-menu-sub>

        {{-- BILLING & TRANSACTIONS --}}
        <x-mary-menu-sub title="Billing" icon="o-credit-card">
            <x-mary-menu-item title="Invoices" icon="o-document-text" link="/billing/invoices" />
            <x-mary-menu-item title="Manual Adjustment" icon="o-pencil" link="/billing/manual-adjustment" />
            <x-mary-menu-item title="Transactions" icon="o-receipt-percent" link="/billing/transactions" />
            <x-mary-menu-item title="Payment Methods" icon="o-wallet" link="/billing/methods" />
        </x-mary-menu-sub>

        {{-- Packages --}}
        <x-mary-menu-sub title="Packages" icon="o-cube">
            <x-mary-menu-item title="All Packages" icon="o-cube" link="/packages" wire:navigate />
            <x-mary-menu-item title="Add Package" icon="o-plus" link="/package/add" wire:navigate />
        </x-mary-menu-sub>

        {{-- REPORTS --}}
        <x-mary-menu-sub title="Reports" icon="o-document-chart-bar">
            <x-mary-menu-item title="User Activity" icon="o-user-circle" link="/reports/users" />
            <x-mary-menu-item title="Sales Summary" icon="o-banknotes" link="/reports/sales" />
        </x-mary-menu-sub>

        {{-- ADMIN SETTINGS --}}
        <x-mary-menu-sub title="Admin Settings" icon="o-cog-6-tooth">
            <x-mary-menu-item title="General Settings" icon="o-adjustments-horizontal" link="/settings/general" />
            <x-mary-menu-item title="Theme Settings" icon="o-paint-brush" link="/admin/theme-settings" wire:navigate />
            <x-mary-menu-item title="Email & SMTP" icon="o-envelope-open" link="/superadmin/email-settings" wire:navigate />
            <x-mary-menu-item title="API Keys" icon="o-key" link="/settings/api" />
            <x-mary-menu-item title="Payment Gateways" icon="o-credit-card" link="/superadmin/payment-gateways"
                wire:navigate />
            <x-mary-menu-item title="Profile & Security" icon="o-shield-check" link="/settings/profile" wire:navigate />
        </x-mary-menu-sub>

        {{-- SUPPORT --}}
        <x-mary-menu-sub title="Support" icon="o-lifebuoy">
            <x-mary-menu-item title="Documentation" icon="o-book-open" link="/docs" />
            <x-mary-menu-item title="Knowledge Base" icon="o-light-bulb" link="/knowledgebase" />
            <x-mary-menu-item title="Contact Support" icon="o-chat-bubble-left-right" link="/support/contact" />
        </x-mary-menu-sub>

    </x-mary-menu>

</div>
