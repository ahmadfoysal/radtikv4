<?php

namespace App\Livewire\Router;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use App\MikroTik\Installer\ScriptInstaller;

use Mary\Traits\Toast;

class Index extends Component
{
    use WithPagination, Toast;

    public string $q = '';
    public int $perPage = 12;

    public ?int $pingingId = null;
    public ?int $pingedId = null;
    public ?bool $pingSuccess = null;


    protected $queryString = [
        'q'    => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    protected function paginatedRouters(): LengthAwarePaginator
    {

        //redirect error if user in not  admin

        return auth()->user()
            ->routers()
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
        $this->pingingId = $id;
        $this->pingedId = null;
        $this->pingSuccess = null;

        try {
            $router = auth()->user()->routers()->findOrFail($id);
            $svc = app(\App\MikroTik\Actions\RouterManager::class);
            $ok  = $svc->pingRouter($router);

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
            $this->error("Error: " . $e->getMessage());
        } finally {
            $this->pingingId = null;
        }
    }



    public function delete(int $id): void
    {
        $router = auth()->user()->routers()->findOrFail($id);
        $router->delete();

        $paginator = $this->paginatedRouters();

        if ($paginator->currentPage() > 1 && $paginator->isEmpty()) {
            $this->previousPage(); // অথবা previousPage('page') যদি কাস্টম পেজ নেম থাকে
        }

        $this->success('Router deleted successfully.');
    }

    public function installScripts(int $routerId): void
    {
        try {
            $router = auth()->user()->routers()->findOrFail($routerId);

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
        return view('livewire.router.index', [
            'routers' => $this->paginatedRouters(),
        ]);
    }
}
