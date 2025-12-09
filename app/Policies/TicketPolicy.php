<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether the user can view the ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Superadmin can view any ticket
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin/reseller can view their own tickets
        return $ticket->owner_id === $user->id || $ticket->created_by === $user->id;
    }

    /**
     * Determine whether the user can update the ticket.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Only superadmin can update tickets
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the ticket.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // Only superadmin can delete tickets
        return $user->isSuperAdmin();
    }
}
