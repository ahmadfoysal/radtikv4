<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Permission;

class ResellerPermissions extends Component
{
    use Toast;

    public ?int $resellerId = null;

    public array $selectedPermissions = [];

    /**
     * Dropdown options for resellers
     *
     * @var array<int, array{id:int,name:string,email?:string}>
     */
    public array $resellerOptions = [];

    /**
     * Available router permissions
     *
     * @var array<int, array{id:int,name:string,description?:string}>
     */
    public array $routerPermissions = [];

    /**
     * Current reseller's assigned permissions
     *
     * @var array<int>
     */
    public array $assignedPermissions = [];

    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user && ($user->isAdmin() || $user->isSuperAdmin()), 403);

        $this->loadResellerOptions();
        $this->loadRouterPermissions();
    }

    public function render(): View
    {
        return view('livewire.admin.reseller-permissions')
            ->title(__('Reseller Permissions'));
    }

    public function updatedResellerId($value): void
    {
        if (!$value) {
            $this->selectedPermissions = [];
            $this->assignedPermissions = [];
            return;
        }

        $this->loadResellerPermissions();
    }

    public function savePermissions(): void
    {
        $this->validate([
            'resellerId' => ['required', 'integer', 'exists:users,id'],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['string'],
        ]);

        $reseller = User::role('reseller')
            ->where('id', $this->resellerId)
            ->first();

        if (!$reseller) {
            $this->error('Reseller not found.');
            return;
        }

        // Check if admin owns this reseller
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $reseller->admin_id !== $user->id) {
            $this->error('You can only manage permissions for your own resellers.');
            return;
        }

        try {
            // Sync permissions
            $reseller->syncPermissions($this->selectedPermissions);

            $this->success('Permissions updated successfully for ' . $reseller->name . '.');

            $this->loadResellerPermissions();
        } catch (\Throwable $e) {
            $this->error('Failed to update permissions: ' . $e->getMessage());
        }
    }

    public function searchResellers(string $value = ''): void
    {
        $this->loadResellerOptions($value);
    }

    public function search(string $value = ''): void
    {
        $this->loadResellerOptions($value);
    }

    public function selectAllPermissions(): void
    {
        $allPermissionNames = collect($this->routerPermissions)
            ->pluck('name')
            ->toArray();

        // Check if all permissions are already selected
        $allSelected = count($allPermissionNames) === count($this->selectedPermissions)
            && empty(array_diff($allPermissionNames, $this->selectedPermissions));

        if ($allSelected) {
            // Deselect all
            $this->selectedPermissions = [];
        } else {
            // Select all
            $this->selectedPermissions = $allPermissionNames;
        }
    }

    public function areAllPermissionsSelected(): bool
    {
        if (empty($this->routerPermissions)) {
            return false;
        }

        $allPermissionNames = collect($this->routerPermissions)
            ->pluck('name')
            ->toArray();

        return count($allPermissionNames) === count($this->selectedPermissions)
            && empty(array_diff($allPermissionNames, $this->selectedPermissions));
    }

    protected function loadResellerOptions(?string $term = null): void
    {
        $this->resellerOptions = $this->resellerQuery($term)
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn(User $reseller) => [
                'id' => $reseller->id,
                'name' => $reseller->name,
                'email' => $reseller->email,
            ])
            ->toArray();

        $this->ensureSelectedResellerIncluded();
    }

    protected function loadRouterPermissions(): void
    {
        $permissions = Permission::orderBy('name')->get();

        $this->routerPermissions = $permissions->map(function (Permission $permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'description' => $this->getPermissionDescription($permission->name),
            ];
        })->toArray();
    }

    protected function loadResellerPermissions(): void
    {
        if (!$this->resellerId) {
            $this->selectedPermissions = [];
            $this->assignedPermissions = [];
            return;
        }

        $reseller = User::role('reseller')
            ->where('id', $this->resellerId)
            ->with('permissions')
            ->first();

        if (!$reseller) {
            $this->selectedPermissions = [];
            $this->assignedPermissions = [];
            return;
        }

        // Get only router-related permissions
        $routerPermissionNames = collect($this->routerPermissions)->pluck('name')->toArray();
        $assigned = $reseller->permissions()
            ->whereIn('name', $routerPermissionNames)
            ->pluck('name')
            ->toArray();

        $this->selectedPermissions = $assigned;
        $this->assignedPermissions = $assigned;
    }

    protected function resellerQuery(?string $term = null): Builder
    {
        $query = User::query()
            ->role('reseller')
            ->select('id', 'name', 'email', 'admin_id');

        $user = Auth::user();

        if ($user && !$user->isSuperAdmin()) {
            $query->where('admin_id', $user->id);
        }

        if ($term !== null && trim($term) !== '') {
            $value = '%' . trim($term) . '%';
            $query->where(function (Builder $builder) use ($value) {
                $builder->where('name', 'like', $value)
                    ->orWhere('email', 'like', $value);
            });
        }

        return $query;
    }

    protected function ensureSelectedResellerIncluded(): void
    {
        if (!$this->resellerId) {
            return;
        }

        $exists = collect($this->resellerOptions)
            ->contains(fn($option) => (int) $option['id'] === (int) $this->resellerId);

        if (!$exists) {
            $reseller = $this->resellerQuery(null)
                ->where('id', $this->resellerId)
                ->first();

            if ($reseller) {
                $this->resellerOptions[] = [
                    'id' => $reseller->id,
                    'name' => $reseller->name,
                    'email' => $reseller->email,
                ];
            }
        }
    }

    protected function getPermissionDescription(string $permissionName): string
    {
        return match ($permissionName) {
            // Router Management
            'view_assigned_routers' => 'View list of assigned routers',
            'view_router_details' => 'View detailed information about routers',
            'edit_assigned_routers' => 'Edit router settings and configuration',
            'delete_assigned_routers' => 'Delete routers from the system',
            'ping_assigned_routers' => 'Test router connectivity (ping)',
            'view_router_status' => 'View router online/offline status',
            'view_router_logs' => 'View router activity logs',
            'view_router_statistics' => 'View router usage statistics and reports',
            'manage_router_vouchers' => 'Create, edit, and manage vouchers for routers',
            'manage_router_profiles' => 'Manage hotspot profiles on routers',
            'sync_router_data' => 'Synchronize data with MikroTik routers',
            'import_router_configs' => 'Import router configurations',

            // Voucher Management
            'view_vouchers' => 'View list of vouchers',
            'generate_vouchers' => 'Generate new vouchers',
            'generate_voucher_batches' => 'Generate vouchers in batches',
            'print_vouchers' => 'Print vouchers',
            'print_single_voucher' => 'Print individual voucher',
            'reset_voucher' => 'Reset voucher password and status',
            'bulk_delete_vouchers' => 'Delete multiple vouchers at once',

            // Hotspot User Management
            'create_single_user' => 'Create a single hotspot user',
            'view_active_sessions' => 'View currently active hotspot sessions',
            'view_session_cookies' => 'View session cookies for hotspot users',
            'view_hotspot_logs' => 'View hotspot activity logs',
            'view_sales_summary' => 'View sales summary reports',

            default => 'Router management permission',
        };
    }
}
