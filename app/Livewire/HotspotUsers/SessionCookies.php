<?php

namespace App\Livewire\HotspotUsers;

use App\MikroTik\Actions\HotspotUserManager;
use App\Models\Router;
use Livewire\Component;
use Mary\Traits\Toast;

class SessionCookies extends Component
{
    use Toast;

    public $router_id = null;
    public array $cookies = [];
    public bool $loading = false;

    public function mount()
    {
        // Initialize empty
    }

    public function updatedRouterId($value)
    {
        if (!$value) {
            $this->cookies = [];
            return;
        }

        $this->loadCookies();
    }

    public function loadCookies()
    {
        if (!$this->router_id) {
            $this->cookies = [];
            return;
        }

        $this->loading = true;

        try {
            $router = auth()->user()->routers()->findOrFail($this->router_id);
            $manager = app(HotspotUserManager::class);
            
            $this->cookies = $manager->getSessionCookies($router);
            
            if (empty($this->cookies)) {
                $this->info('No session cookies found.');
            }
        } catch (\Throwable $e) {
            $this->error('Failed to load session cookies: ' . $e->getMessage());
            $this->cookies = [];
        } finally {
            $this->loading = false;
        }
    }

    public function deleteCookie(string $cookieId)
    {
        try {
            $router = auth()->user()->routers()->findOrFail($this->router_id);
            $manager = app(HotspotUserManager::class);
            
            $manager->deleteSessionCookie($router, $cookieId);
            
            $this->success('Cookie removed successfully.');
            $this->loadCookies();
        } catch (\Throwable $e) {
            $this->error('Failed to remove cookie: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.hotspot-users.session-cookies', [
            'routers' => auth()->user()->routers()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
