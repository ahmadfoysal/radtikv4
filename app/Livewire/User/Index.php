<?php

namespace App\Livewire\User;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    protected $queryString = [
        'search'        => ['except' => ''],
        'perPage'       => ['except' => 10],
        'sortField'     => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    // Reset to first page when search text changes
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function getSortIcon(string $field): string
    {
        if ($this->sortField !== $field) {
            return 'o-arrows-up-down'; // neutral
        }
        return $this->sortDirection === 'asc' ? 'o-arrow-up' : 'o-arrow-down';
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($qq) {
                    $s = '%' . trim($this->search) . '%';
                    $qq->where('name', 'like', $s)
                        ->orWhere('email', 'like', $s)
                        ->orWhere('phone', 'like', $s)
                        ->orWhere('address', 'like', $s);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.user.index', [
            'users' => $users,
        ]);
    }
}
