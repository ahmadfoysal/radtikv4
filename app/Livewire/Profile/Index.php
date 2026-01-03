<?php

namespace App\Livewire\Profile;

use App\Models\UserProfile;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Index extends Component
{
    use Toast, WithPagination;

    public string $q = '';          // search query

    protected $queryString = ['q']; // keep search in URL

    public int $perPage = 12;       // grid pagination

    protected $listeners = [
        'profileCreated' => '$refresh',
        'profileUpdated' => '$refresh',
    ];

    // search পরিবর্তন হলে পেজ রিসেট
    public function updatedQ(): void
    {
        $this->resetPage();
    }

    // Delete profile
    public function delete(int $id): void
    {
        $profile = UserProfile::where('user_id', auth()->id())->find($id);

        if (! $profile) {
            $this->error(
                title: 'Not Found',
                description: 'Profile not found or not yours.'
            );

            return;
        }

        // Check if any vouchers are using this profile
        $voucherCount = $profile->vouchers()->count();

        if ($voucherCount > 0) {
            $this->error(
                title: 'Cannot Delete',
                description: "This profile is being used by {$voucherCount} voucher(s). Please delete or reassign the vouchers first."
            );

            return;
        }

        $profile->delete();

        $this->success(
            title: 'Deleted',
            description: 'Profile removed successfully.'
        );

        $this->resetPage();
    }

    // Computed property: profiles
    public function getProfilesProperty()
    {
        return UserProfile::query()
            ->where('user_id', auth()->id())
            ->withCount('vouchers')
            ->when($this->q, function ($query) {
                $term = '%' . $this->q . '%';

                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('rate_limit', 'like', $term)
                        ->orWhere('validity', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.profile.index', [
            'profiles' => $this->profiles,
        ]);
    }
}
