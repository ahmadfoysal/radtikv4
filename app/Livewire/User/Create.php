<?php

namespace App\Livewire\User;

use Livewire\Attributes\Validate;
use Livewire\Component;

class Create extends Component
{
    #[Validate('required|string|max:255')]
    public $name;
    #[Validate('required|email|max:255')]
    public $email;
    #[Validate('required|string|min:8|max:255')]
    public $password;
    #[Validate('nullable|string|max:20')]
    public $phone;
    #[Validate('nullable|string|max:255')]
    public $address;
    #[Validate('nullable|string|max:255')]
    public $country;

    public function save()
    {
        $this->validate();

        \App\Models\User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'phone' => $this->phone,
            'address' => $this->address,
            'country' => $this->country,
        ]);

        session()->flash('message', 'User created successfully.');

        return redirect()->route('users.index');
    }

    public function render()
    {
        return view('livewire.user.create');
    }
}
