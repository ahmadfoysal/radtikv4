<?php

namespace App\Livewire\Router;

use Livewire\Component;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\Rule;
use Mary\Traits\Toast;


class Create extends Component
{

    use Toast;

    #[Rule(['required', 'string', 'max:100'])]
    public string $name = '';

    #[Rule(['required', 'string', 'max:191', 'regex:/^(([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}|(\d{1,3}\.){3}\d{1,3})$/'])] // IP or hostname
    public string $address = '';

    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $port = 8728;

    #[Rule(['required', 'string', 'max:100'])]
    public string $username = '';

    #[Rule(['required', 'string', 'max:191'])]
    public string $password = '';

    #[Rule(['nullable', 'string', 'max:500'])]
    public string $note = '';

    public function save(): void
    {
        $this->validate();

        Router::create([
            'name'     => $this->name,
            'address'  => $this->address,
            'port'     => $this->port,
            'username' => $this->username,
            'password' => Crypt::encryptString($this->password),
            'note'     => $this->note,
            'app_key'  => Crypt::encryptString(bin2hex(random_bytes(16))),
            'user_id'  => Auth::id(),
        ]);

        // Reset form (keep port default)
        $this->reset(['name', 'address', 'username', 'password', 'note']);
        $this->port = 8728;

        // Optional: toast/notify
        $this->success(title: 'Success', description: 'Router added successfully.');
    }


    public function cancel()
    {
        $this->redirect(route('routers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.router.create')
            ->title(__('Add Router'));
    }
}
