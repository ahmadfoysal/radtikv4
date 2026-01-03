<?php

namespace App\Livewire\Voucher;

use App\Services\VoucherService;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Index extends Component
{
    use Toast;
    use WithPagination;

    protected VoucherService $voucherService;

    public function boot(VoucherService $voucherService): void
    {
        $this->voucherService = $voucherService;
    }

    public string $q = '';

    public int $perPage = 24;

    public string $status = 'all';

    public string $routerFilter = 'all';

    protected $queryString = [
        'q' => ['except' => ''],
        'status' => ['except' => 'all'],
        'createdBy' => ['except' => 'all'],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        $this->authorize('view_vouchers');
    }

    public function updatingQ()
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

    protected function vouchers()
    {
        $user = auth()->user();

        return $this->voucherService->getPaginatedVouchers(
            $user,
            [
                'q' => $this->q,
                'status' => $this->status,
                'routerFilter' => $this->routerFilter,
            ],
            $this->perPage
        );
    }

    protected function statusColor(string $s): string
    {
        return match ($s) {
            'new' => 'badge-info',
            'delivered' => 'badge-primary',
            'active' => 'badge-success',
            'expired' => 'badge-warning',
            'used' => 'badge-neutral',
            'disabled' => 'badge-error',
            default => 'badge-ghost',
        };
    }

    public function delete(int $id)
    {
        $this->authorize('delete_vouchers');

        $user = auth()->user();

        // Get voucher before deletion for logging
        $voucher = \App\Models\Voucher::find($id);

        if ($voucher) {
            // Log the deletion with reason BEFORE deleting
            \App\Services\VoucherLogger::log(
                $voucher,
                $voucher->router,
                'deleted',
                [
                    'deleted_by' => auth()->id(),
                    'batch' => $voucher->batch,
                    'status' => $voucher->status,
                ],
                'Manual deletion by user'
            );
        }

        $result = $this->voucherService->deleteVoucher($user, $id);

        if ($result['success']) {
            $this->success(title: 'Deleted');
            $this->resetPage();
        } else {
            $this->error($result['message']);
        }
    }

    public function toggleDisable(int $id)
    {
        $this->authorize('edit_vouchers');

        $user = auth()->user();
        $result = $this->voucherService->toggleVoucherStatus($user, $id);

        if ($result['success']) {
            $this->success(title: 'Updated');
        } else {
            $this->error($result['message']);
        }
    }

    //reset voucher
    public function resetVoucher(int $id)
    {
        $this->authorize('reset_vouchers');
        $user = auth()->user();
        $result = $this->voucherService->resetVoucher($user, $id);
        if ($result['success']) {
            $this->success(title: 'Reset', description: 'Voucher reset successfully.');
        } else {
            $this->error(title: 'Error', description: $result['message']);
        }
    }

    public function render()
    {
        $user = auth()->user();
        $routers = $user->getAccessibleRouters()->map(fn($router) => [
            'id' => $router->id,
            'name' => $router->name,
        ]);

        return view('livewire.voucher.index', [
            'vouchers' => $this->vouchers(),
            'routers' => $routers,

            // send helpers to view
            'statusColor' => fn($s) => $this->statusColor($s),
        ]);
    }
}
