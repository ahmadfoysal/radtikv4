<?php

namespace App\Livewire\Radius\Profile;

use App\Models\RadiusProfile;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class Edit extends Component
{
    use Toast;

    public RadiusProfile $profile;

    // Form fields
    #[Rule(['required', 'string', 'max:100'])]
    public string $name = '';

    #[Rule([
        'nullable',
        'string',
        'max:50',
        'regex:/^\s*\d+(?:\.\d+)?[kKmMgG]?(?:\/\d+(?:\.\d+)?[kKmMgG]?)?\s*$/',
    ])]
    public ?string $rate_limit = null;

    #[Rule([
        'nullable',
        'string',
        'max:50',
        'regex:/^(?:(\d+d)?(\d+h)?(\d+m)?(\d+s)?)$/i',
    ])]
    public ?string $validity = null;

    #[Rule(['boolean'])]
    public bool $mac_binding = false;

    #[Rule(['nullable', 'string', 'max:255'])]
    public ?string $description = null;

    public function mount(RadiusProfile $profile): void
    {
        $this->profile = $profile;
        $this->name = $profile->name;
        $this->rate_limit = $profile->rate_limit;
        $this->validity = $profile->validity;
        $this->mac_binding = (bool) $profile->mac_binding;
        $this->description = $profile->description;
    }

    public function save(): void
    {
        // Validate all attributes you defined above
        $this->validate();

        // Update profile
        $this->profile->update([
            'name' => $this->name,
            'rate_limit' => $this->rate_limit ?: null,
            'validity' => $this->validity ?: null,
            'mac_binding' => $this->mac_binding,
            'description' => $this->description ?: null,
        ]);

        $this->success(
            title: 'Updated',
            description: 'RADIUS profile updated successfully.'
        );

        $this->redirect(route('radius.profiles'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('radius.profiles'), navigate: true);
    }

    public function render()
    {
        return view('livewire.radius.profile.edit');
    }
}
