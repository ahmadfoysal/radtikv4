<?php

namespace App\Livewire\Radius\Profile;


use Livewire\Component;
use Livewire\Attributes\Rule;
use Mary\Traits\Toast;

class Create extends Component
{
    use Toast;

    //validate
    #[Rule(['required', 'string', 'max:100'])]
    public string $name = '';
    #[Rule([
        'nullable',
        'string',
        'max:50',
        // allow:  "128k"  or  "64k/128M"
        'regex:/^\s*\d+(?:\.\d+)?[kKmMgG]?(?:\/\d+(?:\.\d+)?[kKmMgG]?)?\s*$/'
    ])]
    public ?string $rate_limit = null;
    #[Rule([
        'nullable',
        'string',
        'max:50',
        'regex:/^(?:(\d+d)?(\d+h)?(\d+m)?(\d+s)?)$/i'
    ])]
    public ?string $validity = null;
    #[Rule(['boolean'])]
    public bool $mac_binding = false;
    #[Rule(['nullable', 'string', 'max:255'])]
    public ?string $description = null;

    public function save()
    {
        $this->validate();

        // Create the profile
        \App\Models\RadiusProfile::create([
            'name' => $this->name,
            'rate_limit' => $this->rate_limit,
            'validity' => $this->validity,
            'mac_binding' => $this->mac_binding,
            'description' => $this->description,
            'user_id' => auth()->id(),
        ]);

        // Reset form fields
        $this->reset(['name', 'rate_limit', 'validity', 'mac_binding', 'description']);

        // Optional: toast/notify
        $this->success(title: 'Success', description: 'Radius profile created successfully.');

        $this->redirect(route('radius.profiles'));
    }


    public function cancel()
    {
        $this->redirect(route('radius.profiles'));
    }




    public function render()
    {
        return view('livewire.radius.profile.create');
    }
}
