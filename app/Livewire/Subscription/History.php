<?php

namespace App\Livewire\Subscription;

use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use AuthorizesRequests, WithPagination;

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
        $this->authorize('view_subscription');
    }

    public function render(): View
    {
        $subscriptions = $this->subscriptionQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.subscription.history', [
            'subscriptions' => $subscriptions,
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

    private function subscriptionQuery(): Builder
    {
        return Subscription::with(['user', 'package'])
            ->where('user_id', auth()->id())
            ->when($this->search, function (Builder $query) {
                $query->whereHas('package', function (Builder $q) {
                    $q->where('name', 'like', "%{$this->search}%");
                })->orWhere('billing_cycle', 'like', "%{$this->search}%")
                    ->orWhere('promo_code', 'like', "%{$this->search}%");
            })
            ->when($this->status !== 'all', function (Builder $query) {
                $query->where('status', $this->status);
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
