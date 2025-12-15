<?php

namespace App\Livewire\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Index extends Component
{
    use AuthorizesRequests, Toast, WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public function mount(): void
    {
        // Only superadmin and admin can access this page
        $this->authorize('view', User::class);
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        // Livewire নিজেই page হ্যান্ডেল করে; আলাদা করে লাগবে না
    ];

    /** Common filtered query with role-based filtering */
    protected function filteredQuery(): Builder
    {
        $currentUser = Auth::user();

        $query = User::query();

        // Role-based filtering
        if ($currentUser->hasRole('superadmin')) {
            // Superadmin sees all admins
            $query->whereHas('roles', fn($q) => $q->where('name', 'admin'));
        } elseif ($currentUser->hasRole('admin')) {
            // Admin sees their resellers
            $query->whereHas('roles', fn($q) => $q->where('name', 'reseller'))
                ->where('admin_id', $currentUser->id);
        } else {
            // Other roles shouldn't access this, but just in case
            $query->whereRaw('1 = 0'); // Return no results
        }

        // Apply search filters
        return $query->when($this->search !== '', function (Builder $q) {
            $s = '%' . trim($this->search) . '%';
            $q->where(fn($qq) => $qq->where('name', 'like', $s)
                ->orWhere('email', 'like', $s)
                ->orWhere('phone', 'like', $s)
                ->orWhere('address', 'like', $s));
        });
    }

    /** Search change => back to page 1 */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** Sort columns */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    /** Sort icon helper for Blade */
    public function getSortIcon(string $field): string
    {
        if ($this->sortField !== $field) {
            return 'o-arrows-up-down';
        }

        return $this->sortDirection === 'asc' ? 'o-arrow-up' : 'o-arrow-down';
    }

    /** Delete user and fix pagination if needed */
    public function delete(int $id): void
    {
        $user = User::find($id);

        if (! $user) {
            $this->error(
                title: 'Not Found',
                description: 'User not found.'
            );
            return;
        }

        // Authorization check using policy
        try {
            $this->authorize('delete', $user);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->error(
                title: 'Access Denied',
                description: 'You are not authorized to delete this user.'
            );
            return;
        }

        $user->delete();

        // নতুন total থেকে lastPage বের করি (search ফিল্টারসহ)
        $total = $this->filteredQuery()->count();
        $lastPage = max(1, (int) ceil($total / $this->perPage));

        // বর্তমান পেজ—Livewire v3 এ query param থেকেই পাওয়া যায়
        $current = (int) request()->query('page', 1);

        // যদি current > lastPage (মানে শেষ আইটেম ডিলিট হয়েছে), lastPage-এ নাও
        if ($current > $lastPage) {
            $this->gotoPage($lastPage);
        }

        // ✅ Toast alert
        $this->success(
            title: 'Deleted!',
            description: 'User deleted successfully.'
        );
    }

    /** Impersonate user (only for superadmin) */
    public function impersonate(int $userId): void
    {
        $userToImpersonate = User::find($userId);

        if (!$userToImpersonate) {
            $this->error(
                title: 'User Not Found',
                description: 'The user you are trying to impersonate does not exist.'
            );
            return;
        }

        // Authorization check using policy
        try {
            $this->authorize('impersonate', $userToImpersonate);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->error(
                title: 'Access Denied',
                description: 'You do not have permission to impersonate users.'
            );
            return;
        }

        $currentUser = Auth::user();

        // Store the original user ID in session to allow returning
        session(['impersonator_id' => $currentUser->id]);

        // Log in as the target user
        Auth::login($userToImpersonate);

        $this->success(
            title: 'Impersonation Started',
            description: "You are now logged in as {$userToImpersonate->name}."
        );

        // Redirect to dashboard
        $this->redirect(route('dashboard'));
    }

    public function render()
    {
        $users = $this->filteredQuery()
            ->with('roles') // Load roles relationship
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.user.index', ['users' => $users]);
    }
}
