<?php

namespace App\Livewire\Tickets;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Rule as V;
use Livewire\Component;
use Mary\Traits\Toast;

class Show extends Component
{
    use AuthorizesRequests, Toast;

    public Ticket $ticket;

    public bool $editMode = false;

    #[V(['required', 'string', 'in:open,in_progress,solved,closed'])]
    public string $status = '';

    #[V(['nullable', 'exists:users,id'])]
    public ?int $assigned_to = null;

    public string $messageText = '';

    public function mount(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $this->ticket = $ticket;
        $this->status = $ticket->status;
        $this->assigned_to = $ticket->assigned_to;

        // Load messages with user information once on mount
        $this->ticket->load('messages.user');
    }

    public function toggleEditMode()
    {
        $this->authorize('update', $this->ticket);
        $this->editMode = ! $this->editMode;
    }

    public function updateTicket()
    {
        $this->authorize('update', $this->ticket);
        
        // Validate only ticket-related fields
        $this->validate([
            'status' => 'required|string|in:open,in_progress,solved,closed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $updates = [
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
        ];

        // Set solved_at timestamp if status changed to solved
        if ($this->status === 'solved' && ! $this->ticket->isSolved()) {
            $updates['solved_at'] = now();
        }

        // Set closed_at timestamp if status changed to closed
        if ($this->status === 'closed' && ! $this->ticket->isClosed()) {
            $updates['closed_at'] = now();
        }

        $this->ticket->update($updates);

        $this->success('Ticket updated successfully.');
        $this->editMode = false;
    }

    public function markAsSolved()
    {
        $this->authorize('update', $this->ticket);

        $this->ticket->update([
            'status' => 'solved',
            'solved_at' => now(),
        ]);

        $this->status = 'solved';
        $this->success('Ticket marked as solved.');
    }

    public function changeStatus($newStatus)
    {
        $this->authorize('update', $this->ticket);
        
        $this->validate([
            'status' => 'required|string|in:open,in_progress,solved,closed',
        ]);

        $updates = ['status' => $newStatus];

        // Set solved_at timestamp if status changed to solved
        if ($newStatus === 'solved' && ! $this->ticket->isSolved()) {
            $updates['solved_at'] = now();
        }

        // Set closed_at timestamp if status changed to closed
        if ($newStatus === 'closed' && ! $this->ticket->isClosed()) {
            $updates['closed_at'] = now();
        }

        $this->ticket->update($updates);
        $this->status = $newStatus;

        $this->success('Ticket status updated successfully.');
    }

    public function deleteTicket()
    {
        $this->authorize('delete', $this->ticket);
        
        $this->ticket->delete();
        
        $this->success('Ticket deleted successfully.');
        return redirect()->route('tickets.index');
    }

    public function sendMessage()
    {
        $user = auth()->user();

        // Authorization: only superadmin or ticket owner/creator can send messages
        if (! $user->isSuperAdmin() && $this->ticket->owner_id !== $user->id && $this->ticket->created_by !== $user->id) {
            abort(403, 'Unauthorized to send messages on this ticket.');
        }

        // Validate only message field
        $validated = $this->validate([
            'messageText' => 'required|string|min:1',
        ]);

        $this->ticket->messages()->create([
            'user_id' => $user->id,
            'message' => $this->messageText,
        ]);

        $this->messageText = '';
        $this->success('Message sent successfully.');

        // Refresh ticket with messages to update the view
        $this->ticket->load('messages.user');

        // Emit event for auto-scroll
        $this->dispatch('messageSent');
    }

    public function render()
    {
        $user = auth()->user();

        // Get users for assignee dropdown (superadmin only)
        // Limit to 100 users to avoid performance issues
        $users = [];
        if ($user->isSuperAdmin()) {
            $users = User::orderBy('name')->limit(100)->get(['id', 'name']);
        }

        return view('livewire.tickets.show', [
            'users' => $users,
        ]);
    }
}
