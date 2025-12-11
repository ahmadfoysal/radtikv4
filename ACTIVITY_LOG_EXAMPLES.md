# Activity Log Examples

This document shows real examples of how the activity logs appear to users.

## Example Log Entries (Human-Readable Format)

### Creating a Package
```
📦 John Doe created package: Premium Plan
    Name: Premium Plan, Price Monthly: 100, User Limit: 10
    5 minutes ago • John Doe • 192.168.1.1
```

### Updating a Router
```
📝 Jane Smith updated router: Main Gateway
    Name changed from 'Gateway 1' to 'Main Gateway', Port changed from '8728' to '8729'
    2 hours ago • Jane Smith • 192.168.1.5
```

### Deleting a Voucher
```
🗑️ Admin deleted voucher: VOUCHER123
    Deleted: Username: VOUCHER123, Status: active
    1 day ago • Admin • 192.168.1.2
```

### Bulk Operations
```
📦 System generated multiple vouchers
    Generated 100 vouchers in batch B20251209115125ABCD
    3 minutes ago • John Doe • 192.168.1.1

🗑️ System deleted multiple vouchers  
    Bulk deleted 50 vouchers
    1 hour ago • Jane Smith • 192.168.1.5
```

### Router Assignments
```
🔗 Admin assigned routers to reseller
    Assigned 5 router(s) to reseller
    30 minutes ago • Admin • 192.168.1.2

🔗 Admin removed routers from reseller
    Unassigned 2 router(s) from reseller
    45 minutes ago • Admin • 192.168.1.2
```

## Technical Format (Stored in Database)

While the display is user-friendly, the technical data is preserved:

```json
{
  "action": "updated",
  "model_type": "App\\Models\\Package",
  "model_id": 5,
  "description": "Updated package: Premium Plan",
  "old_values": {
    "name": "Basic Plan",
    "price_monthly": "50.00"
  },
  "new_values": {
    "name": "Premium Plan", 
    "price_monthly": "100.00"
  },
  "user_id": 1,
  "ip_address": "192.168.1.1",
  "created_at": "2025-12-09 12:00:00"
}
```

## Using in Your Code

### Display in Dashboard
```php
// Get recent activity
$recentLogs = ActivityLog::with('user')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// In your view
@foreach($recentLogs as $log)
    <div class="activity-item">
        <strong>{{ $log->readable_summary }}</strong>
        @if($log->formatted_changes)
            <p>{{ $log->formatted_changes }}</p>
        @endif
        <small>{{ $log->time_ago }}</small>
    </div>
@endforeach
```

### Display User's Activity History
```php
// Get user's actions
$userActions = ActivityLog::where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->paginate(20);

// Display shows:
// "You created package: Starter Plan - 2 hours ago"
// "You updated router: Office Router - 5 hours ago"
// "You deleted voucher: TEST123 - 1 day ago"
```

### Filter by Action Type
```php
// Get all deletions
$deletions = ActivityLog::where('action', 'deleted')
    ->with('user')
    ->get();

foreach ($deletions as $log) {
    echo $log->readable_summary; 
    // Output: "John deleted package: Old Plan"
}
```

## Color Coding in UI

The UI component uses color coding for quick identification:

- 🟢 **Green**: Created (new items added)
- 🔵 **Blue**: Updated (items modified)
- 🔴 **Red**: Deleted (items removed)
- ⚪ **Gray**: Other actions (bulk operations, assignments)

## Search and Filter

Users can:
- **Search** by activity description or user name
- **Filter** by action type (created, updated, deleted, bulk operations)
- **View details** including IP address and exact timestamp

This makes it easy to:
- Track who made changes
- Audit system modifications
- Debug issues by seeing activity history
- Comply with security requirements
