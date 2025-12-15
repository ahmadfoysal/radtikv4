<?php

namespace App\Livewire\Components;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mary\Traits\Toast;

class ImpersonationBanner extends Component
{
    use Toast;

    public function stopImpersonation(): void
    {
        $originalUserId = session('impersonator_id');

        if (!$originalUserId) {
            return;
        }

        $originalUser = \App\Models\User::find($originalUserId);

        if (!$originalUser) {
            session()->forget('impersonator_id');
            return;
        }

        // Clear impersonation session
        session()->forget('impersonator_id');

        // Login back as original user
        Auth::login($originalUser);

        $this->success(
            title: 'Impersonation Stopped',
            description: 'You have returned to your original account.'
        );

        $this->redirect(route('users.index'));
    }

    public function render()
    {
        return view('livewire.components.impersonation-banner');
    }
}
