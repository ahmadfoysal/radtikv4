<?php

namespace App\Livewire\Radius\Profile;

use App\Models\RadiusProfile;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Index extends Component
{
    use Toast, WithPagination;

    public string $q = '';        // search query

    protected $queryString = ['q']; // keep search in URL

    public int $perPage = 12;     // grid pagination

    protected $listeners = [
        'profileCreated' => '$refresh',
        'profileUpdated' => '$refresh',
    ];

    // Reset page when search changes
    public function updatedQ()
    {
        $this->resetPage();
    }

    // Delete profile
    public function delete($id)
    {
        $profile = RadiusProfile::find($id);

        if (! $profile) {
            return $this->error(
                title: 'Not Found',
                description: 'Profile not found.'
            );
        }

        $profile->delete();

        $this->success(
            title: 'Deleted',
            description: 'Profile removed successfully.'
        );

        $this->resetPage();
    }

    // Fetch profiles with search + pagination
    public function getProfilesProperty()
    {
        return RadiusProfile::query()
            ->when($this->q, function ($query) {
                $query->where('name', 'like', "%{$this->q}%")
                    ->orWhere('rate_limit', 'like', "%{$this->q}%")
                    ->orWhere('validity', 'like', "%{$this->q}%");
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.radius.profile.index', [
            'profiles' => $this->profiles,
        ]);
    }
}
