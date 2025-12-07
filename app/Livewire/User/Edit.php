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

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->address = $user->address;
    }

    public function render()
    {
        return view('livewire.user.edit');
    }

    public function update()
    {
        // Update user fields
        $validated = $this->validate();
        $this->user->name = $validated['name'];
        $this->user->email = $validated['email'];
        $this->user->phone = $validated['phone'];
        $this->user->address = $validated['address'];

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
