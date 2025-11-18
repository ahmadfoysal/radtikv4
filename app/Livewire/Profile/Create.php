<?php

namespace App\Livewire\Profile;

use App\Models\UserProfile;
use Illuminate\Validation\Rule as VRule;
use Livewire\Component;
use Livewire\Attributes\Rule;
use Mary\Traits\Toast;

class Create extends Component
{
    use Toast;

    #[Rule([
        'required',
        'string',
        'max:100',
        'regex:/^[A-Za-z0-9\-_]+$/'
    ])]
    public string $name = '';


    #[Rule([
        'nullable',
        'string',
        'max:50',
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

    #[Rule(['required', 'numeric', 'min:0'])]
    public $price = 0.00;

    #[Rule(['nullable', 'string', 'max:255'])]
    public ?string $description = null;

    public function save()
    {
        // 1) সব attribute-based রুল রান করো
        $this->validate();

        // 2) name + user_id কম্বিনেশন ইউনিক কিনা চেক করো
        $this->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                VRule::unique('user_profiles', 'name')
                    ->where('user_id', auth()->id()),
            ],
        ]);

        UserProfile::create([
            'name'        => $this->name,
            'rate_limit'  => $this->rate_limit,
            'validity'    => $this->validity ? strtolower($this->validity) : null,
            'mac_binding' => $this->mac_binding,
            'price'       => $this->price,
            'description' => $this->description,
            'user_id'     => auth()->id(),
        ]);

        $this->reset(['name', 'rate_limit', 'validity', 'mac_binding', 'price', 'description']);

        $this->success(title: 'Success', description: 'User profile created successfully.');

        $this->redirect(route('profiles'));
    }

    public function cancel()
    {
        $this->redirect(route('profiles'));
    }

    public function render()
    {
        return view('livewire.profile.create');
    }
}
