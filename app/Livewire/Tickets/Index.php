<?php

namespace App\Livewire\Tickets;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Rule as V;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Index extends Component
{
    use Toast, WithPagination;

    // Filters
    public string $statusFilter = 'all';

    public int $perPage = 10;

    // Create form
    public bool $showCreateModal = false;

    #[V(['required', 'string', 'max:255'])]
    public string $subject = '';

    #[V(['required', 'string'])]
    public string $description = '';

    #[V(['nullable', 'string', 'in:low,normal,high'])]
    public ?string $priority = 'normal';

    #[V(['nullable', 'exists:users,id'])]
    public ?int $owner_id = null;

    #[V(['nullable', 'exists:users,id'])]
    public ?int $assigned_to = null;

    protected $queryString = [
        'statusFilter' => ['except' => 'all'],
        'perPage' => ['except' => 10],
    ];

    public function mount()
    {
        // Set default owner to current user if not superadmin
        if (! auth()->user()->isSuperAdmin()) {
            $this->owner_id = auth()->id();
        }
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    protected function filteredQuery(): Builder
    {
        $user = auth()->user();

        return Ticket::query()
            ->with(['creator', 'owner', 'assignee'])
            ->when($user->isSuperAdmin(), function ($q) {
                // Superadmin sees all tickets
                return $q;
            }, function ($q) use ($user) {
                // Admin/reseller sees only their tickets
                return $q->where(function ($query) use ($user) {
                    $query->where('owner_id', $user->id)
                        ->orWhere('created_by', $user->id);
                });
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                return $q->where('status', $this->statusFilter);
            });
    }

    public function openCreateModal()
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function create()
    {
        $this->validate();

        $user = auth()->user();

        // If not superadmin, set owner to current user
        if (! $user->isSuperAdmin()) {
            $this->owner_id = $user->id();
        }

        // If owner_id is still null (shouldn't happen), default to current user
        if (! $this->owner_id) {
            $this->owner_id = $user->id();
        }

        Ticket::create([
            'subject' => $this->subject,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => 'open',
            'created_by' => $user->id(),
            'owner_id' => $this->owner_id,
            'assigned_to' => $this->assigned_to,
        ]);

        $this->success('Ticket created successfully.');
        $this->closeCreateModal();
        $this->resetPage();
    }

    protected function resetForm()
    {
        $this->subject = '';
        $this->description = '';
        $this->priority = 'normal';
        $this->owner_id = auth()->user()->isSuperAdmin() ? null : auth()->id();
        $this->assigned_to = null;
        $this->resetValidation();
    }

    public function render()
    {
        $tickets = $this->filteredQuery()
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        // Get users for dropdowns (only for superadmin)
        $users = [];
        if (auth()->user()->isSuperAdmin()) {
            $users = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'reseller']);
            })->orderBy('name')->get(['id', 'name']);
        }

        return view('livewire.tickets.index', [
            'tickets' => $tickets,
            'users' => $users,
        ]);
    }
}
