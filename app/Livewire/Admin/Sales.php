<?php

namespace App\Livewire\Admin;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Sales extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';
    public string $status = 'all';
    public string $billing_cycle = 'all';
    public int $perPage = 15;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
        'billing_cycle' => ['except' => 'all'],
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
        $sales = $this->salesQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $stats = [
            'total_sales' => Subscription::count(),
            'total_revenue' => Subscription::sum('amount'),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'total_discount_given' => Subscription::whereRaw('discount_percent > 0')
                ->selectRaw('SUM(original_price - amount) as total_discount')
                ->value('total_discount') ?? 0,
        ];

        return view('livewire.admin.sales', [
            'sales' => $sales,
            'stats' => $stats,
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

    public function updatedBillingCycle(): void
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

    private function salesQuery(): Builder
    {
        return Subscription::with(['user', 'package'])
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->whereHas('user', function (Builder $userQuery) {
                        $userQuery->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%");
                    })
                        ->orWhereHas('package', function (Builder $packageQuery) {
                            $packageQuery->where('name', 'like', "%{$this->search}%");
                        })
                        ->orWhere('promo_code', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status !== 'all', function (Builder $query) {
                $query->where('status', $this->status);
            })
            ->when($this->billing_cycle !== 'all', function (Builder $query) {
                $query->where('billing_cycle', $this->billing_cycle);
            });
    }

    public function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'active' => 'badge-success',
            'cancelled' => 'badge-error',
            'expired' => 'badge-warning',
            'suspended' => 'badge-warning',
            'grace_period' => 'badge-info',
            default => 'badge-ghost',
        };
    }
}
