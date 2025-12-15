<?php

namespace App\Livewire\Router;

use App\Models\Package;
use App\Models\Router;
use App\Models\VoucherTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class Edit extends Component
{
    use AuthorizesRequests, Toast, WithFileUploads;

    public Router $router;

    #[Rule(['required', 'string', 'max:100'])]
    public string $name = '';

    #[Rule(['required', 'string', 'max:191', 'regex:/^(([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}|(\d{1,3}\.){3}\d{1,3})$/'])]
    public string $address = '';

    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $port = 8728;

    #[Rule(['required', 'string', 'max:100'])]
    public string $username = '';

    #[Rule(['required', 'string', 'max:191'])]
    public string $password = '';

    #[Rule(['nullable', 'string', 'max:191'])]
    public string $login_address = '';

    #[Rule(['nullable', 'integer', 'exists:voucher_templates,id'])]
    public ?int $voucher_template_id = null;

    #[Rule(['nullable', 'numeric', 'min:0'])]
    public float $monthly_expense = 0.0;

    #[Rule(['nullable', 'integer', 'exists:packages,id'])]
    public ?int $package_id = null;

    #[Rule(['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,svg,webp'])]
    public $logo = null;

    public function mount(Router $router): void
    {
        $this->authorize('edit_router');

        // Verify user has access to this specific router
        $user = auth()->user();
        try {
            $user->getAuthorizedRouter($router->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(403, 'You are not authorized to edit this router.');
        }

        $this->router = $router;

        $this->name = $router->name;
        $this->address = $router->address;
        $this->login_address = $router->login_address ?? '';
        $this->port = $router->port;
        $this->username = $router->username;
        $this->password = Crypt::decryptString($router->password);
        $this->voucher_template_id = $router->voucher_template_id;
        $this->monthly_expense = $router->monthly_expense ?? 0.0;
        $this->package_id = $router->package['id'] ?? null;
    }

    public function update(): void
    {
        $this->authorize('edit_router');
        
        // Re-verify user has access to this router
        $user = auth()->user();
        try {
            $user->getAuthorizedRouter($this->router->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->error('You are not authorized to edit this router.');
            return;
        }
        
        $this->validate();

        $updateData = [
            'name' => $this->name,
            'address' => $this->address,
            'login_address' => $this->login_address,
            'port' => $this->port,
            'username' => $this->username,
            'password' => Crypt::encryptString($this->password),
            'voucher_template_id' => $this->voucher_template_id,
            'monthly_expense' => $this->monthly_expense,
            'package' => $this->packageSnapshotForUpdate(),
        ];

        // Handle logo upload - replace old logo if new one is uploaded
        if ($this->logo) {
            // Validate logo is actually an image and not malicious
            $validated = $this->validate([
                'logo' => 'required|image|max:2048|mimes:jpg,jpeg,png,svg,webp|dimensions:max_width=2000,max_height=2000',
            ]);
            
            // Delete old logo if exists
            if ($this->router->logo) {
                Storage::disk('public')->delete($this->router->logo);
            }
            // Store new logo with a random name to prevent path traversal
            $updateData['logo'] = $this->logo->store('logos', 'public');
        }

        $this->router->update($updateData);

        $this->success(title: 'Success', description: 'Router updated successfully.');

        $this->redirect(route('routers.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('routers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.router.edit', [
            'voucherTemplates' => VoucherTemplate::select('id', 'name')
                ->orderBy('name')
                ->get(),
            'packages' => Package::orderBy('name')->get(['id', 'name', 'billing_cycle']),
            'storedPackage' => $this->router->package,
        ])
            ->title(__('Edit Router'));
    }

    protected function packagePayload(?int $packageId): ?array
    {
        if (! $packageId) {
            return null;
        }

        $package = Package::find($packageId);

        if (! $package) {
            return null;
        }

        $snapshot = [
            'id' => $package->id,
            'name' => $package->name,
            'price_monthly' => $package->price_monthly,
            'price_yearly' => $package->price_yearly,
            'user_limit' => $package->user_limit,
            'billing_cycle' => $package->billing_cycle,
            'auto_renew_allowed' => $package->auto_renew_allowed,
            'description' => $package->description,
        ];

        return $snapshot;
    }

    protected function packageSnapshotForUpdate(): ?array
    {
        if ($this->package_id === null) {
            return null;
        }

        $snapshot = $this->packagePayload($this->package_id);

        if (! $snapshot && isset($this->router->package['id']) && $this->router->package['id'] === $this->package_id) {
            return $this->router->package;
        }

        return $snapshot;
    }
}
