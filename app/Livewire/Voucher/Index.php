<?php

namespace App\Livewire\Voucher;

use App\Models\User;
use App\Models\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Router;

class Index extends Component
{
    use WithPagination;
    use Toast;

    public string $q = '';
    public int $perPage = 24;

    // Filters
    public string $channel   = 'all';  // all | mikrotik | radius
    public string $status    = 'all';
    public string $routerFilter = 'all';

    protected $queryString = [
        'q'         => ['except' => ''],
        'channel'   => ['except' => 'all'],
        'status'    => ['except' => 'all'],
        'createdBy' => ['except' => 'all'],
        'page'      => ['except' => 1],
    ];

    public function updatingQ()
    {
        $this->resetPage();
    }
    public function updatingChannel()
    {
        $this->resetPage();
    }
    public function updatingStatus()
    {
        $this->resetPage();
    }
    public function updatingRouterFilter()
    {
        $this->resetPage();
    }

    public function loadMore(): void
    {
        $this->perPage += 24;
    }

    protected function vouchers(): LengthAwarePaginator
    {

        \Log::info('routerFilter', ['value' => $this->routerFilter]);
        return Voucher::query()
            ->when($this->q !== '', function ($q) {
                $term = '%' . strtolower($this->q) . '%';
                $q->where(function ($s) use ($term) {
                    $s->whereRaw('LOWER(username) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(batch) LIKE ?', [$term]);
                });
            })

            // channel filter from is_radius
            ->when($this->channel === 'mikrotik', fn($q) => $q->where('is_radius', 0))
            ->when($this->channel === 'radius',   fn($q) => $q->where('is_radius', 1))

            // router filter
            ->when(
                $this->routerFilter !== 'all' && $this->routerFilter !== '' && $this->routerFilter !== null,
                fn($q) => $q->where('router_id', (int) $this->routerFilter)
            )


            // status
            ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))

            ->orderByDesc('id')
            ->paginate($this->perPage);
    }


    protected function statusColor(string $s): string
    {
        return match ($s) {
            'new'       => 'badge-info',
            'delivered' => 'badge-primary',
            'active'    => 'badge-success',
            'expired'   => 'badge-warning',
            'used'      => 'badge-neutral',
            'disabled'  => 'badge-error',
            default     => 'badge-ghost',
        };
    }

    protected function channelColor(int $is_radius): string
    {
        return $is_radius ? 'text-accent' : 'text-primary';
    }

    public function delete(int $id)
    {
        $v = Voucher::find($id);
        if (!$v) return;

        $v->delete();

        $this->success(title: 'Deleted');
        $this->resetPage();
    }

    public function toggleDisable(int $id)
    {
        $v = Voucher::find($id);
        if (!$v) return;

        $v->status = $v->status === 'disabled' ? 'new' : 'disabled';
        $v->save();

        $this->success(title: 'Updated');
    }

    public function render()
    {
        return view('livewire.voucher.index', [
            'vouchers' => $this->vouchers(),
            'routers'  => Router::orderBy('name')->get(['id', 'name']),

            // send helpers to view
            'statusColor'  => fn($s) => $this->statusColor($s),
            'channelColor' => fn($c) => $this->channelColor($c),
        ]);
    }
}
