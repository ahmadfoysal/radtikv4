<?php

namespace App\Livewire\Router;

use App\Models\Package;
use App\Models\Router;
use App\Models\VoucherTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class Create extends Component
{
    use Toast, WithFileUploads;

    #[Rule(['required', 'string', 'max:100'])]
    public string $name = '';

    #[Rule(['required', 'string', 'max:191', 'regex:/^(([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}|(\d{1,3}\.){3}\d{1,3})$/'])] // IP or hostname
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

    #[Rule(['required', 'integer', 'exists:packages,id'])]
    public ?int $package_id = null;

    #[Rule(['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,svg,webp'])]
    public $logo = null;

    public function mount(): void
    {
        $this->voucher_template_id = VoucherTemplate::query()
            ->where('is_active', true)
            ->value('id') ?? VoucherTemplate::query()->value('id');
    }

    public function save()
    {
        $this->validate();

        $user = Auth::user();
        $voucherTemplateId = $this->voucher_template_id
            ?? VoucherTemplate::query()->where('is_active', true)->value('id')
            ?? VoucherTemplate::query()->value('id');

        // Handle logo upload
        $logoPath = null;
        if ($this->logo) {
            $logoPath = $this->logo->store('logos', 'public');
        }

        // If package is selected, use the subscription service
        if ($this->package_id) {
            $package = Package::find($this->package_id);

            if (! $package) {
                $this->error(title: 'Error', description: 'Selected package not found.');

                return;
            }

            // Check if user has enough balance
            if (! $user->hasBalanceForPackage($package)) {
                $this->error(title: 'Insufficient Balance', description: 'You do not have enough balance to subscribe to this package.');

                return;
            }

            try {
                // Use the subscription service to create router with billing
                $user->subscribeRouterWithPackage([
                    'name' => $this->name,
                    'address' => $this->address,
                    'login_address' => $this->login_address,
                    'port' => $this->port,
                    'username' => $this->username,
                    'password' => Crypt::encryptString($this->password),
                    'app_key' => bin2hex(random_bytes(16)),
                    'voucher_template_id' => $voucherTemplateId,
                    'monthly_expense' => $this->monthly_expense,
                    'logo' => $logoPath,
                ], $package);
            } catch (\RuntimeException $e) {
                // Delete uploaded logo if router creation fails
                if ($logoPath) {
                    Storage::disk('public')->delete($logoPath);
                }
                $this->error(title: 'Error', description: $e->getMessage());

                return;
            }
        } else {
            // Create router without package (no billing)
            Router::create([
                'name' => $this->name,
                'address' => $this->address,
                'login_address' => $this->login_address,
                'port' => $this->port,
                'username' => $this->username,
                'password' => Crypt::encryptString($this->password),
                'app_key' => bin2hex(random_bytes(16)),
                'user_id' => Auth::id(),
                'voucher_template_id' => $voucherTemplateId,
                'monthly_expense' => $this->monthly_expense,
                'logo' => $logoPath,
            ]);
        }

        // Reset form (keep port default)
        $this->reset([
            'name',
            'address',
            'login_address',
            'username',
            'password',
            'voucher_template_id',
            'monthly_expense',
            'package_id',
            'logo',
        ]);
        $this->port = 8728;
        $this->voucher_template_id = VoucherTemplate::query()
            ->where('is_active', true)
            ->value('id') ?? VoucherTemplate::query()->value('id');
        $this->monthly_expense = 0.0;

        // Optional: toast/notify
        $this->success(title: 'Success', description: 'Router added successfully.');

        return $this->redirect(route('routers.index'), navigate: true);
    }

    public function cancel()
    {
        $this->redirect(route('routers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.router.create', [
            'voucherTemplates' => VoucherTemplate::select('id', 'name')
                ->orderBy('name')
                ->get(),
            'packages' => Package::orderBy('name')->get(['id', 'name', 'billing_cycle']),
        ])
            ->title(__('Add Router'));
    }
}
