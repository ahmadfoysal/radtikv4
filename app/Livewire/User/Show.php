<?php

namespace App\Livewire\User;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use App\Models\Router;

class Show extends Component
{
    use AuthorizesRequests;

    public User $user;

    public function mount(User $user)
    {
        $this->authorize('view', $user);
        $this->user = $user;
    }

    public function render()
    {
        return view('livewire.user.show', [
            'user' => $this->user,
        ]);
    }
}
