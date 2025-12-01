<?php

namespace App\Livewire\Voucher;

use App\Models\Router;
use App\Models\Voucher;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;

class BulkManager extends Component
{
    use Toast, WithPagination;

    // Filters
    public $router_id;
    public $batch;
    public $status = 'inactive';

    // Options for Selects
    public $batches = [];

    // Table Headers
    public array $headers = [
        ['key' => 'username', 'label' => 'Username'],
        ['key' => 'password', 'label' => 'Password'],
        ['key' => 'profile.name', 'label' => 'Profile', 'sortable' => false], // Assumes relationship
        ['key' => 'batch', 'label' => 'Batch'],
        ['key' => 'status', 'label' => 'Status'],
    ];

    public function mount()
    {
        // Initial load logic if needed
    }

    // Reset pagination when filters update
    public function updated($prop)
    {
        if (in_array($prop, ['router_id', 'batch', 'status'])) {
            $this->resetPage();
        }

        if ($prop === 'router_id') {
            $this->loadBatches();
            $this->batch = null;
        }
    }

    public function loadBatches()
    {
        if (!$this->router_id) {
            $this->batches = [];
            return;
        }

        $this->batches = Voucher::where('router_id', $this->router_id)
            ->select('batch')
            ->distinct()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->pluck('batch')
            ->map(fn($b) => ['id' => $b, 'name' => $b])
            ->toArray();
    }

    // Central query logic
    public function getQuery(): Builder
    {
        return Voucher::query()
            ->with('profile') // Eager load profile
            ->when($this->router_id, fn($q) => $q->where('router_id', $this->router_id))
            ->when($this->batch, fn($q) => $q->where('batch', $this->batch))
            ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
            ->orderBy('id', 'desc');
    }

    public function delete()
    {
        $count = $this->getQuery()->count();

        if ($count === 0) {
            $this->error('No vouchers found to delete.');
            return;
        }

        // Bulk delete logic
        $this->getQuery()->chunkById(1000, function ($vouchers) {
            Voucher::whereIn('id', $vouchers->pluck('id'))->delete();
        });

        $this->success("{$count} Vouchers deleted successfully.");

        // Reset specific filters safely
        $this->reset(['batch', 'status']);
        $this->resetPage();
    }

    public function print()
    {
        $count = $this->getQuery()->count();

        if ($count === 0) {
            $this->error('No vouchers to print.');
            return;
        }

        // Redirect to print controller. 
        // Template will be fetched from Router model in the Controller.
        return redirect()->route('vouchers.print', [
            'router_id' => $this->router_id,
            'batch' => $this->batch,
            'status' => $this->status,
        ]);
    }

    public function render()
    {
        return view('livewire.voucher.bulk-manager', [
            'routers' => Router::orderBy('name')->get(['id', 'name']),
            'vouchers' => $this->getQuery()->paginate(10), // Pagination for the table
            'total_count' => $this->getQuery()->count(),   // Total found
        ]);
    }
}
