<?php

namespace App\Livewire\Router;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;

class Index extends Component
{
    use WithPagination;

    public string $q = '';
    public int $perPage = 12;

    protected $queryString = [
        'q'    => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    protected function paginatedRouters(): LengthAwarePaginator
    {
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
        // ভবিষ্যতে এখানে আসল পিং/হেলথচেক বসাবে
        $this->dispatch('notify', type: 'info', message: "Ping router #{$id}");
    }

    public function delete(int $id): void
    {
        $router = auth()->user()->routers()->findOrFail($id);
        $router->delete();

        if ($this->page > 1 && $this->paginatedRouters()->isEmpty()) {
            $this->previousPage();
        }

        $this->dispatch('notify', type: 'success', message: 'Router deleted.');
    }

    public function render(): View
    {
        return view('livewire.router.index', [
            'routers' => $this->paginatedRouters(),
        ]);
    }
}
