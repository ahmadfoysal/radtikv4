<?php

namespace App\Livewire\ActivityLog;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $filter = 'all';
    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = ActivityLog::with('user')
            ->orderBy('created_at', 'desc');

        // Scope logs based on user role
        if ($user->isAdmin()) {
            // Admin can only see their own logs and their resellers' logs
            $resellerIds = $user->reseller()->pluck('id')->toArray();
            $allowedUserIds = array_merge([$user->id], $resellerIds);

            $query->whereIn('user_id', $allowedUserIds);
        }
        // Super admin can see all logs (no restriction)

        // Filter by action type
        if ($this->filter !== 'all') {
            $query->where('action', $this->filter);
        }

        // Search in description or user name
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        $logs = $query->paginate(20);

        return view('livewire.activity-log.index', [
            'logs' => $logs,
        ])->title('Activity Log');
    }
}
