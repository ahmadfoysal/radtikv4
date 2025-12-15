<?php

namespace App\Livewire\User;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class Create extends Component
{
    use AuthorizesRequests, Toast;

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

    public function mount(): void
    {
        // Authorization check - only superadmin and admin can create users
        $this->authorize('create', \App\Models\User::class);
    }

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

            // Set admin_id when admin creates a reseller
            if (auth()->user()->hasRole('admin')) {
                $userData['admin_id'] = auth()->id();
            }

            $user = \App\Models\User::create($userData);

            // Decide which role to give the new user
            $roleToAssign = null;

            if (auth()->user()->hasRole('superadmin')) {
                $roleToAssign = 'admin';
            } elseif (auth()->user()->hasRole('admin')) {
                $roleToAssign = 'reseller';
            }

            // Optional: block creation if the current user isn't allowed to assign any role
            if (is_null($roleToAssign)) {
                abort(403, 'You are not allowed to assign a role to this user.');
            }

            // Assign the role (or use syncRoles([$roleToAssign]) if you want to replace any existing roles)
            $user->assignRole($roleToAssign);
        });

        $this->success(
            title: 'Success!',
            description: 'User created successfully.'
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
