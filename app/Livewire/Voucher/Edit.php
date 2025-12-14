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
        $this->authorize('edit_vouchers');

        // Verify user has access to the voucher's router
        $user = Auth::user();
        try {
            $router = $user->getAuthorizedRouter($voucher->router_id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(403, 'You are not authorized to edit this voucher.');
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
        $user = Auth::user();
        $profiles = $user->getAccessibleProfiles();

        $this->available_profiles = $profiles->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name . ($p->rate_limit ? ' (' . $p->rate_limit . ')' : ''),
        ])->toArray();
    }

    public function update(): void
    {
        $this->authorize('edit_vouchers');
        $this->validate();

        try {
            $user = Auth::user();

            // Verify user still has access to the voucher's router
            try {
                $router = $user->getAuthorizedRouter($this->voucher->router_id);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                $this->error('You are not authorized to edit this voucher.');
                return;
            }

            // Verify the selected profile is accessible
            if ($this->user_profile_id) {
                $accessibleProfiles = $user->getAccessibleProfiles();
                $selectedProfile = $accessibleProfiles->firstWhere('id', $this->user_profile_id);
                if (!$selectedProfile) {
                    $this->error('Selected profile is not accessible or does not exist.');
                    return;
                }
            }

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
