<?php

namespace App\Livewire\Knowledgebase;

use App\Models\KnowledgebaseArticle;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $q = '';

    public string $category = 'all';

    public int $perPage = 12;

    protected $queryString = [
        'q' => ['except' => ''],
        'category' => ['except' => 'all'],
        'page' => ['except' => 1],
    ];

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    protected function articles(): LengthAwarePaginator
    {
        return KnowledgebaseArticle::query()
            ->where('is_active', true)
            ->when($this->q !== '', function ($query) {
                $term = '%' . strtolower($this->q) . '%';
                $query->where(function ($q) use ($term) {
                    $q->whereRaw('LOWER(title) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(content) LIKE ?', [$term]);
                });
            })
            ->when($this->category !== 'all', function ($query) {
                $query->where('category', $this->category);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    protected function categories(): array
    {
        return KnowledgebaseArticle::query()
            ->where('is_active', true)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.knowledgebase.index', [
            'articles' => $this->articles(),
            'categories' => $this->categories(),
        ]);
    }
}
