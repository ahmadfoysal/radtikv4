<?php

namespace App\Livewire\Router;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Router;
use App\Services\MikrotikService;

// class Index extends Component
// {
//     public array $routers = [];

//     public function mount()
//     {
//         // Example static data (replace with DB data)
//         $this->routers = [
//             ['id' => 1, 'name' => 'MKT-01', 'ip' => '10.0.0.1', 'host' => 'core-router.local', 'protocol' => 'api', 'port' => 8728, 'uptime' => '6d 12h', 'status' => 'Online'],
//             ['id' => 2, 'name' => 'MKT-02', 'ip' => '10.0.0.2', 'host' => 'branch-a.local', 'protocol' => 'ssh', 'port' => 22, 'uptime' => '12h 03m', 'status' => 'Online'],
//             ['id' => 3, 'name' => 'MKT-03', 'ip' => '10.0.0.3', 'host' => 'branch-b.local', 'protocol' => 'api', 'port' => 8728, 'uptime' => '—', 'status' => 'Offline'],
//             ['id' => 4, 'name' => 'MKT-04', 'ip' => '10.0.0.4', 'host' => 'branch-c.local', 'protocol' => 'api', 'port' => 8728, 'uptime' => '2d 01h', 'status' => 'Degraded'],
//             ['id' => 5, 'name' => 'MKT-05', 'ip' => '10.0.0.5', 'host' => 'lab-router.local', 'protocol' => 'api', 'port' => 8728, 'uptime' => '8d 06h', 'status' => 'Online'],
//         ];
//     }

//     public function create()
//     {
//         // Redirect or open modal for add-router
//         $this->dispatch('toast', type: 'info', title: 'Add Router', description: 'Redirecting to create form...');
//         // return redirect()->route('routers.create');
//     }

//     public function show($id)
//     {
//         $this->dispatch('toast', type: 'info', title: 'Router Details', description: "Router ID: $id");
//     }

//     public function edit($id)
//     {
//         $this->dispatch('toast', type: 'info', title: 'Edit Router', description: "Editing Router ID: $id");
//     }

//     public function toggle($id)
//     {
//         $this->dispatch('toast', type: 'success', title: 'Router Status Changed', description: "Toggled router ID: $id");
//     }

//     public function render()
//     {
//         return view('livewire.router.index');
//     }
// }

class Index extends Component
{
    use WithPagination;

    public string $q = '';
    public int $perPage = 8;

    /** Cache MikroTik /system/resource/print per router id */
    public array $resources = []; // [router_id => array(resource row)]

    /** Put 'q' and 'page' in the URL (nice UX) */
    protected $queryString = [
        'q'    => ['except' => ''],
        'page' => ['except' => 1],
    ];

    /** Reset page and clear cached resources when search updates */
    public function updatingQ(): void
    {
        $this->resetPage();
        $this->resources = [];
    }

    /** Manual refresh button */
    public function refresh(): void
    {
        $this->resources = [];
    }

    public function ping(int $id): void
    {
        // stub for now
        $this->dispatch('notify', type: 'info', message: "Ping router #{$id} (wire up later)");
    }

    public function edit(int $id): void
    {
        $this->dispatch('notify', type: 'info', message: "Edit router #{$id}");
    }

    public function delete(int $id): void
    {
        $this->dispatch('notify', type: 'warning', message: "Delete router #{$id}");
    }

    /** Load MikroTik resource for the current page routers (cached) */
    protected function loadResources(LengthAwarePaginator $routers): void
    {
        /** @var MikrotikService $mt */
        $mt = app(MikrotikService::class);

        foreach ($routers as $router) {
            /** @var Router $router */
            if (! array_key_exists($router->id, $this->resources)) {
                try {
                    // Returns first row from /system/resource/print
                    $row = $mt->getRouterResource($router) ?? [];
                } catch (\Throwable $e) {
                    $row = ['error' => $e->getMessage()];
                }
                $this->resources[$router->id] = $row;
            }
        }
    }

    /** DB query with search and pagination */
    protected function paginatedRouters(): LengthAwarePaginator
    {
        return auth()->user()
            ->routers()
            ->when($this->q !== '', function ($q) {
                $term = '%' . strtolower($this->q) . '%';
                $q->where(function ($q) use ($term) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(address) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(note) LIKE ?', [$term]);
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function render(): View
    {
        $routers = $this->paginatedRouters();
        $this->loadResources($routers);

        return view('livewire.router.index', [
            'routers'   => $routers,
            'resources' => $this->resources,
        ]);
    }
}
