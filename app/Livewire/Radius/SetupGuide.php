<?php

namespace App\Livewire\Radius;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SetupGuide extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('view_router');
    }

    public function render(): View
    {
        return view('livewire.radius.setup-guide');
    }
}
