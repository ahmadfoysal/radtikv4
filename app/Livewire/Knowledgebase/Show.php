<?php

namespace App\Livewire\Knowledgebase;

use App\Models\KnowledgebaseArticle;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    public KnowledgebaseArticle $article;

    public function mount(string $slug): void
    {
        $this->article = KnowledgebaseArticle::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.knowledgebase.show');
    }
}
