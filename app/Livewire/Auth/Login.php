<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Rule;

class Login extends Component
{
    #[Rule(['required', 'email', 'max:191'])]
    public string $email = '';

    #[Rule(['required', 'string', 'max:191'])]
    public string $password = '';

    public bool $remember = false;

    public function updated($property): void
    {
        $this->validateOnly($property);
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('components.layouts.auth.mary', [
                'title'     => __('Log in'),
                'header'    => __('Log in to your account'),
                'subheader' => __('Enter your email and password below to log in'),
            ]);
    }
}
