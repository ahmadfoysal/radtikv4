<?php

namespace App\Livewire\Admin;

use App\Models\ResellerProfile;
use App\Models\UserProfile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Mary\Traits\Toast;

class AssignResellerProfiles extends Component
{
    use Toast;

    public ?int $resellerId = null;

    public array $selectedProfileIds = [];

    /**
     * Dropdown options for resellers
     *
     * @var array<int, array{id:int,name:string,email?:string}>
     */
    public array $resellerOptions = [];

    /**
     * Profile dropdown options
     *
     * @var array<int, array{id:int,name:string,rate_limit?:string,owner?:string}>
     */
    public array $profileOptions = [];

    /**
     * Assigned profile data for the summary list
     *
     * @var array<int, array{id:int,name:string,rate_limit:string|null,assigned_at:string|null,assigned_by:string|null}>
     */
    public array $assignedProfiles = [];

    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user && ($user->isAdmin() || $user->isSuperAdmin()), 403);

        $this->loadResellerOptions();
        $this->loadProfileOptions();
    }

    public function render(): View
    {
        return view('livewire.admin.assign-reseller-profiles')
            ->title(__('Assign Profiles to Resellers'));
    }

    public function updatedResellerId($value): void
    {
        if (! $value) {
            $this->selectedProfileIds = [];
            $this->assignedProfiles = [];

            return;
        }

        $this->loadAssignments();
        $this->ensureSelectedResellerIncluded();
    }

    public function saveAssignments(): void
    {
        $this->validate();

        $allowedProfileIds = $this->availableProfileIds();
        $this->selectedProfileIds = array_values(array_unique(array_map('intval', $this->selectedProfileIds)));

        $selected = array_values(array_intersect($allowedProfileIds, $this->selectedProfileIds));
        $profileIdsToDetach = array_diff($allowedProfileIds, $selected);

        DB::transaction(function () use ($selected, $profileIdsToDetach) {
            if ($profileIdsToDetach) {
                ResellerProfile::query()
                    ->where('reseller_id', $this->resellerId)
                    ->whereIn('profile_id', $profileIdsToDetach)
                    ->delete();

                // Log profile unassignment
                \App\Services\ActivityLogger::logCustom(
                    'profiles_unassigned',
                    null,
                    "Unassigned " . count($profileIdsToDetach) . " profile(s) from reseller",
                    [
                        'reseller_id' => $this->resellerId,
                        'profile_ids' => $profileIdsToDetach,
                        'count' => count($profileIdsToDetach),
                    ]
                );
            }

            foreach ($selected as $profileId) {
                ResellerProfile::updateOrCreate(
                    [
                        'reseller_id' => $this->resellerId,
                        'profile_id' => $profileId,
                    ],
                    [
                        'assigned_by' => Auth::id(),
                    ]
                );
            }

            // Log profile assignment
            if (count($selected) > 0) {
                \App\Services\ActivityLogger::logCustom(
                    'profiles_assigned',
                    null,
                    "Assigned " . count($selected) . " profile(s) to reseller",
                    [
                        'reseller_id' => $this->resellerId,
                        'profile_ids' => $selected,
                        'count' => count($selected),
                    ]
                );
            }
        });

        $this->loadAssignments();
        $this->success(__('Profiles for the selected reseller were updated.'));
    }

    public function updatedSelectedProfileIds($value): void
    {
        $this->selectedProfileIds = collect($value ?? [])
            ->filter(fn($id) => filled($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    public function searchResellers(string $value = ''): void
    {
        $this->loadResellerOptions($value);
    }

    public function searchProfiles(string $value = ''): void
    {
        $this->loadProfileOptions($value);
    }

    protected function loadResellerOptions(?string $term = null): void
    {
        $this->resellerOptions = $this->resellerQuery($term)
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn(User $reseller) => [
                'id' => $reseller->id,
                'name' => $reseller->name,
                'email' => $reseller->email,
            ])
            ->toArray();

        $this->ensureSelectedResellerIncluded();
    }

    protected function loadProfileOptions(?string $term = null): void
    {
        $this->profileOptions = $this->profileQuery($term)
            ->orderBy('name')
            ->limit(25)
            ->get()
            ->map(fn(UserProfile $profile) => [
                'id' => $profile->id,
                'name' => $profile->name,
                'rate_limit' => $profile->rate_limit,
                'owner' => $profile->user?->name,
                'detail' => collect([$profile->rate_limit, $profile->user?->name])
                    ->filter()
                    ->implode(' | '),
            ])
            ->toArray();

        $this->ensureSelectedProfilesIncluded();
    }

    protected function loadAssignments(): void
    {
        $allowedProfileIds = $this->availableProfileIds();

        $assignments = ResellerProfile::query()
            ->where('reseller_id', $this->resellerId)
            ->whereIn('profile_id', $allowedProfileIds)
            ->with(['profile:id,name,rate_limit', 'assignedBy:id,name'])
            ->get();

        $this->selectedProfileIds = $assignments->pluck('profile_id')->map(fn($id) => (int) $id)->toArray();

        $this->assignedProfiles = $assignments->map(fn(ResellerProfile $assignment) => [
            'id' => $assignment->profile_id,
            'name' => $assignment->profile?->name ?? __('Unknown Profile'),
            'rate_limit' => $assignment->profile?->rate_limit,
            'assigned_at' => optional($assignment->created_at)?->toDayDateTimeString(),
            'assigned_by' => $assignment->assignedBy?->name,
        ])->toArray();

        $this->ensureSelectedProfilesIncluded();
    }

    protected function resellerQuery(?string $term = null): Builder
    {
        $query = User::query()
            ->role('reseller')
            ->select('id', 'name', 'email', 'admin_id');

        $user = Auth::user();

        if ($user && ! $user->isSuperAdmin()) {
            $query->where('admin_id', $user->id);
        }

        if ($term !== null && trim($term) !== '') {
            $value = '%' . trim($term) . '%';
            $query->where(function (Builder $builder) use ($value) {
                $builder->where('name', 'like', $value)
                    ->orWhere('email', 'like', $value);
            });
        }

        return $query;
    }

    protected function profileQuery(?string $term = null): Builder
    {
        $query = UserProfile::query()
            ->select('id', 'name', 'rate_limit', 'user_id')
            ->with('user:id,name');

        $user = Auth::user();

        if ($user && ! $user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($term !== null && trim($term) !== '') {
            $value = '%' . trim($term) . '%';
            $query->where(function (Builder $builder) use ($value) {
                $builder->where('name', 'like', $value)
                    ->orWhere('rate_limit', 'like', $value);
            });
        }

        return $query;
    }

    protected function ensureSelectedResellerIncluded(): void
    {
        if (! $this->resellerId) {
            return;
        }

        $exists = collect($this->resellerOptions)
            ->contains(fn($option) => (int) $option['id'] === (int) $this->resellerId);

        if (! $exists) {
            $reseller = $this->resellerQuery(null)
                ->where('id', $this->resellerId)
                ->first();

            if ($reseller) {
                $this->resellerOptions[] = [
                    'id' => $reseller->id,
                    'name' => $reseller->name,
                    'email' => $reseller->email,
                ];
            }
        }
    }

    protected function ensureSelectedProfilesIncluded(): void
    {
        if (empty($this->selectedProfileIds)) {
            return;
        }

        $existingIds = collect($this->profileOptions)->pluck('id')->map(fn($id) => (int) $id);
        $missingIds = collect($this->selectedProfileIds)
            ->map(fn($id) => (int) $id)
            ->diff($existingIds)
            ->values();

        if ($missingIds->isEmpty()) {
            return;
        }

        $additionalProfiles = $this->profileQuery(null)
            ->whereIn('id', $missingIds->all())
            ->get()
            ->map(fn(UserProfile $profile) => [
                'id' => $profile->id,
                'name' => $profile->name,
                'rate_limit' => $profile->rate_limit,
                'owner' => $profile->user?->name,
                'detail' => collect([$profile->rate_limit, $profile->user?->name])
                    ->filter()
                    ->implode(' | '),
            ])
            ->toArray();

        $this->profileOptions = array_merge($this->profileOptions, $additionalProfiles);
    }

    protected function availableProfileIds(): array
    {
        return $this->profileQuery(null)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    }

    protected function rules(): array
    {
        return [
            'resellerId' => ['required', 'integer', 'exists:users,id'],
            'selectedProfileIds' => ['array'],
            'selectedProfileIds.*' => ['integer'],
        ];
    }
}
