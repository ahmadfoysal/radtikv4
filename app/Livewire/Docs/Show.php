<?php

namespace App\Livewire\Docs;

use App\Models\DocumentationArticle;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    public DocumentationArticle $article;

    public function mount(string $slug): void
    {
        $this->article = DocumentationArticle::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.docs.show');
    }
}
