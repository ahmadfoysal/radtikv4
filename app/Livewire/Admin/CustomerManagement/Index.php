<?php

namespace App\Livewire\Admin\CustomerManagement;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Index extends Component
{
    use AuthorizesRequests, Toast, WithPagination;

    public string $search = '';
    public string $status = 'all';
    public int $perPage = 15;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
        'perPage' => ['except' => 15],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403, 'Access denied. Superadmin only.');
    }

    public function render()
    {
        $customers = $this->customerQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.customer-management.index', [
            'customers' => $customers,
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function getSortIcon(string $field): string
    {
        if ($this->sortField !== $field) {
            return 'o-arrows-up-down';
        }

        return $this->sortDirection === 'asc' ? 'o-arrow-up' : 'o-arrow-down';
    }

    private function customerQuery(): Builder
    {
        return User::with(['roles', 'routers', 'subscriptions' => function ($query) {
            $query->latest()->limit(1);
        }])
            ->whereHas('roles', function (Builder $query) {
                $query->where('name', 'admin');
            })
            ->withCount('routers')
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('address', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status !== 'all', function (Builder $query) {
                if ($this->status === 'active') {
                    $query->where('is_active', true)->whereNull('suspended_at');
                } elseif ($this->status === 'suspended') {
                    $query->whereNotNull('suspended_at');
                } elseif ($this->status === 'inactive') {
                    $query->where('is_active', false);
                }
            });
    }

    public function getStatusBadgeClass(User $user): string
    {
        if ($user->suspended_at) {
            return 'badge-error';
        }

        return $user->is_active ? 'badge-success' : 'badge-warning';
    }

    public function getStatusText(User $user): string
    {
        if ($user->suspended_at) {
            return 'Suspended';
        }

        return $user->is_active ? 'Active' : 'Inactive';
    }

    /** Impersonate user (only for superadmin) */
    public function impersonate(int $userId): void
    {
        $currentUser = auth()->user();

        // Only superadmin can impersonate
        if (!$currentUser->hasRole('superadmin')) {
            $this->error(
                title: 'Access Denied',
                description: 'You do not have permission to impersonate users.'
            );
            return;
        }

        $userToImpersonate = User::find($userId);

        if (!$userToImpersonate) {
            $this->error(
                title: 'User Not Found',
                description: 'The user you are trying to impersonate does not exist.'
            );
            return;
        }

        // Store the original user ID in session to allow returning
        session(['impersonator_id' => $currentUser->id]);

        // Log in as the target user
        auth()->login($userToImpersonate);

        $this->success(
            title: 'Impersonation Started',
            description: "You are now logged in as {$userToImpersonate->name}."
        );

        // Redirect to dashboard
        redirect()->to('/dashboard');
    }
}
