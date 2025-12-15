<?php

namespace App\Livewire\Router;

use App\MikroTik\Installer\ScriptInstaller;
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

    public ?int $pingingId = null;

    public ?int $pingedId = null;

    public ?bool $pingSuccess = null;

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

    protected function paginatedRouters(): LengthAwarePaginator
    {
        $user = auth()->user();
        $accessibleRouters = $user->getAccessibleRouters();
        $accessibleRouterIds = $accessibleRouters->pluck('id')->toArray();

        return \App\Models\Router::query()
            ->whereIn('id', $accessibleRouterIds)
            ->with(['zone', 'voucherTemplate'])
            ->withCount([
                'vouchers as total_vouchers_count',
                'vouchers as active_vouchers_count' => function ($q) {
                    $q->where('status', 'active');
                },
                'vouchers as expired_vouchers_count' => function ($q) {
                    $q->where('status', 'expired');
                },
            ])
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

    public function ping(int $id): void
    {
        $this->authorize('ping_router');

        $this->pingingId = $id;
        $this->pingedId = null;
        $this->pingSuccess = null;

        try {
            $router = auth()->user()->getAuthorizedRouter($id);
            $svc = app(\App\MikroTik\Actions\RouterManager::class);
            $ok = $svc->pingRouter($router);

            $this->pingedId = $id;
            $this->pingSuccess = $ok;

            if ($ok) {
                $this->success("Ping to {$router->address} successful!");
            } else {
                $this->error("Ping to {$router->address} failed!");
            }
        } catch (\Throwable $e) {
            $this->pingedId = $id;
            $this->pingSuccess = false;
            $this->error('Error: ' . $e->getMessage());
        } finally {
            $this->pingingId = null;
        }
    }

    public function installScripts(int $routerId): void
    {
        //authorize user to install scripts
        $this->authorize('install_scripts');

        try {
            $router = auth()->user()->getAuthorizedRouter($routerId);

            /** @var ScriptInstaller $installer */
            $installer = app(ScriptInstaller::class);

            $installer->installAllScriptsAndSchedulers($router);

            $this->success('All RADTik scripts and schedulers installed successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to install scripts: ' . $e->getMessage());
        }
    }

    public function render(): View
    {
        $this->authorize('view_router');
        return view('livewire.router.index', [
            'routers' => $this->paginatedRouters(),
        ]);
    }
}
