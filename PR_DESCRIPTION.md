# Add Subscription Expiry Check Middleware to Mikrotik API Routes

## Problem Statement

Mikrotik routers with expired subscriptions were able to continue syncing data through API endpoints. This allowed unauthorized access after subscription ends, which is a security and business logic concern.

## Solution Overview

Implemented a middleware-based approach to validate router subscription status before allowing access to any Mikrotik API endpoints. This ensures that only routers with valid, non-expired subscriptions can sync data with the system.

## Changes Made

### 1. CheckRouterSubscription Middleware

**File:** `app/Http/Middleware/CheckRouterSubscription.php`

Created a new middleware that:
- Extracts the `token` query parameter from incoming requests
- Validates the token against the router's `app_key` field in the database
- Checks if the router has an active package with subscription data
- Verifies the `end_date` field in the package JSON to ensure subscription hasn't expired
- Returns appropriate 403 JSON error responses for:
  - Missing token
  - Invalid token
  - No active subscription/package
  - Expired subscription
- Allows the request to proceed to the controller if all checks pass

**Validation Logic:**
```php
// Check if package exists and has end_date
if (!$router->package || !isset($router->package['end_date'])) {
    return 403 error: "No active subscription"
}

// Check if subscription is expired
$endDate = Carbon::parse($router->package['end_date']);
if ($endDate->isPast()) {
    return 403 error: "Subscription expired"
}

// Allow request to proceed
return $next($request);
```

### 2. Middleware Registration

**File:** `bootstrap/app.php`

Registered the middleware with an alias `check.router.subscription` for easy reference in routes:
```php
$middleware->alias([
    'check.router.subscription' => \App\Http\Middleware\CheckRouterSubscription::class,
]);
```

### 3. Applied Middleware to All Mikrotik API Routes

**File:** `routes/web.php`

Applied the middleware to all 6 Mikrotik API endpoints:
- `GET /mikrotik/api/pull-inactive-users` - Pull new/inactive vouchers
- `GET /mikrotik/api/pull-active-users` - Pull active users for restoration
- `POST /mikrotik/api/push-active-users` - Receive usage data from router
- `GET /mikrotik/api/sync-orphans` - Clean up orphaned users
- `GET /mikrotik/api/pull-profiles` - Pull user profiles
- `GET /mikrotik/api/pull-updated-profiles` - Pull recently updated profiles

### 4. Added Missing Controller Method

**File:** `app/Http/Controllers/Api/MikrotikApiController.php`

Implemented the `pullUpdatedProfiles()` method that was referenced in routes but missing from the controller:

**Features:**
- Accepts optional `since` query parameter to filter profiles by update timestamp
- Returns profiles in two formats:
  - `format=flat`: Plain text format (name;shared_users;rate_limit)
  - Default: JSON format with full profile details
- Includes the same authentication logic as other endpoints
- Handles date parsing errors gracefully

**Example Usage:**
```
GET /mikrotik/api/pull-updated-profiles?token=xxx&since=2025-12-01
GET /mikrotik/api/pull-updated-profiles?token=xxx&format=flat
```

### 5. Testing Infrastructure

#### Router Factory
**File:** `database/factories/RouterFactory.php`

Created a factory for generating test router data:
- Generates realistic router attributes (name, IP addresses, ports)
- Encrypts password using Laravel's Crypt facade
- Generates unique `app_key` for each router
- Supports custom package data for subscription testing

#### Router Model Enhancement
**File:** `app/Models/Router.php`

Added `HasFactory` trait to enable factory usage in tests.

#### Comprehensive Test Suite
**File:** `tests/Feature/CheckRouterSubscriptionMiddlewareTest.php`

Created 11 test cases covering all scenarios:

**Token Validation Tests:**
- ✅ Blocks requests with no token
- ✅ Blocks requests with invalid token

**Subscription Validation Tests:**
- ✅ Blocks requests when router has no package
- ✅ Blocks requests when package has no end_date
- ✅ Blocks requests when subscription is expired
- ✅ Allows requests when subscription is valid

**Route Coverage Tests:**
- ✅ Middleware applies to `pullActiveUsers`
- ✅ Middleware applies to `pushActiveUsers`
- ✅ Middleware applies to `syncOrphans`
- ✅ Middleware applies to `pullProfiles`
- ✅ Middleware applies to `pullUpdatedProfiles`

## Technical Details

### Package Data Structure

The router's `package` field is a JSON column containing subscription information:
```json
{
  "id": 1,
  "name": "Basic Plan",
  "end_date": "2025-12-31 23:59:59",
  "start_date": "2025-01-01 00:00:00",
  "price": 50.00,
  ...
}
```

The middleware specifically checks the `end_date` field to determine subscription validity.

### Execution Flow

```
1. Request arrives at Mikrotik API endpoint
2. CheckRouterSubscription middleware executes
   ├─> Extract token from query parameter
   ├─> Find router by app_key
   ├─> Validate package exists
   ├─> Check end_date not expired
   └─> Return 403 if any check fails
3. If middleware passes, controller action executes
4. Controller performs its own token validation (preserved)
5. Controller returns response
```

### Error Responses

The middleware returns standardized JSON error responses:

```json
// No token provided
{"error": "Token is required"}

// Invalid token
{"error": "Invalid token"}

// No active subscription
{"error": "No active subscription"}

// Expired subscription
{"error": "Subscription expired"}
```

## Benefits

1. **Security**: Prevents expired routers from accessing API endpoints
2. **Business Logic**: Enforces subscription requirements consistently
3. **Minimal Changes**: No modification to existing controller logic
4. **Centralized**: Single middleware handles all subscription checks
5. **Testable**: Comprehensive test coverage ensures reliability
6. **Maintainable**: Clear separation of concerns between authentication and subscription validation

## Testing Results

All tests pass successfully:
```
✅ 11/11 new middleware tests passing
✅ 26/26 existing subscription tests passing
✅ No breaking changes to existing functionality
✅ Middleware properly attached to all routes (verified)
✅ No security vulnerabilities detected
```

## Verification Steps

To verify the middleware is working:

1. **List routes with middleware:**
   ```bash
   php artisan route:list --path=mikrotik -v
   ```

2. **Run middleware tests:**
   ```bash
   php artisan test --filter=CheckRouterSubscriptionMiddlewareTest
   ```

3. **Test with expired router:**
   ```bash
   # Create router with expired subscription in tinker/test
   # Make request to any Mikrotik API endpoint
   # Should receive 403 with "Subscription expired" message
   ```

## Migration Notes

No database migrations required. The middleware uses existing data structures:
- `routers.app_key` - For token validation
- `routers.package` - JSON field containing subscription data

## Future Considerations

- Consider adding logging for blocked subscription attempts
- Add metrics/monitoring for subscription expiry events
- Consider adding grace period before hard blocking
- Add admin notification when routers are blocked due to expiry

## Files Changed

1. `app/Http/Middleware/CheckRouterSubscription.php` - New middleware (51 lines)
2. `app/Http/Controllers/Api/MikrotikApiController.php` - Added pullUpdatedProfiles method (57 lines)
3. `app/Models/Router.php` - Added HasFactory trait (2 lines)
4. `bootstrap/app.php` - Registered middleware alias (4 lines)
5. `database/factories/RouterFactory.php` - New factory (42 lines)
6. `routes/web.php` - Applied middleware to 6 routes (12 lines modified)
7. `tests/Feature/CheckRouterSubscriptionMiddlewareTest.php` - New test suite (186 lines)

**Total:** 7 files changed, 347 insertions(+), 7 deletions(-)
