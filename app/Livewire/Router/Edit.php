<?php

namespace App\Livewire\Router;

use Livewire\Component;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\Rule;
use Mary\Traits\Toast;

class Edit extends Component
{
    use Toast;

    public Router $router;

    #[Rule(['required', 'string', 'max:100'])]
    public string $name = '';

    #[Rule(['required', 'string', 'max:191', 'regex:/^(([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}|(\d{1,3}\.){3}\d{1,3})$/'])]
    public string $address = '';

    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $port = 8728;

    #[Rule(['required', 'string', 'max:100'])]
    public string $username = '';

    #[Rule(['required', 'string', 'max:191'])]
    public string $password = '';

    #[Rule(['nullable', 'string', 'max:500'])]
    public string $note = '';

    public function mount(Router $router): void
    {
        if ($router->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $this->router = $router;

        $this->name     = $router->name;
        $this->address  = $router->address;
        $this->port     = $router->port;
        $this->username = $router->username;
        $this->password = Crypt::decryptString($router->password);
        $this->note     = $router->note ?? '';
    }

    public function update(): void
    {
        $this->validate();

        $this->router->update([
            'name'     => $this->name,
            'address'  => $this->address,
            'port'     => $this->port,
            'username' => $this->username,
            'password' => Crypt::encryptString($this->password),
            'note'     => $this->note,
        ]);

        $this->success(title: 'Success', description: 'Router updated successfully.');

        $this->redirect(route('routers.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('routers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.router.edit')
            ->title(__('Edit Router'));
    }
}
