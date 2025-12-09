<?php

namespace App\Livewire\Voucher;

use App\Models\Router;
use App\Models\Voucher;
use Illuminate\Support\Str;
use Livewire\Attributes\Rule as V;
use Livewire\Component;
use Mary\Traits\Toast;

class Generate extends Component
{
    use Toast;

    // === Mode Selection ===
    public string $type = 'mikrotik'; // 'mikrotik' | 'radius'

    // === Inputs ===
    #[V(['required', 'integer', 'min:1', 'max:5000'])]
    public int $quantity = 10;

    #[V(['required', 'integer', 'min:4', 'max:32'])]
    public int $length = 8;

    #[V(['nullable', 'string', 'max:10', 'alpha_dash'])]
    public string $prefix = '';

    #[V(['nullable', 'integer', 'min:0'])]
    public ?int $serial_start = null;

    public string $char_type = 'letters_upper';

    // === Router & Profile ===

    // Schema অনুযায়ী router_id এখন required (উভয় মোডেই)
    #[V(['required', 'exists:routers,id'])]
    public $router_id = null;

    // UserProfile ID (Table: user_profiles)
    #[V(['required', 'exists:user_profiles,id'])]
    public $profile_id = '';

    // Options container
    public array $available_profiles = [];

    // === Lifecycle Hooks ===

    public function mount()
    {
        $this->loadProfiles();
    }

    /**
     * লোডিং লজিক এখন একদম সিম্পল।
     * সরাসরি ইউজারের রিলেশন থেকে প্রোফাইলগুলো লোড হবে।
     */
    public function loadProfiles()
    {
        $this->available_profiles = auth()->user()->profiles()
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
            ->all();
    }

    // === Generators ===

    protected function charset(): string
    {
        return match ($this->char_type) {
            'numeric' => '0123456789',
            'letters_upper' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'letters_lower' => 'abcdefghijklmnopqrstuvwxyz',
            'letters_mixed' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'alnum_upper' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            'alnum_mixed' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            default => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        };
    }

    public function save()
    {
        $this->validate();

        $codes = $this->generateCodes();
        $rows = $this->buildRows($codes);

        Voucher::insert($rows);

        // Log bulk voucher generation
        \App\Services\ActivityLogger::logCustom(
            'bulk_generated',
            null,
            "Generated {$this->quantity} vouchers in batch {$rows[0]['batch']}",
            [
                'quantity' => $this->quantity,
                'batch' => $rows[0]['batch'],
                'router_id' => $this->router_id,
                'profile_id' => $this->profile_id,
                'type' => $this->type,
            ]
        );

        $this->success('Vouchers generated successfully.');
        // nevigat to route
        $this->redirect(route('vouchers.index'), navigate: true);
    }

    protected function generateCodes(): array
    {
        $codes = [];
        $seen = [];
        $cs = $this->charset();
        $max = strlen($cs) - 1;

        for ($i = 0; $i < $this->quantity; $i++) {
            $attempts = 0;
            do {
                $serial = $this->serial_start !== null ? (string) ($this->serial_start + $i) : '';
                $rnd = '';
                for ($j = 0; $j < $this->length; $j++) {
                    $rnd .= $cs[random_int(0, $max)];
                }

                $u = $this->prefix.$serial.$rnd;

                // Collision fallback
                if ($attempts++ > 5 && isset($seen[$u])) {
                    $u .= $cs[random_int(0, $max)];
                }
            } while (isset($seen[$u]));

            $seen[$u] = true;
            $codes[] = $u;
        }

        return $codes;
    }

    protected function buildRows(array $codes): array
    {
        $batch = 'B'.now()->format('ymdHis').Str::upper(Str::random(4));
        $userId = auth()->id();
        $now = now();

        $rows = [];
        foreach ($codes as $code) {
            $rows[] = [
                'name' => $code,
                'username' => $code,
                'password' => $code,
                'batch' => $batch,
                'status' => 'inactive',
                'created_by' => $userId,
                'user_id' => $userId,
                'router_id' => $this->router_id, // Required by Schema
                'user_profile_id' => $this->profile_id, // Unified Profile ID
                'is_radius' => $this->type === 'radius',
                'created_at' => $now,
                'updated_at' => $now,
                'bytes_in' => 0,
                'bytes_out' => 0,
            ];
        }

        return $rows;
    }

    public function cancel(): void
    {
        $this->resetValidation();
        $this->redirect(route('vouchers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.voucher.generate', [
            'routers' => Router::orderBy('name')->get(['id', 'name', 'address']),
        ]);
    }
}
