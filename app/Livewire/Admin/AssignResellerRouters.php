<?php

namespace App\Livewire\Admin;

use App\Models\ResellerRouter;
use App\Models\Router;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Mary\Traits\Toast;

class AssignResellerRouters extends Component
{
    use Toast;

    public ?int $resellerId = null;

    public array $selectedRouterIds = [];

    /**
     * Dropdown options
     *
     * @var array<int, array{id:int,name:string,email?:string}>
     */
    public array $resellerOptions = [];

    /**
     * Router dropdown options
     *
     * @var array<int, array{id:int,name:string,address?:string,owner?:string}>
     */
    public array $routerOptions = [];

    /**
     * Assigned router data for the summary list
     *
     * @var array<int, array{id:int,name:string,address:string|null,assigned_at:string|null,assigned_by:string|null}>
     */
    public array $assignedRouters = [];

    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user && ($user->isAdmin() || $user->isSuperAdmin()), 403);

        $this->loadResellerOptions();
        $this->loadRouterOptions();
    }

    public function render(): View
    {
        return view('livewire.admin.assign-reseller-routers')
            ->title(__('Assign Routers to Resellers'));
    }

    public function updatedResellerId($value): void
    {
        if (! $value) {
            $this->selectedRouterIds = [];
            $this->assignedRouters = [];

            return;
        }

        $this->loadAssignments();
        $this->ensureSelectedResellerIncluded();
    }

    public function saveAssignments(): void
    {
        $this->validate();

        $allowedRouterIds = $this->availableRouterIds();
        $this->selectedRouterIds = array_values(array_unique(array_map('intval', $this->selectedRouterIds)));

        $selected = array_values(array_intersect($allowedRouterIds, $this->selectedRouterIds));
        $routerIdsToDetach = array_diff($allowedRouterIds, $selected);

        DB::transaction(function () use ($selected, $routerIdsToDetach) {
            if ($routerIdsToDetach) {
                ResellerRouter::query()
                    ->where('reseller_id', $this->resellerId)
                    ->whereIn('router_id', $routerIdsToDetach)
                    ->delete();
            }

            foreach ($selected as $routerId) {
                ResellerRouter::updateOrCreate(
                    [
                        'reseller_id' => $this->resellerId,
                        'router_id' => $routerId,
                    ],
                    [
                        'assigned_by' => Auth::id(),
                    ]
                );
            }
        });

        $this->loadAssignments();
        $this->success(__('Routers for the selected reseller were updated.'));
    }

    public function updatedSelectedRouterIds($value): void
    {
        $this->selectedRouterIds = collect($value ?? [])
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    public function searchResellers(string $value = ''): void
    {
        $this->loadResellerOptions($value);
    }

    public function searchRouters(string $value = ''): void
    {
        $this->loadRouterOptions($value);
    }

    protected function loadResellerOptions(?string $term = null): void
    {
        $this->resellerOptions = $this->resellerQuery($term)
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (User $reseller) => [
                'id' => $reseller->id,
                'name' => $reseller->name,
                'email' => $reseller->email,
            ])
            ->toArray();

        $this->ensureSelectedResellerIncluded();
    }

    protected function loadRouterOptions(?string $term = null): void
    {
        $this->routerOptions = $this->routerQuery($term)
            ->orderBy('name')
            ->limit(25)
            ->get()
            ->map(fn (Router $router) => [
                'id' => $router->id,
                'name' => $router->name,
                'address' => $router->address,
                'owner' => $router->user?->name,
                'detail' => collect([$router->address, $router->user?->name])
                    ->filter()
                    ->implode(' | '),
            ])
            ->toArray();

        $this->ensureSelectedRoutersIncluded();
    }

    protected function loadAssignments(): void
    {
        $allowedRouterIds = $this->availableRouterIds();

        $assignments = ResellerRouter::query()
            ->where('reseller_id', $this->resellerId)
            ->whereIn('router_id', $allowedRouterIds)
            ->with(['router:id,name,address', 'assignedBy:id,name'])
            ->get();

        $this->selectedRouterIds = $assignments->pluck('router_id')->map(fn ($id) => (int) $id)->toArray();

        $this->assignedRouters = $assignments->map(fn (ResellerRouter $assignment) => [
            'id' => $assignment->router_id,
            'name' => $assignment->router?->name ?? __('Unknown Router'),
            'address' => $assignment->router?->address,
            'assigned_at' => optional($assignment->created_at)?->toDayDateTimeString(),
            'assigned_by' => $assignment->assignedBy?->name,
        ])->toArray();

        $this->ensureSelectedRoutersIncluded();
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
            $value = '%'.trim($term).'%';
            $query->where(function (Builder $builder) use ($value) {
                $builder->where('name', 'like', $value)
                    ->orWhere('email', 'like', $value);
            });
        }

        return $query;
    }

    protected function routerQuery(?string $term = null): Builder
    {
        $query = Router::query()
            ->select('id', 'name', 'address', 'user_id')
            ->with('user:id,name');

        $user = Auth::user();

        if ($user && ! $user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($term !== null && trim($term) !== '') {
            $value = '%'.trim($term).'%';
            $query->where(function (Builder $builder) use ($value) {
                $builder->where('name', 'like', $value)
                    ->orWhere('address', 'like', $value);
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
            ->contains(fn ($option) => (int) $option['id'] === (int) $this->resellerId);

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

    protected function ensureSelectedRoutersIncluded(): void
    {
        if (empty($this->selectedRouterIds)) {
            return;
        }

        $existingIds = collect($this->routerOptions)->pluck('id')->map(fn ($id) => (int) $id);
        $missingIds = collect($this->selectedRouterIds)
            ->map(fn ($id) => (int) $id)
            ->diff($existingIds)
            ->values();

        if ($missingIds->isEmpty()) {
            return;
        }

        $additionalRouters = $this->routerQuery(null)
            ->whereIn('id', $missingIds->all())
            ->get()
            ->map(fn (Router $router) => [
                'id' => $router->id,
                'name' => $router->name,
                'address' => $router->address,
                'owner' => $router->user?->name,
                'detail' => collect([$router->address, $router->user?->name])
                    ->filter()
                    ->implode(' | '),
            ])
            ->toArray();

        $this->routerOptions = array_merge($this->routerOptions, $additionalRouters);
    }

    protected function availableRouterIds(): array
    {
        return $this->routerQuery(null)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    protected function rules(): array
    {
        return [
            'resellerId' => ['required', 'integer', 'exists:users,id'],
            'selectedRouterIds' => ['array'],
            'selectedRouterIds.*' => ['integer'],
        ];
    }
}
