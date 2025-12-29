<?php

namespace App\Livewire\Voucher;

use App\Models\Router;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Mary\Traits\Toast;

class BulkManager extends Component
{
    use Toast;

    // Filters
    public $router_id;

    public $batch;

    // CHANGE: Default status 'all' ensures vouchers show up immediately after router selection
    public $status = 'all';

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
        $this->authorize('view_voucher_list');
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
        if (! $this->router_id) {
            $this->batches = [];

            return;
        }

        try {
            // Verify user has access to this router
            $user = auth()->user();
            $router = $user->getAuthorizedRouter($this->router_id);

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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->error('You are not authorized to access this router.');
            $this->batches = [];
            $this->router_id = null;
        }
    }

    // Central query logic
    public function getQuery(): ?Builder
    {
        if (! $this->router_id) {
            return null;
        }

        try {
            // Verify user has access to this router
            $user = auth()->user();
            $router = $user->getAuthorizedRouter($this->router_id);

            return Voucher::query()
                ->with('profile')
                ->where('router_id', $this->router_id)
                ->when($this->batch, fn($q) => $q->where('batch', $this->batch))
                ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
                ->orderBy('id', 'desc');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return null;
        }
    }

    // Handles both Bulk Delete (no arg) and Single Delete (id arg)
    public function delete($id = null)
    {
        $this->authorize('bulk_delete_vouchers');
        if ($id) {
            // Single Delete
            $voucher = Voucher::find($id);
            if ($voucher) {
                $voucher->delete(); // Will trigger model's deleted event for logging
            }
            $this->success('Voucher deleted successfully.');
        } else {
            // Bulk Delete
            $query = $this->getQuery();

            if (! $query) {
                return;
            }

            $count = $query->count();

            if ($count === 0) {
                $this->error('No vouchers found to delete.');

                return;
            }

            // Fetch vouchers before deletion for logging
            $query->chunkById(1000, function ($vouchers) {
                foreach ($vouchers as $voucher) {
                    $voucher->delete(); // Will trigger model's deleted event for logging
                }
            });

            // Log bulk voucher deletion
            \App\Models\ActivityLog::log(
                'bulk_deleted',
                "Bulk deleted {$count} vouchers",
                [
                    'count' => $count,
                    'router_id' => $this->router_id,
                    'batch' => $this->batch,
                    'status' => $this->status,
                ]
            );

            $this->success("{$count} Vouchers deleted successfully.");

            // Reset filters after bulk delete (Keep router_id selected)
            $this->reset(['batch', 'status']);
            $this->status = 'all'; // Reset status to all
        }
    }

    public function print()
    {
        $this->authorize('print_vouchers');
        $query = $this->getQuery();

        if (! $query || $query->count() === 0) {
            $this->error('No vouchers to print.');

            return;
        }

        $url = route('vouchers.print', [
            'router_id' => $this->router_id,
            'batch' => $this->batch,
            'status' => $this->status,
        ]);

        $this->js("window.open('$url', '_blank');");
    }

    public function printVoucher(int $voucherId): void
    {
        if (! $this->router_id) {
            $this->error('Select a router first.');

            return;
        }

        try {
            // Verify user has access to this router
            $user = auth()->user();
            $router = $user->getAuthorizedRouter($this->router_id);

            $voucher = Voucher::where('router_id', $this->router_id)->find($voucherId);

            if (! $voucher) {
                $this->error('Voucher not found for the selected router.');

                return;
            }

            $url = route('vouchers.print.single', ['voucher' => $voucherId]);

            $this->js("window.open('$url', '_blank');");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->error('You are not authorized to access this router.');
        }
    }

    public function render()
    {
        $query = $this->getQuery();
        $user = auth()->user();
        $routers = $user->getAccessibleRouters()->map(fn($router) => [
            'id' => $router->id,
            'name' => $router->name,
        ]);

        return view('livewire.voucher.bulk-manager', [
            'routers' => $routers,
            'vouchers' => $query ? $query->get() : [],
            'total_count' => $query ? $query->count() : 0,
        ]);
    }
}
