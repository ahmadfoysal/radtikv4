# Voucher Logging System Documentation

## Overview

The Voucher Logging System is a comprehensive tracking solution implemented in RADTik v4 that captures and preserves snapshot data of voucher operations. This system is crucial for billing, accounting, and audit purposes, maintaining historical records even after vouchers or routers are deleted.

## System Architecture

### Core Components

1. **VoucherLog Model** (`app/Models/VoucherLog.php`)
2. **VoucherLogger Service** (`app/Services/VoucherLogger.php`)
3. **Database Migration** (`database/migrations/2025_10_25_095737_create_voucher_logs_table.php`)
4. **Livewire Component** (`app/Livewire/VoucherLogs/Index.php`)
5. **View Template** (`resources/views/livewire/voucher-logs/index.blade.php`)

---

## Database Schema

### Table: `voucher_logs`

```sql
CREATE TABLE voucher_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,

    -- Foreign Keys (nullable with ON DELETE SET NULL)
    user_id BIGINT NULLABLE,
    voucher_id BIGINT NULLABLE,
    router_id BIGINT NULLABLE,

    -- Event Information
    event_type VARCHAR(255),

    -- Snapshot Fields (preserved even after deletion)
    username VARCHAR(255) NULLABLE,
    profile VARCHAR(255) NULLABLE,
    price DECIMAL(12, 2) NULLABLE,
    validity_days INT NULLABLE,
    router_name VARCHAR(255) NULLABLE,

    -- Metadata
    meta JSON NULLABLE,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX(created_at)  -- For pruning queries
);
```

### Key Design Decisions

1. **Nullable Foreign Keys**: All foreign keys are nullable to preserve log integrity when parent records are deleted
2. **Snapshot Fields**: Critical data is duplicated as direct columns (not relying on relationships) to survive deletions
3. **Cascading Strategy**: Uses `nullOnDelete()` instead of `cascadeOnDelete()` to maintain historical records
4. **JSON Metadata**: Flexible `meta` field stores additional context (IP addresses, batch info, deletion reasons, etc.)

---

## VoucherLogger Service

### Purpose

Centralized service for logging voucher-related events with automatic snapshot data capture.

### Implementation

```php
namespace App\Services;

class VoucherLogger
{
    /**
     * Log a voucher event with snapshot data.
     *
     * @param Voucher|null $voucher The voucher (nullable for deleted vouchers)
     * @param Router|null $router The associated router (nullable)
     * @param string $eventType Event type: 'activated', 'deleted', 'expired', 'synced'
     * @param array $extra Additional metadata
     * @return VoucherLog The created log entry
     */
    public static function log(
        ?Voucher $voucher,
        ?Router $router,
        string $eventType,
        array $extra = []
    ): VoucherLog;
}
```

### Features

1. **Automatic Snapshot Capture**: Extracts and stores profile data (name, price, validity) at the moment of logging
2. **Null-Safe**: Handles cases where voucher or router may already be deleted
3. **Metadata Support**: Accepts arbitrary additional data via `$extra` parameter
4. **User Tracking**: Automatically captures the authenticated user ID

### Usage Examples

```php
// Log voucher activation
VoucherLogger::log(
    $voucher,
    $router,
    'activated',
    ['ip' => request()->ip()]
);

// Log voucher deletion with context
VoucherLogger::log(
    $voucher,
    $voucher->router,
    'deleted',
    [
        'deleted_by' => auth()->id(),
        'batch' => $voucher->batch,
        'status' => $voucher->status,
    ]
);

// Log expiration (voucher may be null)
VoucherLogger::log(
    null,
    $router,
    'expired',
    ['reason' => 'auto-cleanup']
);
```

---

## Event Types

The system tracks the following event types:

| Event Type  | Description                | When Triggered                              |
| ----------- | -------------------------- | ------------------------------------------- |
| `activated` | Voucher activated by user  | When voucher is used/activated on router    |
| `deleted`   | Voucher removed            | Voucher model deletion (via Eloquent event) |
| `expired`   | Voucher expired            | Automated cleanup or manual expiration      |
| `synced`    | Voucher synced with router | Synchronization operations                  |

---

## Model Integration

### VoucherLog Model

```php
namespace App\Models;

class VoucherLog extends Model
{
    use Prunable;

    protected $casts = [
        'meta' => 'array',
    ];

    // Relationships
    public function user() { return $this->belongsTo(User::class); }
    public function voucher() { return $this->belongsTo(Voucher::class); }
    public function router() { return $this->belongsTo(Router::class); }

    // Auto-pruning: Logs older than 6 months are automatically deleted
    public function prunable()
    {
        return static::where('created_at', '<', now()->subMonths(6));
    }
}
```

### Voucher Model Integration

The logging is automatically triggered via Eloquent model events:

```php
// app/Models/Voucher.php
protected static function booted()
{
    static::deleted(function ($voucher) {
        VoucherLogger::log(
            $voucher,
            $voucher->router,
            'deleted',
            [
                'deleted_by' => auth()->id(),
                'batch' => $voucher->batch,
                'status' => $voucher->status,
            ]
        );
    });
}
```

### Related Model Relationships

```php
// User Model
public function voucherLogs()
{
    return $this->hasMany(VoucherLog::class);
}

// Router Model
public function voucherLogs()
{
    return $this->hasMany(VoucherLog::class);
}

// Voucher Model
public function logs()
{
    return $this->hasMany(VoucherLog::class);
}
```

---

## User Interface

### Voucher Logs Index Page

**Route**: `/vouchers/logs`  
**Component**: `App\Livewire\VoucherLogs\Index`  
**Permission**: `view_vouchers`

### Features

1. **Advanced Filtering**:

    - Router selection (scoped to user's accessible routers)
    - Event type filter (All, Activated, Deleted)
    - Date range filter (from/to dates, defaults to today)
    - Full-text search (username, router name, profile name)

2. **Data Display**:

    - Date/Time with formatted display
    - Event type badges (color-coded)
    - Username and profile information
    - Router name (clickable link if router still exists)
    - Price in BDT (৳) currency format
    - Validity in days
    - Expandable metadata details

3. **Pagination**: 25 records per page

4. **Real-time Updates**: Livewire reactive filtering without page reload

### UI Implementation

```blade
{{-- Filter Controls --}}
<x-mary-card>
    <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-5">
        <x-mary-select label="Router" wire:model.live="router_id" />
        <x-mary-select label="Event Type" wire:model.live="event_type" />
        <x-mary-input label="From Date" type="date" wire:model.live="from_date" />
        <x-mary-input label="To Date" type="date" wire:model.live="to_date" />
        <x-mary-input label="Search" wire:model.live.debounce.500ms="search" />
    </div>
</x-mary-card>

{{-- Logs Table --}}
<table class="table table-sm table-zebra">
    <thead>
        <tr>
            <th>Date/Time</th>
            <th>Event</th>
            <th>Username</th>
            <th>Profile</th>
            <th>Router</th>
            <th>Price</th>
            <th>Validity</th>
            <th>Details</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $log)
            <tr>
                <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                <td>
                    <span class="badge badge-{{ $log->event_type === 'activated' ? 'success' : 'error' }}">
                        {{ ucfirst($log->event_type) }}
                    </span>
                </td>
                <td>{{ $log->username }}</td>
                <td>{{ $log->profile }}</td>
                <td>{{ $log->router_name }}</td>
                <td>৳{{ number_format($log->price, 2) }}</td>
                <td>{{ $log->validity_days }} days</td>
                <td>
                    @if($log->meta)
                        <button wire:click="showDetails({{ json_encode($log->meta) }})">
                            View
                        </button>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
```

---

## Integration with Dashboard

The voucher logging system powers the admin billing dashboard with real-time financial metrics:

```php
// app/Livewire/Dashboard.php

$voucherLogQuery = DB::table('voucher_logs')
    ->where('event_type', 'activated')
    ->where('user_id', $user->id);

// Today's Income
$todayIncome = (clone $voucherLogQuery)
    ->whereDate('created_at', today())
    ->sum('price');

// Monthly Income
$monthIncome = (clone $voucherLogQuery)
    ->whereMonth('created_at', now()->month)
    ->whereYear('created_at', now()->year)
    ->sum('price');

// Income by Profile (for pie chart)
$incomeByProfile = (clone $voucherLogQuery)
    ->selectRaw('profile, SUM(price) as total_income, COUNT(*) as activation_count')
    ->groupBy('profile')
    ->get();
```

### Dashboard Metrics Powered by Voucher Logs

1. **Today's Income**: Sum of activation prices from today
2. **Monthly Income**: Sum of activation prices this month
3. **Activation Count**: Count of voucher activations
4. **Income Trend Chart**: Daily income from voucher activations (line chart)
5. **Activation Trend Chart**: Daily activation count (bar chart)
6. **Income by Profile Chart**: Distribution by profile (pie chart)
7. **Top Selling Profiles**: Ranked by total income
8. **Top Routers by Income**: Ranked by activation revenue
9. **Recent Activations**: Latest 10 activation logs

---

## Testing

### Test Coverage

The system includes comprehensive unit tests (`tests/Feature/VoucherLoggerTest.php`):

1. **test_log_creates_voucher_log_with_snapshot_data**: Verifies complete snapshot capture
2. **test_log_handles_null_voucher**: Tests logging when voucher is deleted
3. **test_log_handles_null_router**: Tests logging when router is deleted
4. **test_log_records_different_event_types**: Validates all event types
5. **test_voucher_log_belongs_to_user**: Tests user relationship
6. **test_voucher_log_belongs_to_voucher**: Tests voucher relationship
7. **test_voucher_log_belongs_to_router**: Tests router relationship
8. **test_prunable_returns_old_logs**: Validates auto-pruning logic

### Running Tests

```bash
php artisan test --filter=VoucherLogger
```

---

## Data Retention & Pruning

### Automatic Cleanup

The `VoucherLog` model uses Laravel's `Prunable` trait for automatic data retention:

```php
public function prunable()
{
    return static::where('created_at', '<', now()->subMonths(6));
}
```

### Running Pruning

```bash
# Manual pruning
php artisan model:prune

# Schedule in app/Console/Kernel.php (recommended)
$schedule->command('model:prune')->daily();
```

**Policy**: Logs older than **6 months** are automatically deleted to manage database size.

---

## Security & Access Control

### Permission System

-   **Required Permission**: `view_vouchers`
-   **Data Scoping**: Users only see logs from routers they have access to
-   **Admin Hierarchy**: Admins see their logs + their resellers' logs
-   **Super Admin**: Full system access

### Query Scoping Example

```php
// app/Livewire/VoucherLogs/Index.php

$accessibleRouterIds = auth()->user()
    ->getAccessibleRouters()
    ->pluck('id')
    ->toArray();

$query = VoucherLog::query()
    ->whereIn('router_id', $accessibleRouterIds)
    ->orderByDesc('created_at');
```

---

## Use Cases

### 1. Financial Reporting

-   Calculate daily/monthly revenue from voucher activations
-   Track income by profile type
-   Compare router performance by revenue
-   Generate billing reports for resellers

### 2. Audit Trail

-   Track who deleted which vouchers and when
-   Investigate suspicious deletion patterns
-   Verify voucher activation history
-   Compliance and record-keeping

### 3. Business Intelligence

-   Identify top-selling profiles
-   Analyze peak activation times
-   Monitor router utilization
-   Track reseller performance

### 4. Customer Support

-   Verify customer purchase history
-   Check voucher activation status
-   Provide proof of service delivery
-   Resolve billing disputes

---

## Benefits

### 1. Data Persistence

-   **Survives Deletions**: Snapshot approach preserves data even when vouchers/routers are deleted
-   **Complete History**: Full audit trail of all voucher operations
-   **Reference Integrity**: Logs remain valid with readable data

### 2. Performance

-   **Denormalized Design**: No complex joins required for reporting
-   **Indexed Queries**: Fast filtering on `created_at` for time-based reports
-   **Efficient Storage**: Auto-pruning prevents unlimited growth

### 3. Flexibility

-   **Extensible Metadata**: JSON `meta` field accommodates custom data per event type
-   **Multiple Event Types**: Supports various voucher lifecycle events
-   **Easy Integration**: Simple service API for logging from any part of the application

### 4. Business Value

-   **Revenue Tracking**: Accurate income calculation from activation logs
-   **Accountability**: Clear record of who performed actions and when
-   **Analytics Ready**: Structured data for reporting and dashboards
-   **Compliance**: Meets audit and record-keeping requirements

---

## Future Enhancements

### Potential Improvements

1. **Export Functionality**

    - CSV/Excel export with filters
    - PDF report generation
    - Scheduled email reports

2. **Advanced Analytics**

    - Revenue forecasting
    - Churn analysis
    - Customer lifetime value calculations
    - Seasonal trend detection

3. **Real-time Notifications**

    - Alert on unusual deletion patterns
    - Revenue milestone notifications
    - Daily/weekly digest emails

4. **Enhanced Metadata**

    - Device fingerprinting
    - Geolocation data
    - Browser/OS information
    - Session duration tracking

5. **API Access**
    - RESTful API endpoints for logs
    - Webhook support for external integrations
    - Third-party reporting tools integration

---

## Implementation Timeline

The voucher logging system was implemented in the following phases:

1. **Database Schema Design** (Oct 25, 2025)

    - Created migration with snapshot approach
    - Designed nullable foreign keys strategy
    - Added pruning index

2. **Service Layer** (Oct 25, 2025)

    - Implemented VoucherLogger service
    - Created model with relationships
    - Added auto-pruning logic

3. **Model Integration** (Oct 25, 2025)

    - Added Eloquent event listeners
    - Integrated with Voucher deletion
    - Created relationship methods

4. **User Interface** (Oct 26, 2025)

    - Built Livewire index component
    - Implemented advanced filtering
    - Created responsive table view

5. **Dashboard Integration** (Dec 20, 2025)

    - Integrated with admin dashboard
    - Built financial metrics
    - Created billing charts

6. **Testing & Documentation** (Dec 20, 2025)
    - Comprehensive unit tests
    - Full test coverage
    - Documentation completion

---

## Conclusion

The Voucher Logging System provides RADTik v4 with a robust, scalable solution for tracking voucher operations. By capturing snapshot data at the moment of each event, the system ensures historical integrity regardless of future changes to vouchers, routers, or profiles. This architecture supports accurate billing, comprehensive auditing, and valuable business intelligence while maintaining optimal performance through denormalized storage and automatic data retention policies.

The system's design prioritizes:

-   **Data Integrity**: Logs remain valid even after deletions
-   **Performance**: Efficient queries without complex joins
-   **Flexibility**: JSON metadata for extensibility
-   **Usability**: Rich filtering and intuitive UI
-   **Scalability**: Auto-pruning prevents unlimited growth

This foundation enables RADTik v4 to provide accurate financial tracking, comprehensive audit trails, and actionable business insights for WiFi hotspot service providers.
