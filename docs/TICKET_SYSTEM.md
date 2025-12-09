# Support Ticket System Documentation

## Overview

The support ticket system allows users to create and manage support requests. Different user roles have different levels of access and permissions.

## User Roles and Permissions

### Admin & Reseller
- Can create tickets for themselves
- Can view their own tickets (tickets they own or created)
- Cannot update or delete tickets
- Cannot assign tickets

### Superadmin
- Can view all tickets in the system
- Can create tickets on behalf of any user
- Can update ticket status (open → in_progress → solved → closed)
- Can assign tickets to any user
- Can mark tickets as solved
- Can delete tickets

## Database Schema

### Tickets Table

| Column      | Type      | Description                                    |
|-------------|-----------|------------------------------------------------|
| id          | bigint    | Primary key                                    |
| subject     | string    | Ticket subject/title                           |
| description | text      | Detailed description of the issue              |
| status      | string    | Current status (open, in_progress, solved, closed) |
| priority    | string    | Priority level (low, normal, high)             |
| created_by  | foreignId | User who created the ticket                    |
| owner_id    | foreignId | User who owns the ticket                       |
| assigned_to | foreignId | User assigned to handle the ticket (nullable)  |
| closed_at   | datetime  | When the ticket was closed (nullable)          |
| solved_at   | datetime  | When the ticket was solved (nullable)          |
| timestamps  | datetime  | created_at and updated_at                      |

## Routes

- `GET /tickets` - List all tickets (with filters)
- `GET /tickets/{ticket}` - View specific ticket details

## Components

### App\Livewire\Tickets\Index
Lists tickets and provides a creation form.

**Features:**
- Filter by status (All, Open, In Progress, Solved, Closed)
- Pagination
- Create new ticket modal
- Role-based ticket filtering (admins/resellers see only their tickets, superadmin sees all)

**Properties:**
- `statusFilter`: Current status filter
- `showCreateModal`: Modal visibility state
- `subject`, `description`, `priority`: Form fields
- `owner_id`, `assigned_to`: Assignment fields (superadmin only)

### App\Livewire\Tickets\Show
Displays ticket details and allows management (superadmin only).

**Features:**
- View all ticket information
- Edit mode for superadmin
- Update status
- Assign to user
- Quick "Mark as Solved" action

## Model

### App\Models\Ticket

**Relationships:**
- `creator()`: User who created the ticket
- `owner()`: User who owns the ticket
- `assignee()`: User assigned to handle the ticket

**Helper Methods:**
- `isSolved()`: Check if ticket is solved
- `isOpen()`: Check if ticket is open
- `isClosed()`: Check if ticket is closed
- `isInProgress()`: Check if ticket is in progress

## Authorization

Authorization is handled through `App\Policies\TicketPolicy`.

**Policy Methods:**
- `view()`: Admins can view their own tickets, superadmins can view all
- `update()`: Only superadmins can update tickets
- `delete()`: Only superadmins can delete tickets

## Usage Examples

### Creating a Ticket (Admin/Reseller)

1. Navigate to `/tickets`
2. Click "New Ticket" button
3. Fill in:
   - Subject
   - Description
   - Priority (optional)
4. Click "Create Ticket"

### Creating a Ticket on Behalf of Another User (Superadmin)

1. Navigate to `/tickets`
2. Click "New Ticket" button
3. Fill in:
   - Subject
   - Description
   - Priority
   - Ticket Owner (select user)
   - Assign To (optional)
4. Click "Create Ticket"

### Managing a Ticket (Superadmin)

1. Navigate to `/tickets`
2. Click on a ticket to view details
3. Click "Edit" to enter edit mode
4. Update:
   - Status
   - Assigned user
5. Click "Save Changes"

Or use the quick "Mark as Solved" button to immediately solve the ticket.

## Status Flow

```
open → in_progress → solved → closed
```

- **open**: Initial state when ticket is created
- **in_progress**: Superadmin is working on the ticket
- **solved**: Issue has been resolved
- **closed**: Ticket is closed (no further action)

## Design Patterns

The ticket system follows the existing application patterns:

- **MaryUI Components**: Uses x-mary-* components for consistency
- **Tailwind CSS**: Follows the existing color and spacing conventions
- **Livewire**: Uses Livewire 3 for reactive components
- **Authorization**: Laravel Policies for role-based access control
- **Toast Notifications**: Uses Mary\Traits\Toast for user feedback

## Future Enhancements

Possible improvements:
- Real-time chat/comments on tickets
- File attachments
- Email notifications
- Ticket categories/tags
- Service Level Agreement (SLA) tracking
- Ticket templates
- Search functionality
- Advanced filters (by owner, assignee, date range)
