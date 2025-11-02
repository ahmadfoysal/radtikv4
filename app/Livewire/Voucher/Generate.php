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
    public string $tab = 'mikrotik'; // 'mikrotik' | 'radius'

    // Inputs
    #[V(['required', 'integer', 'min:1', 'max:10000'])]
    public int $quantity = 10;
    #[V(['required', 'integer', 'min:8', 'max:32'])]
    public int $length   = 8;
    #[V(['nullable', 'string', 'max:10'])]
    public string $prefix = '';
    #[V(['nullable', 'integer', 'min:0', 'max:99999999'])]
    public ?int $serial_start = null;

    /**
     * Character types (match the image):
     * letters_upper | letters_mixed | alnum_lower | alnum_upper | alnum_mixed
     */
    // #[V(['required', 'in:letters_upper,letters_mixed,alnum_lower,alnum_upper,alnum_mixed'])]
    public string $char_type = 'letters_upper';

    // MikroTik tab
    #[V(['required_if:tab,mikrotik', 'nullable', 'exists:routers,id'])]
    public $router_id = null;

    #[V(['required_if:tab,mikrotik', 'nullable', 'string', 'max:64'])]
    public string $mikrotik_profile = '';

    // RADIUS tab
    #[V(['required_if:tab,radius', 'nullable', 'string', 'max:64'])]
    public string $radius_profile = '';

    /** Character set for the selected type */
    protected function charset(): string
    {
        return match ($this->char_type) {
            'neumeric'      => '0123456789',
            'letters_upper' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'letters_lower' => 'abcdefghijklmnopqrstuvwxyz',
            'letters_mixed' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'alnum_lower'   => 'abcdefghijklmnopqrstuvwxyz0123456789',
            'alnum_upper'   => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            'alnum_mixed'   => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            default         => '0123456789',
        };
    }

    /** Random code of given length from charset */
    protected function randomCode(int $len): string
    {
        $cs  = $this->charset();
        $out = '';
        $max = strlen($cs) - 1;

        for ($i = 0; $i < $len; $i++) {
            $out .= $cs[random_int(0, $max)];
        }
        return $out;
    }

    /** Build a single credential: prefix + serial + random(length) */
    protected function buildCredential(int $index): string
    {
        $prefix    = $this->prefix ?? '';
        $serialStr = '';

        if ($this->serial_start !== null && $this->serial_start !== '') {
            $serialStr = (string) ((int) $this->serial_start + $index);
            // চাইলে এখানে padding যোগ করতে পারেন, উদাহরণ:
            // $serialStr = str_pad($serialStr, 4, '0', STR_PAD_LEFT);
        }

        $randomStr = $this->randomCode($this->length);

        return $prefix . $serialStr . $randomStr;
    }


    /**
     * === The method you asked for ===
     * Returns an array of UNIQUE voucher codes based on current inputs.
     * Usage: $codes = $this->generateCodes();
     */
    public function generate(): array
    {
        $this->validate();

        if ($this->tab === 'mikrotik') {
            if (empty($this->router_id) || $this->mikrotik_profile === '') {
                throw new \RuntimeException('Router and MikroTik profile are required for MikroTik tab.');
            }
        } elseif ($this->tab === 'radius') {
            if ($this->radius_profile === '') {
                throw new \RuntimeException('RADIUS profile is required for RADIUS tab.');
            }
        }

        $codes = [];
        $seen  = [];

        for ($i = 0; $i < $this->quantity; $i++) {

            // try until unique
            $attempts = 0;
            do {
                $u = $this->buildCredential($i);
                $attempts++;
                if ($attempts > 5 && isset($seen[$u])) {
                    // খুবই rare: fallback – random অংশটি রিজেনারেট করুন
                    $u .= $this->randomCode(2);
                }
            } while (isset($seen[$u]));

            $seen[$u] = true;
            $codes[]  = $u;
        }
        return $codes;
    }


    /**
     * Optional helper: build DB-ready rows from the generated codes.
     * Call: $rows = $this->buildRows($this->generateCodes());
     * Then later: Voucher::insert($rows);
     */
    public function buildRows(array $codes): array
    {
        $batch = 'B' . now()->format('ymdHis') . Str::upper(Str::random(4));
        $userId = auth()->id();

        $rows = [];
        foreach ($codes as $code) {
            $rows[] = [
                'name'            => $code,
                'username'        => $code,
                'password'        => $code,
                'router_profile'  => $this->tab === 'mikrotik' ? $this->mikrotik_profile : null,
                'radius_profile'  => $this->tab === 'radius'   ? $this->radius_profile   : null,
                'expires_at'      => null,
                'user_id'         => $userId,
                'router_id'       => $this->router_id,
                'created_by'      => $userId,
                'status'          => 'inactive',
                'mac_address'     => null,
                'activated_at'    => null,
                'batch'           => $batch,
                'is_radius'       => $this->tab === 'radius',
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }
        return $rows;
    }

    public function save()
    {
        $codes = $this->generate();
        $rows  = $this->buildRows($codes);
        Voucher::insert($rows);

        $this->success(
            title: 'Success!',
            description: 'Vouchers generated successfully.'
        );
    }



    public function render()
    {
        return view('livewire.voucher.generate', [
            'routers' => Router::orderBy('name')->get(['id', 'name', 'address']),
        ]);
    }
}
