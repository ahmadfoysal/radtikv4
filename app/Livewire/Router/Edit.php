<?php

namespace App\Livewire\Router;

use Livewire\Component;
use App\Models\Router;
use App\Models\VoucherTemplate;
use App\Models\Package;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\Rule;
use Mary\Traits\Toast;

class Edit extends Component
{
    use Toast;

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

    public function mount(Router $router): void
    {
        if ($router->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $this->router = $router;

        $this->name     = $router->name;
        $this->address  = $router->address;
        $this->login_address = $router->login_address ?? '';
        $this->port     = $router->port;
        $this->username = $router->username;
        $this->password = Crypt::decryptString($router->password);
        $this->voucher_template_id = $router->voucher_template_id;
        $this->monthly_expense = $router->monthly_expense ?? 0.0;
        $this->package_id = $router->package['id'] ?? null;
    }

    public function update(): void
    {
        $this->validate();

        $this->router->update([
            'name'     => $this->name,
            'address'  => $this->address,
            'login_address' => $this->login_address,
            'port'     => $this->port,
            'username' => $this->username,
            'password' => Crypt::encryptString($this->password),
            'voucher_template_id' => $this->voucher_template_id,
            'monthly_expense' => $this->monthly_expense,
            'package' => $this->packageSnapshotForUpdate(),
        ]);

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
        if (!$packageId) {
            return null;
        }

        $package = Package::find($packageId);

        if (!$package) {
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

        if (!$snapshot && isset($this->router->package['id']) && $this->router->package['id'] === $this->package_id) {
            return $this->router->package;
        }

        return $snapshot;
    }
}
