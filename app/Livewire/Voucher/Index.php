<?php

namespace App\Livewire\Voucher;

use App\Models\User;
use App\Models\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $q = '';
    public int $perPage = 24;

    // Filters
    public string $channel   = 'all';   // all|mikrotik|radius
    public string $status    = 'all';   // all|new|delivered|active|expired|used
    public string $createdBy = 'all';   // all|me|<user_id>

    // Keep these in URL (nice UX)
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
    public function updatingCreatedBy()
    {
        $this->resetPage();
    }

    protected function vouchers(): LengthAwarePaginator
    {
        $user = auth()->user();

        return Voucher::query()
            // Optional: limit to user's org/scope as needed
            ->when($this->q !== '', function ($q) {
                $term = '%' . mb_strtolower($this->q) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->whereRaw('LOWER(username) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(batch) LIKE ?', [$term]);
                });
            })
            ->when($this->channel !== 'all', function ($q) {
                $q->where('delivery_channel', $this->channel); // 'mikrotik' | 'radius'
            })
            ->when($this->status !== 'all', function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->createdBy === 'me', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            })
            ->when($this->createdBy !== 'all' && $this->createdBy !== 'me', function ($q) {
                if (ctype_digit($this->createdBy)) {
                    $q->where('created_by', (int) $this->createdBy);
                }
            })
            ->orderByDesc('id')
            ->paginate($this->perPage);
    }

    protected function statusColor(string $status): string
    {
        return match ($status) {
            'new'       => 'badge-info',
            'delivered' => 'badge-primary',
            'active'    => 'badge-success',
            'expired'   => 'badge-warning',
            'used'      => 'badge-neutral',
            default     => 'badge-ghost',
        };
    }

    protected function channelColor(?string $channel): string
    {
        return match ($channel) {
            'mikrotik' => 'text-primary',
            'radius'   => 'text-accent',
            default    => 'text-base-content/60',
        };
    }

    public function render(): View
    {
        $vouchers = $this->vouchers();

        // Creator list for filter dropdown
        $creatorIds = Voucher::query()->distinct()->pluck('created_by')->filter()->values();
        $creators   = User::whereIn('id', $creatorIds)->orderBy('name')->get(['id', 'name']);

        return view('livewire.voucher.index', [
            'vouchers'   => $vouchers,
            'creators'   => $creators,
            // helpers
            'statusColor' => fn($s) => $this->statusColor($s),
            'channelColor' => fn($c) => $this->channelColor($c),
        ]);
    }
}
