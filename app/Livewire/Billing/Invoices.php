<?php

namespace App\Livewire\Billing;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Invoices extends Component
{
    use WithPagination;

    public string $search = '';

    public string $type = 'all';

    public int $perPage = 15;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => 'all'],
        'perPage' => ['except' => 15],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function render(): View
    {
        $invoices = $this->invoiceQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.billing.invoices', [
            'invoices' => $invoices,
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
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
            return 'o-arrows-up-down';
        }

        return $this->sortDirection === 'asc' ? 'o-arrow-up' : 'o-arrow-down';
    }

    protected function invoiceQuery(): Builder
    {
        $userId = auth()->id();

        return Invoice::query()
            ->with(['router:id,name'])
            ->where('user_id', $userId)
            ->when($this->type !== 'all', function (Builder $query) {
                $query->where('type', $this->type);
            })
            ->when($this->search !== '', function (Builder $query) {
                $term = '%'.mb_strtolower(trim($this->search)).'%';

                $query->where(function (Builder $sub) use ($term) {
                    $sub->whereRaw('LOWER(category) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(type) LIKE ?', [$term]);
                });
            });
    }
}
