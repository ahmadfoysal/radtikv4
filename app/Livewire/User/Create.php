<?php

namespace App\Livewire\User;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class Create extends Component
{
    use Toast;

    #[Validate('required|string|max:255')]
    public $name;

    #[Validate('required|email|max:255|unique:users,email')]
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

        DB::transaction(function () {
            $userData = [
                'name' => $this->name,
                'email' => $this->email,
                'password' => bcrypt($this->password),
                'phone' => $this->phone,
                'address' => $this->address,
                'country' => $this->country,
            ];

            // Determine role based on creator
            $currentUser = auth()->user();
            $roleToAssign = null;

            if ($currentUser->hasRole('superadmin')) {
                // Superadmin can only create another superadmin
                $roleToAssign = 'superadmin';
            } elseif ($currentUser->hasRole('admin')) {
                // Admin can only create reseller
                $roleToAssign = 'reseller';
                // Set admin_id to link reseller to admin
                $userData['admin_id'] = $currentUser->id;
            } else {
                // Resellers cannot create users
                abort(403, 'You are not authorized to create users.');
            }

            $user = \App\Models\User::create($userData);

            // Assign the determined role
            $user->assignRole($roleToAssign);
        });

        $roleCreated = auth()->user()->hasRole('superadmin') ? 'Superadmin' : 'Reseller';

        $this->success(
            title: 'Success!',
            description: "{$roleCreated} user created successfully."
        );

        $this->redirect(route('users.index'), navigate: true);
    }

    public function cancel()
    {
        // redirect to user list with a flash message

        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.user.create');
    }
}
