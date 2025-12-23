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
    public float $monthly_isp_cost = 0.0;

    #[Rule(['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,svg,webp'])]
    public $logo = null;

    public function mount(Router $router): void
    {
        $this->authorize('edit_router');

        $this->router = $router;

        $this->name = $router->name;
        $this->address = $router->address;
        $this->login_address = $router->login_address ?? '';
        $this->port = $router->port;
        $this->username = $router->username;
        $this->password = Crypt::decryptString($router->password);
        $this->voucher_template_id = $router->voucher_template_id;
        $this->monthly_isp_cost = $router->monthly_isp_cost ?? 0.0;
    }

    public function update(): void
    {
        $this->authorize('edit_router');
        $this->validate();

        $updateData = [
            'name' => $this->name,
            'address' => $this->address,
            'login_address' => $this->login_address,
            'port' => $this->port,
            'username' => $this->username,
            'password' => Crypt::encryptString($this->password),
            'voucher_template_id' => $this->voucher_template_id,
            'monthly_isp_cost' => $this->monthly_isp_cost,
        ];

        // Handle logo upload - replace old logo if new one is uploaded
        if ($this->logo) {
            // Delete old logo if exists
            if ($this->router->logo) {
                Storage::disk('public')->delete($this->router->logo);
            }
            // Store new logo
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
        ])
            ->title(__('Edit Router'));
    }
}
