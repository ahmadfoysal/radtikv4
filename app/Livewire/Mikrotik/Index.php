<?php

namespace App\Livewire\Mikrotik;

use Livewire\Component;

class Index extends Component
{
    public array $routers = [];

    public function mount()
    {
        // Example static data (replace with DB data)
        $this->routers = [
            ['id' => 1, 'name' => 'MKT-01', 'ip' => '10.0.0.1', 'host' => 'core-router.local', 'protocol' => 'api', 'port' => 8728, 'uptime' => '6d 12h', 'status' => 'Online'],
            ['id' => 2, 'name' => 'MKT-02', 'ip' => '10.0.0.2', 'host' => 'branch-a.local', 'protocol' => 'ssh', 'port' => 22, 'uptime' => '12h 03m', 'status' => 'Online'],
            ['id' => 3, 'name' => 'MKT-03', 'ip' => '10.0.0.3', 'host' => 'branch-b.local', 'protocol' => 'api', 'port' => 8728, 'uptime' => '—', 'status' => 'Offline'],
            ['id' => 4, 'name' => 'MKT-04', 'ip' => '10.0.0.4', 'host' => 'branch-c.local', 'protocol' => 'api', 'port' => 8728, 'uptime' => '2d 01h', 'status' => 'Degraded'],
            ['id' => 5, 'name' => 'MKT-05', 'ip' => '10.0.0.5', 'host' => 'lab-router.local', 'protocol' => 'api', 'port' => 8728, 'uptime' => '8d 06h', 'status' => 'Online'],
        ];
    }

    public function create()
    {
        // Redirect or open modal for add-router
        $this->dispatch('toast', type: 'info', title: 'Add Router', description: 'Redirecting to create form...');
        // return redirect()->route('routers.create');
    }

    public function show($id)
    {
        $this->dispatch('toast', type: 'info', title: 'Router Details', description: "Router ID: $id");
    }

    public function edit($id)
    {
        $this->dispatch('toast', type: 'info', title: 'Edit Router', description: "Editing Router ID: $id");
    }

    public function toggle($id)
    {
        $this->dispatch('toast', type: 'success', title: 'Router Status Changed', description: "Toggled router ID: $id");
    }

    public function render()
    {
        return view('livewire.mikrotik.index')
            ->layout('components.layouts.app', ['title' => 'Routers – RadTik']);
    }
}
