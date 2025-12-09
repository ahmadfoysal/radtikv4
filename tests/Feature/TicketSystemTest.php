<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    \Spatie\Permission\Models\Role::create(['name' => 'superadmin']);
    \Spatie\Permission\Models\Role::create(['name' => 'admin']);
    \Spatie\Permission\Models\Role::create(['name' => 'reseller']);

    // Create users
    $this->superadmin = User::factory()->create(['email' => 'superadmin@test.com']);
    $this->superadmin->assignRole('superadmin');

    $this->admin = User::factory()->create(['email' => 'admin@test.com']);
    $this->admin->assignRole('admin');

    $this->reseller = User::factory()->create(['email' => 'reseller@test.com']);
    $this->reseller->assignRole('reseller');
});

test('admin can access tickets index page', function () {
    $this->actingAs($this->admin)
        ->get(route('tickets.index'))
        ->assertOk();
});

test('reseller can access tickets index page', function () {
    $this->actingAs($this->reseller)
        ->get(route('tickets.index'))
        ->assertOk();
});

test('superadmin can access tickets index page', function () {
    $this->actingAs($this->superadmin)
        ->get(route('tickets.index'))
        ->assertOk();
});

test('admin can create a ticket', function () {
    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\Tickets\Index::class)
        ->set('subject', 'Test Ticket')
        ->set('description', 'This is a test ticket description')
        ->set('priority', 'normal')
        ->call('create')
        ->assertHasNoErrors();

    expect(Ticket::count())->toBe(1);
    $ticket = Ticket::first();
    expect($ticket->subject)->toBe('Test Ticket');
    expect($ticket->owner_id)->toBe($this->admin->id);
    expect($ticket->created_by)->toBe($this->admin->id);
});

test('superadmin can create a ticket for another user', function () {
    $this->actingAs($this->superadmin);

    Livewire::test(\App\Livewire\Tickets\Index::class)
        ->set('subject', 'Test Ticket')
        ->set('description', 'Test description')
        ->set('priority', 'high')
        ->set('owner_id', $this->admin->id)
        ->call('create')
        ->assertHasNoErrors();

    expect(Ticket::count())->toBe(1);
    $ticket = Ticket::first();
    expect($ticket->owner_id)->toBe($this->admin->id);
    expect($ticket->created_by)->toBe($this->superadmin->id);
});

test('admin can only see their own tickets', function () {
    $this->actingAs($this->admin);

    // Create ticket for admin
    Ticket::create([
        'subject' => 'Admin Ticket',
        'description' => 'Test',
        'status' => 'open',
        'created_by' => $this->admin->id,
        'owner_id' => $this->admin->id,
    ]);

    // Create ticket for reseller
    Ticket::create([
        'subject' => 'Reseller Ticket',
        'description' => 'Test',
        'status' => 'open',
        'created_by' => $this->reseller->id,
        'owner_id' => $this->reseller->id,
    ]);

    Livewire::test(\App\Livewire\Tickets\Index::class)
        ->assertSee('Admin Ticket')
        ->assertDontSee('Reseller Ticket');
});

test('superadmin can see all tickets', function () {
    $this->actingAs($this->superadmin);

    // Create tickets for different users
    Ticket::create([
        'subject' => 'Admin Ticket',
        'description' => 'Test',
        'status' => 'open',
        'created_by' => $this->admin->id,
        'owner_id' => $this->admin->id,
    ]);

    Ticket::create([
        'subject' => 'Reseller Ticket',
        'description' => 'Test',
        'status' => 'open',
        'created_by' => $this->reseller->id,
        'owner_id' => $this->reseller->id,
    ]);

    Livewire::test(\App\Livewire\Tickets\Index::class)
        ->assertSee('Admin Ticket')
        ->assertSee('Reseller Ticket');
});

test('superadmin can update ticket status', function () {
    $this->actingAs($this->superadmin);

    $ticket = Ticket::create([
        'subject' => 'Test Ticket',
        'description' => 'Test',
        'status' => 'open',
        'created_by' => $this->admin->id,
        'owner_id' => $this->admin->id,
    ]);

    Livewire::test(\App\Livewire\Tickets\Show::class, ['ticket' => $ticket])
        ->call('toggleEditMode')
        ->set('status', 'in_progress')
        ->call('updateTicket')
        ->assertHasNoErrors();

    expect($ticket->fresh()->status)->toBe('in_progress');
});

test('superadmin can mark ticket as solved', function () {
    $this->actingAs($this->superadmin);

    $ticket = Ticket::create([
        'subject' => 'Test Ticket',
        'description' => 'Test',
        'status' => 'open',
        'created_by' => $this->admin->id,
        'owner_id' => $this->admin->id,
    ]);

    Livewire::test(\App\Livewire\Tickets\Show::class, ['ticket' => $ticket])
        ->call('markAsSolved')
        ->assertHasNoErrors();

    $ticket->refresh();
    expect($ticket->status)->toBe('solved');
    expect($ticket->solved_at)->not->toBeNull();
});

test('admin cannot update ticket', function () {
    $this->actingAs($this->admin);

    $ticket = Ticket::create([
        'subject' => 'Test Ticket',
        'description' => 'Test',
        'status' => 'open',
        'created_by' => $this->admin->id,
        'owner_id' => $this->admin->id,
    ]);

    $this->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertDontSee('Edit');
});

test('ticket status filter works', function () {
    $this->actingAs($this->admin);

    Ticket::create([
        'subject' => 'Open Ticket',
        'description' => 'Test',
        'status' => 'open',
        'created_by' => $this->admin->id,
        'owner_id' => $this->admin->id,
    ]);

    Ticket::create([
        'subject' => 'Solved Ticket',
        'description' => 'Test',
        'status' => 'solved',
        'created_by' => $this->admin->id,
        'owner_id' => $this->admin->id,
    ]);

    Livewire::test(\App\Livewire\Tickets\Index::class)
        ->set('statusFilter', 'open')
        ->assertSee('Open Ticket')
        ->assertDontSee('Solved Ticket');

    Livewire::test(\App\Livewire\Tickets\Index::class)
        ->set('statusFilter', 'solved')
        ->assertSee('Solved Ticket')
        ->assertDontSee('Open Ticket');
});
