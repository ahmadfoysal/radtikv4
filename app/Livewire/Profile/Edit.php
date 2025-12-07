<?php

namespace App\Livewire\Profile;

use App\Models\UserProfile;
use Illuminate\Validation\Rule as VRule;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class Edit extends Component
{
    use Toast;

    public UserProfile $profile;

    #[Rule([
        'required',
        'string',
        'max:100',
        'regex:/^[A-Za-z0-9\-_]+$/',
    ])]
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

    public ?int $shared_users = null;

    #[Rule(['boolean'])]
    public bool $mac_binding = false;

    #[Rule(['required', 'numeric', 'min:0'])]
    public float $price = 0.00;

    #[Rule(['nullable', 'string', 'max:255'])]
    public ?string $description = null;

    public function mount(UserProfile $profile): void
    {
        $this->profile = $profile;
        $this->name = $profile->name;
        $this->rate_limit = $profile->rate_limit;
        $this->validity = $profile->validity;
        $this->mac_binding = (bool) $profile->mac_binding;
        $this->price = (float) $profile->price;
        $this->description = $profile->description;
        $this->shared_users = $profile->shared_users;
    }

    public function save(): void
    {

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                VRule::unique('user_profiles', 'name')
                    ->where('user_id', auth()->id())
                    ->ignore($this->profile->id),
            ],
        ]);

        $this->profile->update([
            'name' => $this->name,
            'rate_limit' => $this->rate_limit ?: null,
            'validity' => $this->validity ? strtolower($this->validity) : null,
            'shared_users' => $this->shared_users,
            'mac_binding' => $this->mac_binding,
            'price' => $this->price !== null ? (float) $this->price : 0,
            'description' => $this->description ?: null,
        ]);

        $this->success(
            title: 'Updated',
            description: 'User profile updated successfully.'
        );

        $this->redirect(route('profiles'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('profiles'), navigate: true);
    }

    public function render()
    {
        return view('livewire.profile.edit');
    }
}
