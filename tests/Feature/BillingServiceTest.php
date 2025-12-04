<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Router;
use App\Models\User;
use App\Models\Zone;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billingService = new BillingService;
    }

    public function test_credit_adds_to_user_balance_and_creates_invoice(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $invoice = $this->billingService->credit(
            $user,
            50.00,
            'topup',
            'Test credit transaction'
        );

        // Refresh user to get updated balance
        $user->refresh();

        $this->assertEquals(150.00, (float) $user->balance);
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals('credit', $invoice->type);
        $this->assertEquals('topup', $invoice->category);
        $this->assertEquals(50.00, (float) $invoice->amount);
        $this->assertEquals(150.00, (float) $invoice->balance_after);
        $this->assertEquals('Test credit transaction', $invoice->description);
        $this->assertEquals('BDT', $invoice->currency);
    }

    public function test_credit_throws_exception_for_non_positive_amount(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Credit amount must be positive.');

        $this->billingService->credit($user, 0, 'topup');
    }

    public function test_credit_throws_exception_for_negative_amount(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Credit amount must be positive.');

        $this->billingService->credit($user, -50, 'topup');
    }

    public function test_debit_subtracts_from_user_balance_and_creates_invoice(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $invoice = $this->billingService->debit(
            $user,
            30.00,
            'subscription',
            'Monthly subscription'
        );

        // Refresh user to get updated balance
        $user->refresh();

        $this->assertEquals(70.00, (float) $user->balance);
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals('debit', $invoice->type);
        $this->assertEquals('subscription', $invoice->category);
        $this->assertEquals(30.00, (float) $invoice->amount);
        $this->assertEquals(70.00, (float) $invoice->balance_after);
        $this->assertEquals('Monthly subscription', $invoice->description);
        $this->assertEquals('BDT', $invoice->currency);
    }

    public function test_debit_throws_exception_for_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 50.00]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient balance.');

        $this->billingService->debit($user, 100.00, 'subscription');
    }

    public function test_debit_throws_exception_for_non_positive_amount(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Debit amount must be positive.');

        $this->billingService->debit($user, 0, 'subscription');
    }

    public function test_debit_allows_exact_balance_debit(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $invoice = $this->billingService->debit($user, 100.00, 'subscription');

        $user->refresh();

        $this->assertEquals(0.00, (float) $user->balance);
        $this->assertEquals(0.00, (float) $invoice->balance_after);
    }

    public function test_credit_with_router_association(): void
    {
        $user = User::factory()->create(['balance' => 0.00]);

        $zone = new Zone;
        $zone->name = 'Test Zone';
        $zone->user_id = $user->id;
        $zone->save();

        $router = new Router;
        $router->name = 'Test Router';
        $router->address = '192.168.1.1';
        $router->port = 8728;
        $router->username = 'admin';
        $router->password = encrypt('password');
        $router->user_id = $user->id;
        $router->zone_id = $zone->id;
        $router->save();

        $invoice = $this->billingService->credit(
            $user,
            100.00,
            'topup',
            null,
            [],
            $router
        );

        $this->assertEquals($router->id, $invoice->router_id);
    }

    public function test_credit_with_meta_data(): void
    {
        $user = User::factory()->create(['balance' => 0.00]);
        $meta = ['transaction_id' => 'TXN123', 'payment_method' => 'bkash'];

        $invoice = $this->billingService->credit(
            $user,
            100.00,
            'topup',
            'Payment via bKash',
            $meta
        );

        $this->assertEquals($meta, $invoice->meta);
    }

    public function test_invoice_belongs_to_user(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $invoice = $this->billingService->credit($user, 50.00, 'topup');

        $this->assertTrue($invoice->user->is($user));
    }

    public function test_user_has_many_invoices(): void
    {
        $user = User::factory()->create(['balance' => 0.00]);

        $this->billingService->credit($user, 100.00, 'topup');
        $this->billingService->debit($user, 30.00, 'subscription');

        $user->refresh();

        $this->assertCount(2, $user->invoices);
    }

    public function test_multiple_sequential_transactions(): void
    {
        $user = User::factory()->create(['balance' => 0.00]);

        // First credit
        $this->billingService->credit($user, 100.00, 'topup');
        $user->refresh();
        $this->assertEquals(100.00, (float) $user->balance);

        // Second credit
        $this->billingService->credit($user, 50.00, 'adjustment');
        $user->refresh();
        $this->assertEquals(150.00, (float) $user->balance);

        // First debit
        $this->billingService->debit($user, 25.00, 'subscription');
        $user->refresh();
        $this->assertEquals(125.00, (float) $user->balance);

        // Check invoices
        $this->assertCount(3, $user->invoices);
    }
}
