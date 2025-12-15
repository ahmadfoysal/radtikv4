<?php

namespace App\Livewire\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Edit extends Component
{
    public User $user;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255|unique:users,email,:user->id')]
    public string $email = '';

    #[Validate('nullable|string|min:8|max:255')]
    public ?string $password = null;

    #[Validate('nullable|string|max:20')]
    public ?string $phone = null;

    #[Validate('nullable|string|max:255')]
    public ?string $address = null;

    public float $commission = 0;

    public function mount(User $user): void
    {
        $this->user = $user->load('roles'); // Load roles for blade conditions
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->address = $user->address;
        $this->commission = (float) $user->commission;
    }

    public function render()
    {
        return view('livewire.user.edit');
    }

    public function update()
    {
        // Validate basic fields
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->user->id,
            'password' => 'nullable|string|min:8|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ];

        // Add commission validation only for superadmin editing admin
        if (auth()->user()->hasRole('superadmin') && $this->user->hasRole('admin')) {
            $rules['commission'] = 'required|numeric|min:0|max:100';
        }

        $validated = $this->validate($rules);

        $this->user->name = $validated['name'];
        $this->user->email = $validated['email'];
        $this->user->phone = $validated['phone'];
        $this->user->address = $validated['address'];

        // Only update commission for admin users when edited by superadmin
        if (auth()->user()->hasRole('superadmin') && $this->user->hasRole('admin')) {
            $this->user->commission = $validated['commission'];
        }

        if (! empty($validated['password'])) {
            $this->user->password = Hash::make($validated['password']);
        }

        $this->user->save();

        session()->flash('success', 'User updated successfully.');

        $this->redirect(route('users.index'), navigate: true);
    }

    public function delete(): void
    {
        $this->user->delete();

        session()->flash('success', 'User deleted successfully.');

        $this->redirectRoute('users.index', navigate: true);
    }

    public function cancel()
    {
        $this->redirect(route('users.index'), navigate: true);
    }
}
