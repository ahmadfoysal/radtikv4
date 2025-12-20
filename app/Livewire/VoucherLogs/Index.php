<?php

namespace App\Livewire\VoucherLogs;

use App\Models\Router;
use App\Models\VoucherLog;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public $router_id = null;
    public $event_type = 'all';
    public $from_date = null;
    public $to_date = null;
    public $search = '';
    public $selectedLogMeta = [];
    public $showDetailsModal = false;

    public function mount(): void
    {
        $this->authorize('view_vouchers');

        // Default to today
        $this->from_date = Carbon::today()->format('Y-m-d');
        $this->to_date = Carbon::today()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRouterId(): void
    {
        $this->resetPage();
    }

    public function updatedEventType(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function getRoutersProperty()
    {
        return auth()->user()->getAccessibleRouters();
    }

    public function showDetails(array $meta): void
    {
        $this->selectedLogMeta = $meta;
        $this->showDetailsModal = true;
    }

    public function render(): View
    {
        $user = auth()->user();
        $accessibleRouterIds = $user->getAccessibleRouters()->pluck('id')->toArray();

        $query = VoucherLog::query()
            ->with(['router', 'voucher', 'user'])
            ->whereIn('router_id', $accessibleRouterIds)
            ->orderByDesc('created_at');

        // Apply router filter
        if ($this->router_id && $this->router_id !== 'all') {
            $query->where('router_id', $this->router_id);
        }

        // Apply event type filter
        if ($this->event_type && $this->event_type !== 'all') {
            $query->where('event_type', $this->event_type);
        }

        // Apply date range filter
        if ($this->from_date) {
            $query->whereDate('created_at', '>=', $this->from_date);
        }

        if ($this->to_date) {
            $query->whereDate('created_at', '<=', $this->to_date);
        }

        // Apply search filter
        if ($this->search) {
            $search = '%' . $this->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', $search)
                    ->orWhere('router_name', 'like', $search)
                    ->orWhere('profile', 'like', $search);
            });
        }

        $logs = $query->paginate(25);

        $eventTypes = [
            ['id' => 'all', 'name' => 'All Events'],
            ['id' => 'activated', 'name' => 'Activated'],
            ['id' => 'deleted', 'name' => 'Deleted'],
        ];

        $routers = [
            ['id' => 'all', 'name' => 'All Routers'],
            ...$this->routers->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->toArray()
        ];

        return view('livewire.voucher-logs.index', [
            'logs' => $logs,
            'routers' => $routers,
            'eventTypes' => $eventTypes,
        ]);
    }
}
