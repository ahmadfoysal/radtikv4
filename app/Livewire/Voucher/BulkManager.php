<?php

namespace App\Livewire\Voucher;

use App\Models\Router;
use App\Models\Voucher;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;

class BulkManager extends Component
{
    use Toast;

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
        ['key' => 'profile.name', 'label' => 'Profile', 'sortable' => false],
        ['key' => 'batch', 'label' => 'Batch'],
        ['key' => 'status', 'label' => 'Status'],
    ];

    public function mount()
    {
        // No initial load needed
    }

    public function updated($prop)
    {
        // Load batches when router selected
        if ($prop === 'router_id') {
            $this->batch = null;
            $this->loadBatches();
        }
    }

    public function loadBatches()
    {
        if (!$this->router_id) {
            $this->batches = [];
            return;
        }

        // Fix for SQL Strict Mode (Error 3065):
        // Group by batch and order by the latest created_at within that group
        $this->batches = Voucher::where('router_id', $this->router_id)
            ->select('batch')
            ->groupBy('batch')
            ->orderByRaw('MAX(created_at) DESC')
            ->limit(50)
            ->pluck('batch')
            ->map(fn($b) => ['id' => $b, 'name' => $b])
            ->toArray();
    }

    // Central query logic
    public function getQuery(): ?Builder
    {
        if (!$this->router_id) {
            return null;
        }

        return Voucher::query()
            ->with('profile')
            ->where('router_id', $this->router_id)
            ->when($this->batch, fn($q) => $q->where('batch', $this->batch))
            ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
            ->orderBy('id', 'desc');
    }

    // Handles both Bulk Delete (no arg) and Single Delete (id arg)
    public function delete($id = null)
    {
        if ($id) {
            // Single Delete
            Voucher::where('id', $id)->delete();
            $this->success("Voucher deleted successfully.");
        } else {
            // Bulk Delete
            $query = $this->getQuery();

            if (!$query) return;

            $count = $query->count();

            if ($count === 0) {
                $this->error('No vouchers found to delete.');
                return;
            }

            $query->chunkById(1000, function ($vouchers) {
                Voucher::whereIn('id', $vouchers->pluck('id'))->delete();
            });

            $this->success("{$count} Vouchers deleted successfully.");

            // Reset filters after bulk delete
            $this->reset(['batch', 'status']);
        }
    }

    public function print()
    {
        $query = $this->getQuery();

        if (!$query || $query->count() === 0) {
            $this->error('No vouchers to print.');
            return;
        }

        return redirect()->route('vouchers.print', [
            'router_id' => $this->router_id,
            'batch' => $this->batch,
            'status' => $this->status,
        ]);
    }

    public function render()
    {
        $query = $this->getQuery();

        return view('livewire.voucher.bulk-manager', [
            'routers' => Router::orderBy('name')->get(['id', 'name']),
            'vouchers' => $query ? $query->get() : [],
            'total_count' => $query ? $query->count() : 0,
        ]);
    }
}
