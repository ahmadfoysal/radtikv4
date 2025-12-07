<?php

namespace App\Livewire\Package;

use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Index extends Component
{
    use Toast, WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    /** Common filtered query */
    protected function filteredQuery(): Builder
    {
        return Package::query()->when($this->search !== '', function (Builder $q) {
            $s = '%'.trim($this->search).'%';
            $q->where(fn ($qq) => $qq->where('name', 'like', $s)
                ->orWhere('description', 'like', $s)
                ->orWhere('billing_cycle', 'like', $s));
        });
    }

    /** Search change => back to page 1 */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** Sort columns */
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

    /** Sort icon helper for Blade */
    public function getSortIcon(string $field): string
    {
        if ($this->sortField !== $field) {
            return 'o-arrows-up-down';
        }

        return $this->sortDirection === 'asc' ? 'o-arrow-up' : 'o-arrow-down';
    }

    /** Delete package and fix pagination if needed */
    public function delete(int $id): void
    {
        $package = Package::find($id);

        if (! $package) {
            $this->error('Package not found.');

            return;
        }

        $package->delete();

        // Calculate last page after deletion
        $total = $this->filteredQuery()->count();
        $lastPage = max(1, (int) ceil($total / $this->perPage));

        $current = (int) request()->query('page', 1);

        if ($current > $lastPage) {
            $this->gotoPage($lastPage);
        }

        $this->success(
            title: 'Deleted!',
            description: 'Package deleted successfully.'
        );
    }

    public function render()
    {
        $packages = $this->filteredQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.package.index', ['packages' => $packages]);
    }
}
