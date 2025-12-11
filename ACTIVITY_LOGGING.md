# Activity Logging System Documentation

## Overview

This project now includes a comprehensive, system-wide activity logging feature that automatically tracks all CRUD operations (Create, Read, Update, Delete) across the entire application. The logging system is lightweight, secure, and maintainable.

## Features

✅ **Automatic Logging**: All model CRUD operations are logged automatically via a trait  
✅ **Security**: Sensitive data (passwords, tokens) is automatically sanitized  
✅ **Performance**: Efficient database indexing for fast queries  
✅ **Audit Trail**: Complete history with old/new values for updates  
✅ **Context**: Captures user, IP address, user agent, and timestamps  
✅ **Extensible**: Easy to add custom logging for special operations  
✅ **Tested**: Comprehensive test suite with 100% passing tests

## Architecture

### 1. Database Table: `activity_logs`

```sql
- id: Primary key
- user_id: Foreign key to users (nullable)
- action: Type of action (created, updated, deleted, custom)
- model_type: Class name of the affected model
- model_id: ID of the affected model
- description: Human-readable description
- old_values: JSON with previous values (for updates)
- new_values: JSON with new values (for creates/updates)
- ip_address: IP address of the request
- user_agent: User agent string
- timestamps: created_at, updated_at
```

### 2. Core Components

#### ActivityLog Model (`app/Models/ActivityLog.php`)
Eloquent model representing activity logs with:
- Relationship to User
- Morph relationship to any logged model
- JSON casting for old_values and new_values

#### ActivityLogger Service (`app/Services/ActivityLogger.php`)
Main service class providing:
- `log()`: Core logging method
- `logCreated()`: Log model creation
- `logUpdated()`: Log model updates
- `logDeleted()`: Log model deletion
- `logCustom()`: Log custom actions
- Automatic sanitization of sensitive fields

#### LogsActivity Trait (`app/Models/Traits/LogsActivity.php`)
Reusable trait that automatically logs model events:
- Hooks into Eloquent's created, updated, deleted events
- Smart detection of whether to log
- Configurable per-model exclusions

## Human-Readable Log Format

The activity logging system automatically generates user-friendly descriptions that are easy to understand:

### Automatic Descriptions
- **Created**: "John Doe created package: Premium Plan"
- **Updated**: "Jane Smith updated router: Main Gateway"
- **Deleted**: "Admin deleted voucher: VOUCHER123"

### Formatted Changes
For updates, the system shows what changed in plain language:
- "Name changed from 'Basic Plan' to 'Premium Plan', Price Monthly changed from '50' to '100'"

### Accessible Attributes
Each log entry provides several human-readable properties:
- `readable_summary`: Complete sentence describing the activity
- `formatted_changes`: Detailed description of what changed
- `time_ago`: "5 minutes ago", "2 hours ago", etc.
- `readable_action`: "created", "updated", "deleted", etc.
- `readable_model_name`: "package", "router", "user", etc.

## Usage

### Automatic Logging (Recommended)

Simply add the `LogsActivity` trait to any model:

```php
use App\Models\Traits\LogsActivity;

class YourModel extends Model
{
    use LogsActivity;
    
    // Your model code...
}
```

That's it! All CRUD operations will be logged automatically.

### Custom Logging

For special operations not covered by the trait:

```php
use App\Services\ActivityLogger;

// Log a custom action
ActivityLogger::logCustom(
    'vouchers_generated',
    null,
    "Generated 100 vouchers in batch B12345",
    [
        'quantity' => 100,
        'batch' => 'B12345',
        'router_id' => 5
    ]
);

// Log with a model reference
ActivityLogger::logCustom(
    'maintenance_performed',
    $router,
    "Performed maintenance on router",
    ['maintenance_type' => 'reboot']
);
```

### Excluding Specific Actions

To exclude certain actions from logging on a specific model:

```php
class YourModel extends Model
{
    use LogsActivity;
    
    // Don't log updates for this model
    protected $excludedActionsFromLog = ['updated'];
}
```

### Logging Without Authentication

By default, logging only happens when a user is authenticated. To allow logging without auth (e.g., in console commands):

```php
class YourModel extends Model
{
    use LogsActivity;
    
    // Allow logging even without authenticated user
    protected $logWithoutAuth = true;
}
```

## Models with Logging Enabled

The following models automatically log all CRUD operations:

**Core Models:**
- User
- Router
- Voucher
- Package
- Invoice
- Ticket
- TicketMessage

**Configuration Models:**
- RadiusServer
- RadiusProfile
- UserProfile
- Zone
- VoucherTemplate
- VoucherBatch
- PaymentGateway
- ResellerRouter

**Content Models:**
- KnowledgebaseArticle
- DocumentationArticle

## Viewing Activity Logs

### Activity Log UI Component

A complete Livewire component is provided to display activity logs in a user-friendly interface:

**Location**: `app/Livewire/ActivityLog/Index.php`

**Features**:
- Real-time search across activities
- Filter by action type (created, updated, deleted, etc.)
- Pagination for large datasets
- Color-coded activity icons
- Timestamps in human-readable format ("5 minutes ago")
- User information and IP tracking

**To add to your routes** (example):
```php
Route::get('/activity-log', \App\Livewire\ActivityLog\Index::class)
    ->name('activity-log.index')
    ->middleware(['auth']);
```

### Displaying Logs in Your Views

```blade
@foreach($logs as $log)
    <div>
        <strong>{{ $log->readable_summary }}</strong>
        <p>{{ $log->formatted_changes }}</p>
        <small>{{ $log->time_ago }} by {{ $log->user->name ?? 'System' }}</small>
    </div>
@endforeach
```

## Querying Logs

### Get all logs for a user
```php
$logs = ActivityLog::where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->get();

// Display them
foreach ($logs as $log) {
    echo $log->readable_summary; // "John created package: Premium Plan"
    echo $log->time_ago; // "5 minutes ago"
}
```

### Get logs for a specific model
```php
$logs = ActivityLog::where('model_type', Package::class)
    ->where('model_id', $packageId)
    ->orderBy('created_at', 'desc')
    ->get();
```

### Get logs for a specific action
```php
$deletions = ActivityLog::where('action', 'deleted')
    ->with('user')
    ->orderBy('created_at', 'desc')
    ->get();
```

### Get recent activity
```php
$recentLogs = ActivityLog::with('user')
    ->orderBy('created_at', 'desc')
    ->limit(50)
    ->get();
```

### Using model relationships
```php
// Get all logs for a specific package
$package = Package::find($id);
$logs = $package->activityLogs()->get();

// Get all actions performed by a user
$user = User::find($id);
$actions = $user->activityLogs()->get();
```

## Security Features

### Automatic Sanitization

The following fields are automatically sanitized (replaced with `[REDACTED]`):
- password
- password_confirmation
- current_password
- remember_token
- two_factor_secret
- two_factor_recovery_codes

### Request Context

Every log entry captures:
- IP address
- User agent
- Timestamp
- Authenticated user (if available)

## Testing

A comprehensive test suite is included in `tests/Feature/ActivityLoggerTest.php`:

```bash
# Run all activity logging tests
php artisan test --filter=ActivityLoggerTest

# Run all tests
php artisan test
```

## Database Maintenance

The `activity_logs` table has efficient indexes on:
- model_type and model_id (compound index)
- user_id
- action
- created_at

For long-term projects, consider implementing a pruning strategy:

```php
// Example: Delete logs older than 1 year
ActivityLog::where('created_at', '<', now()->subYear())->delete();
```

## Performance Considerations

The logging system is designed to be lightweight:
- Uses database transactions for consistency
- Only logs when users are authenticated (by default)
- Efficient indexing for fast queries
- JSON fields for flexible data storage

## Bulk Operations

Bulk operations that bypass Eloquent events are explicitly logged:
- Bulk voucher generation
- Bulk voucher deletion
- Router assignment/unassignment operations

## Future Enhancements

Consider these potential additions:
1. Admin UI to view activity logs
2. Export logs to CSV/Excel
3. Advanced filtering and search
4. Dashboard widgets showing recent activity
5. Email notifications for critical actions
6. Log retention policies

## Troubleshooting

### Logs not being created

1. Check if the model has the `LogsActivity` trait
2. Verify a user is authenticated (unless `logWithoutAuth = true`)
3. Check if the action is excluded via `excludedActionsFromLog`
4. Ensure the `activity_logs` table exists (run migrations)

### Too many logs

1. Add specific actions to `excludedActionsFromLog` for frequently updated models
2. Implement a pruning strategy
3. Consider logging only specific models

### Performance issues

1. Ensure database indexes are in place
2. Consider archiving old logs to a separate table
3. Use database transactions for bulk operations

## Support

For issues or questions about the logging system, please refer to:
- This documentation
- Test suite: `tests/Feature/ActivityLoggerTest.php`
- Source code: `app/Models/ActivityLog.php`, `app/Services/ActivityLogger.php`, `app/Models/Traits/LogsActivity.php`
