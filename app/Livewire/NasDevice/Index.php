<?php

namespace App\Livewire\NasDevice;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Index extends Component
{
    use AuthorizesRequests, Toast, WithPagination;

    public string $q = '';

    public int $perPage = 12;

    protected $queryString = [
        'q' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->authorize('view_router');
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    protected function paginatedNasDevices(): LengthAwarePaginator
    {
        $user = auth()->user();
        $accessibleRouters = $user->getAccessibleRouters();
        $accessibleRouterIds = $accessibleRouters->pluck('id')->toArray();

        return \App\Models\Router::query()
            ->whereIn('id', $accessibleRouterIds)
            ->where('is_nas_device', true)
            ->with(['parentRouter', 'radiusServer', 'zone'])
            ->when($this->q !== '', function ($q) {
                $term = '%' . mb_strtolower($this->q) . '%';

                $q->where(function ($sub) use ($term) {
                    $sub->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(address) LIKE ?', [$term]);
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function render(): View
    {
        $this->authorize('view_router');
        return view('livewire.nas-device.index', [
            'nasDevices' => $this->paginatedNasDevices(),
        ]);
    }
}
