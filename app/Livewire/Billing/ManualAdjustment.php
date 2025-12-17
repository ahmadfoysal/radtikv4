<?php

namespace App\Livewire\Billing;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Mary\Traits\Toast;
use RuntimeException;

class ManualAdjustment extends Component
{
    use Toast;

    public ?int $adminId = null;

    public string $action = 'credit';

    public ?float $amount = null;

    public string $category = 'manual_adjustment';

    public ?string $description = null;

    public array $adminOptions = [];

    public ?float $currentBalance = null;

    public ?float $commissionPercentage = null;

    public function getCommissionAmountProperty(): float
    {
        if (!$this->amount || !$this->commissionPercentage || $this->action !== 'credit') {
            return 0;
        }
        return round(($this->amount * $this->commissionPercentage) / 100, 2);
    }

    public function getTotalCreditProperty(): float
    {
        if ($this->action !== 'credit' || !$this->amount) {
            return 0;
        }
        return $this->amount + $this->commissionAmount;
    }

    public function mount(): void
    {
        //  abort_unless(auth()->user()?->hasRole('superadmin'), 403);

        $this->loadAdminOptions();
    }

    public function render(): View
    {
        return view('livewire.billing.manual-adjustment');
    }

    public function updatedAdminId($value): void
    {
        $this->currentBalance = null;
        $this->commissionPercentage = null;

        if (empty($value)) {
            return;
        }

        $user = User::role('admin')
            ->select('id', 'balance', 'commission')
            ->find($value);

        if ($user) {
            $this->currentBalance = (float) $user->balance;
            $this->commissionPercentage = (float) $user->commission;
        }
    }

    public function submit(): void
    {
        $validated = $this->validate($this->rules());

        $user = User::role('admin')->findOrFail($validated['adminId']);

        try {
            $invoice = match ($validated['action']) {
                'credit' => $user->credit(
                    amount: (float) $validated['amount'],
                    category: $validated['category'],
                    description: $this->description,
                    meta: $this->buildMetaPayload('credit')
                ),
                'debit' => $user->debit(
                    amount: (float) $validated['amount'],
                    category: $validated['category'],
                    description: $this->description,
                    meta: $this->buildMetaPayload('debit')
                ),
                'adjust' => $this->performAdjustment(
                    $user,
                    (float) $validated['amount'],
                    $validated['category'],
                    $this->description
                ),
                default => null,
            };
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return;
        }

        if ($validated['action'] === 'adjust' && $invoice === null) {
            $this->info('Balance already matches the requested amount.');

            return;
        }

        $this->updatedAdminId($user->id);

        $this->reset(['amount', 'description']);
        $this->action = 'credit';

        $this->success('Balance updated successfully.');
    }

    public function resetForm(): void
    {
        $this->reset(['amount', 'description']);
        $this->action = 'credit';
    }

    public function search(string $value = ''): void
    {
        $this->loadAdminOptions($value);
    }

    protected function loadAdminOptions(?string $term = null): void
    {
        $this->adminOptions = $this->adminQuery($term)
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn(User $admin) => $this->formatAdminOption($admin))
            ->toArray();

        $this->ensureSelectedAdminIncluded();
    }

    protected function adminQuery(?string $term = null): Builder
    {
        return User::role('admin')
            ->select('id', 'name', 'email')
            ->when(
                $term !== null && trim($term) !== '',
                fn(Builder $query) => $query->where(function (Builder $inner) use ($term) {
                    $value = '%' . trim($term) . '%';
                    $inner->where('name', 'like', $value)
                        ->orWhere('email', 'like', $value);
                })
            );
    }

    protected function ensureSelectedAdminIncluded(): void
    {
        if (! $this->adminId) {
            return;
        }

        if (collect($this->adminOptions)->contains(fn($option) => (int) $option['id'] === $this->adminId)) {
            return;
        }

        $admin = User::role('admin')
            ->select('id', 'name', 'email')
            ->find($this->adminId);

        if ($admin) {
            $this->adminOptions[] = $this->formatAdminOption($admin);
        }
    }

    protected function formatAdminOption(User $admin): array
    {
        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
        ];
    }

    protected function rules(): array
    {
        return [
            'adminId' => 'required|integer|exists:users,id',
            'action' => 'required|string|in:credit,debit,adjust',
            'amount' => [
                'required',
                'numeric',
                $this->action === 'adjust' ? 'min:0' : 'min:0.01',
            ],
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ];
    }

    protected function buildMetaPayload(string $operation, ?float $targetBalance = null): array
    {
        return [
            'operation' => $operation,
            'actor_id' => auth()->id(),
            'actor_name' => auth()->user()?->name,
            'target_balance' => $targetBalance,
        ];
    }

    protected function performAdjustment(User $user, float $targetBalance, string $category, ?string $description): ?Invoice
    {
        if ($targetBalance < 0) {
            throw new RuntimeException('Target balance cannot be negative.');
        }

        $currentBalance = (float) $user->balance;
        $difference = $targetBalance - $currentBalance;

        if (abs($difference) < 0.01) {
            return null;
        }

        if ($difference > 0) {
            return $user->credit(
                amount: $difference,
                category: $category,
                description: $description,
                meta: $this->buildMetaPayload('adjust_credit', $targetBalance)
            );
        }

        return $user->debit(
            amount: abs($difference),
            category: $category,
            description: $description,
            meta: $this->buildMetaPayload('adjust_debit', $targetBalance)
        );
    }
}
