<?php

namespace App\Livewire\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

use Mary\Traits\Toast;

class Index extends Component
{
    use WithPagination, Toast;

    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    protected $queryString = [
        'search'        => ['except' => ''],
        'perPage'       => ['except' => 10],
        'sortField'     => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        // Livewire নিজেই page হ্যান্ডেল করে; আলাদা করে লাগবে না
    ];

    /** Common filtered query */
    protected function filteredQuery(): Builder
    {
        return User::query()->when($this->search !== '', function (Builder $q) {
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
            session()->flash('error', 'User not found.');
            return;
        }

        if (auth()->id() === $user->id) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $user->delete();

        // নতুন total থেকে lastPage বের করি (search ফিল্টারসহ)
        $total    = $this->filteredQuery()->count();
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

    public function render()
    {
        $users = $this->filteredQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.user.index', ['users' => $users]);
    }
}
