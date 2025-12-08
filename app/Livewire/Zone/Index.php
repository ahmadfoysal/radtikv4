<?php

namespace App\Livewire\Zone;

use App\Models\Zone;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Index extends Component
{
    use Toast, WithPagination;

    public string $search = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public ?string $description = null;

    #[Validate('required|regex:/^#[0-9a-fA-F]{6}$/')]
    public string $color = '#2563eb';

    #[Validate('boolean')]
    public bool $is_active = true;

    public ?int $zoneId = null;

    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $userId = auth()->id();

        if (! $userId) {
            $this->error('Authentication required.', 'Please sign in again.');

            return;
        }

        $this->validate();

        $payload = [
            'name' => trim($this->name),
            'description' => $this->description ? trim($this->description) : null,
            'color' => strtolower($this->color),
            'is_active' => $this->is_active,
            'user_id' => $userId,
        ];

        if ($this->zoneId) {
            $zone = $this->currentUserZones()->find($this->zoneId);

            if (! $zone) {
                $this->error('Zone not found.', 'The selected zone is no longer available.');

                return;
            }

            $zone->update($payload);

            $this->success('Zone updated', 'Changes saved successfully.');
        } else {
            Zone::create($payload);

            $this->success('Zone created', 'Zone added successfully.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $zone = $this->currentUserZones()->find($id);

        if (! $zone) {
            $this->error('Zone not found.', 'The selected zone is no longer available.');

            return;
        }

        $this->zoneId = $zone->id;
        $this->name = $zone->name;
        $this->description = $zone->description;
        $this->color = $zone->color ?? '#2563eb';
        $this->is_active = $zone->is_active;
    }

    public function delete(int $id): void
    {
        $zone = $this->currentUserZones()->withCount('routers')->find($id);

        if (! $zone) {
            $this->error('Zone not found.', 'Nothing to delete.');

            return;
        }

        if ($zone->routers_count > 0) {
            $this->error('Zone in use', 'Detach routers before deleting this zone.');

            return;
        }

        $zone->delete();

        if ($this->zoneId === $id) {
            $this->resetForm();
        }

        $this->success('Deleted', 'Zone removed successfully.');
        $this->resetPage();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->reset([
            'name',
            'description',
            'color',
            'is_active',
            'zoneId',
        ]);

        $this->name = '';
        $this->description = null;
        $this->color = '#2563eb';
        $this->is_active = true;

        $this->resetValidation();
    }

    protected function currentUserZones()
    {
        $userId = auth()->id();

        return Zone::query()->when($userId, fn ($query) => $query->where('user_id', $userId), function ($query) {
            $query->whereRaw('1 = 0');
        });
    }

    public function render()
    {
        $zones = $this->currentUserZones()
            ->withCount('routers')
            ->when($this->search !== '', function ($query) {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.zone.index', [
            'zones' => $zones,
        ]);
    }
}
