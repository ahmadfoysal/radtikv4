<?php

namespace App\Livewire\Voucher;

use App\Models\UserProfile;
use App\Models\Voucher;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule as V;
use Livewire\Component;
use Mary\Traits\Toast;

class Edit extends Component
{
    use Toast;

    public Voucher $voucher;

    #[V(['required', 'string', 'min:3', 'max:64'])]
    public string $username = '';

    #[V(['required', 'string', 'min:3', 'max:64'])]
    public string $password = '';

    #[V(['nullable', 'integer', 'exists:user_profiles,id'])]
    public ?int $user_profile_id = null;

    public array $available_profiles = [];

    public function mount(Voucher $voucher): void
    {
        // Check if user has permission to edit this voucher
        if ($voucher->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'You do not have permission to edit this voucher.');
        }

        $this->voucher = $voucher;
        $this->username = $voucher->username;
        $this->password = $voucher->password;
        $this->user_profile_id = $voucher->user_profile_id;

        // Load available profiles from database
        $this->loadProfiles();
    }

    protected function loadProfiles(): void
    {
        $profiles = Auth::user()->profiles()->orderBy('name')->get();

        $this->available_profiles = $profiles->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name . ($p->rate_limit ? ' (' . $p->rate_limit . ')' : ''),
        ])->toArray();
    }

    public function update(): void
    {
        $this->validate();

        try {
            // Update voucher in database
            $this->voucher->update([
                'username' => $this->username,
                'password' => $this->password,
                'user_profile_id' => $this->user_profile_id,
            ]);

            $this->success('Voucher updated successfully.');

            // Redirect back to vouchers list
            $this->redirect(route('vouchers.index'), navigate: true);
        } catch (\Throwable $e) {
            $this->error('Failed to update voucher: ' . $e->getMessage());
        }
    }

    public function cancel(): void
    {
        $this->redirect(route('vouchers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.voucher.edit');
    }
}
