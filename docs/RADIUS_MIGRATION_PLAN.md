# RADIUS Migration Plan - RADTik v4

## Executive Summary

**Current Problem**: Direct MikroTik API script installation is synchronous, time-consuming, and prone to timeout failures (405 errors). Large script installations can take 60+ seconds, exceeding HTTP request limits.

**Proposed Solution**: Migrate to a RADIUS-based architecture where:
- RADIUS servers handle authentication/authorization independently
- Main application acts as central data store
- RADIUS servers pull/push data via RESTful API
- MikroTik routers communicate with RADIUS servers (fast, native protocol)
- Eliminates long-running API operations entirely

---

## Architecture Overview

### Current Architecture (Problems)
```
User (Browser) → Livewire Component → MikroTik API → Router
                     ↓ (60+ seconds)
                 HTTP Timeout (405)
```

**Issues:**
- Synchronous HTTP operations timeout
- Script installation unreliable
- Router synchronization failures
- Poor scalability (one-to-one connection)
- Network latency affects all operations

### Proposed Architecture (Solution)
```
┌─────────────────────────────────────────────────────────┐
│                   RADTik Application                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │   Database   │  │  REST API    │  │   Livewire   │ │
│  │ (Vouchers,   │←→│  (Protected) │←→│     UI       │ │
│  │  Routers,    │  │              │  │              │ │
│  │  RADIUS)     │  │              │  │              │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────┬───────────────────────────┘
                              │ HTTPS API Calls
                              │ (Pull/Push Data)
                              ↓
┌─────────────────────────────────────────────────────────┐
│              RADIUS Server (FreeRADIUS)                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │   Memory     │  │  API Client  │  │   RADIUS     │ │
│  │   Cache      │←→│  (Sync Data) │  │   Service    │ │
│  │ (Hot Data)   │  │              │  │   (Port 1812)│ │
│  └──────────────┘  └──────────────┘  └──────┬───────┘ │
└─────────────────────────────────────────────┼───────────┘
                                              │ RADIUS Protocol
                                              │ (Fast, Native)
                                              ↓
                              ┌───────────────────────────┐
                              │   MikroTik Routers        │
                              │  (No scripts needed)      │
                              │  (Native RADIUS support)  │
                              └───────────────────────────┘
```

---

## Benefits Analysis

### Technical Benefits
1. **No More Timeouts**: RADIUS authentication happens in milliseconds, not minutes
2. **Native Protocol**: MikroTik has built-in RADIUS support (no custom scripts)
3. **Horizontal Scaling**: Deploy multiple RADIUS servers for load distribution
4. **Fault Tolerance**: If one RADIUS server fails, routers can failover to backup
5. **Regional Distribution**: Place RADIUS servers close to router clusters (low latency)
6. **Separation of Concerns**: Authentication logic separated from application logic

### Operational Benefits
1. **Simplified Router Setup**: Just configure RADIUS IP/port/secret (30 seconds)
2. **No Script Installation**: Eliminates 60+ second installation process
3. **Real-time Updates**: Voucher changes reflected immediately via cache invalidation
4. **Better Monitoring**: RADIUS logs provide detailed authentication audit trail
5. **Standard Protocol**: Industry-standard AAA (Authentication, Authorization, Accounting)

### Business Benefits
1. **Multi-Tenant Ready**: Each reseller can have dedicated RADIUS server(s)
2. **White-Label Capable**: Resellers can brand/host their own RADIUS servers
3. **Performance SLA**: Guarantee sub-second authentication response times
4. **Cost Optimization**: Reduce MikroTik API calls, lower bandwidth usage

---

## Database Schema Design

### New Tables

#### `radius_servers`
```
id                  - Primary Key (ULID/UUID)
user_id             - Foreign Key → users.id (owner)
name                - string(100) - Display name
host                - string(255) - IP/domain
port                - integer(default: 1812) - RADIUS auth port
accounting_port     - integer(default: 1813) - RADIUS accounting port
secret              - text(encrypted) - Shared secret
api_token           - text(encrypted) - Token for server to call app API
provider            - enum('freeradius', 'radiusd', 'custom')
status              - enum('active', 'inactive', 'maintenance')
last_sync_at        - timestamp - Last successful data sync
health_check_url    - string(255) - Endpoint to check server health
max_routers         - integer(nullable) - Limit routers per server
region              - string(50, nullable) - Geographic region
notes               - text(nullable)
settings            - json - Additional config (caching strategy, sync interval)
created_at          - timestamp
updated_at          - timestamp
deleted_at          - timestamp(soft delete)

Indexes:
- user_id
- status
- host + port (unique)

Constraints:
- user_id cascades on delete
- api_token must be unique
```

#### Update `routers` table
```
Add columns:
radius_server_id    - Foreign Key → radius_servers.id (nullable for migration)
radius_nas_secret   - text(encrypted) - NAS (Network Access Server) secret
radius_enabled      - boolean(default: false)
radius_configured_at- timestamp(nullable)
fallback_method     - enum('local', 'api', 'deny') - If RADIUS fails

Indexes:
- radius_server_id

Constraints:
- radius_server_id cascades set null on delete
```

#### `radius_sync_logs`
```
id                  - Primary Key
radius_server_id    - Foreign Key → radius_servers.id
sync_type           - enum('full', 'incremental', 'voucher_only')
direction           - enum('pull', 'push')
records_affected    - integer
status              - enum('success', 'failed', 'partial')
error_message       - text(nullable)
started_at          - timestamp
completed_at        - timestamp
duration_ms         - integer - Execution time
initiated_by        - enum('system', 'manual', 'webhook')

Indexes:
- radius_server_id + started_at
- status
```

#### `radius_authentication_logs` (optional, for audit)
```
id                  - Primary Key
radius_server_id    - Foreign Key → radius_servers.id
router_id           - Foreign Key → routers.id
username            - string(255)
status              - enum('accept', 'reject', 'challenge')
response_time_ms    - integer
authenticated_at    - timestamp
ip_address          - string(45) - Client IP
mac_address         - string(17, nullable)

Indexes:
- radius_server_id + authenticated_at
- username
- router_id
- Partition by month (for performance)
```

---

## RADIUS Server Management Flow

### 1. RADIUS Server Creation
**Actor**: Admin/Reseller with `manage_radius_servers` permission

**Steps**:
1. User navigates to "RADIUS Servers" section
2. Fills form:
   - Name (e.g., "US-East-1 RADIUS")
   - Host (IP or domain)
   - Port (default 1812)
   - Provider (FreeRADIUS recommended)
   - Region (optional, for organization)
   - Max Routers (optional, to prevent overload)
3. System generates:
   - Strong random `secret` (32-char alphanumeric)
   - Unique `api_token` (Laravel Sanctum token)
4. System encrypts sensitive fields using `Crypt::encrypt()`
5. System performs health check (optional):
   - Ping host
   - Try test authentication (if server already running)
6. Save to `radius_servers` table
7. Display setup instructions:
   - How to install FreeRADIUS
   - Configuration template with API endpoint
   - Secret for NAS clients

**Validations**:
- User cannot exceed their RADIUS server quota (if applicable)
- Host+Port combination must be unique
- Host must be reachable (optional soft check)

### 2. Router Assignment to RADIUS Server
**Actor**: Admin/Reseller managing their routers

**Steps**:
1. User edits existing router OR creates new router
2. Form includes:
   - "RADIUS Server" dropdown (shows user's RADIUS servers)
   - "NAS Secret" field (auto-generated or custom)
   - "Enable RADIUS" checkbox
3. On save:
   - Associate `router.radius_server_id`
   - Generate unique `radius_nas_secret` for this router
   - Set `radius_enabled = true`
4. System provides MikroTik configuration commands:
   ```
   /radius add service=hotspot address=<host>:<port> secret=<nas_secret>
   /ip hotspot profile set [find] use-radius=yes
   ```
5. User copies commands, applies to MikroTik via Terminal/Winbox
6. System optionally verifies configuration (test authentication)

**Business Rules**:
- Router can belong to only ONE RADIUS server at a time
- RADIUS server must be `active` status
- If RADIUS server reaches `max_routers`, prevent assignment
- Resellers can only assign to their own RADIUS servers

### 3. RADIUS Server Monitoring
**Automated Background Job**: `CheckRadiusServerHealth` (runs every 5 minutes)

**Checks**:
1. **Connectivity**: TCP connection to host:port
2. **Authentication Test**: Send dummy auth request
3. **Sync Status**: Check `last_sync_at` (warn if > 1 hour old)
4. **Response Time**: Measure average auth response time

**Actions**:
- Update `status` field if server becomes unreachable
- Send notification to owner if server down > 15 minutes
- Auto-switch routers to backup RADIUS server (if configured)
- Log to `radius_sync_logs` for troubleshooting

---

## Authentication Flow

### Detailed Flow: User Connects to WiFi Hotspot

```
1. User connects to WiFi → MikroTik Hotspot Splash Page
   ↓
2. User enters voucher code (e.g., "ABCD-1234-EFGH")
   ↓
3. MikroTik sends RADIUS Access-Request to RADIUS server:
   - Username: ABCD-1234-EFGH
   - Password: ABCD-1234-EFGH (or blank if voucher-only)
   - NAS-IP-Address: <router_ip>
   - NAS-Identifier: <router_name>
   - Calling-Station-ID: <user_mac_address>
   ↓
4. RADIUS Server receives request:
   4a. Check memory cache first (Redis/Memcached)
       - Key: voucher:ABCD-1234-EFGH
       - Cache hit? → Skip to step 5
   4b. Cache miss? → Query Application API:
       POST https://radtik.app/api/v1/radius/authenticate
       Headers:
         Authorization: Bearer <api_token>
       Body:
         {
           "username": "ABCD-1234-EFGH",
           "mac_address": "<user_mac>",
           "router_identifier": "<router_name>",
           "nas_ip": "<router_ip>"
         }
   4c. Application validates:
       - Voucher exists and belongs to this router's owner
       - Voucher not expired
       - Voucher not already used (if single-use)
       - Router is active and belongs to RADIUS server
   4d. Application returns:
       {
         "status": "accept" | "reject",
         "attributes": {
           "Session-Timeout": 3600,
           "Idle-Timeout": 600,
           "WISPr-Bandwidth-Max-Down": 1024000,
           "WISPr-Bandwidth-Max-Up": 512000,
           "Acct-Interim-Interval": 300
         },
         "cache_ttl": 300  // Cache for 5 minutes
       }
   4e. RADIUS server caches result
   ↓
5. RADIUS Server sends Access-Accept/Reject to MikroTik:
   - Accept: Include all attributes (timeout, bandwidth, etc.)
   - Reject: Include Reply-Message (reason)
   ↓
6. MikroTik grants/denies access:
   - Accept: Apply bandwidth limits, start session timer
   - Reject: Show error message on splash page
   ↓
7. MikroTik sends Accounting-Start to RADIUS server:
   - Acct-Status-Type: Start
   - Acct-Session-Id: <unique_session_id>
   - User-Name: ABCD-1234-EFGH
   ↓
8. RADIUS Server forwards to Application API:
   POST https://radtik.app/api/v1/radius/accounting
   {
     "type": "start",
     "username": "ABCD-1234-EFGH",
     "session_id": "<unique_session_id>",
     "mac_address": "<user_mac>",
     "timestamp": "2026-02-02 10:30:00"
   }
   ↓
9. Application records session start:
   - Mark voucher as "used" (if single-use)
   - Create session log entry
   - Update user online status
   ↓
10. During session: MikroTik sends Interim-Updates (every 5 min):
    - Acct-Status-Type: Interim-Update
    - Acct-Input-Octets: <bytes_downloaded>
    - Acct-Output-Octets: <bytes_uploaded>
    - Acct-Session-Time: <seconds_elapsed>
    ↓
11. RADIUS Server forwards to Application API (aggregated):
    POST /api/v1/radius/accounting
    {
      "type": "update",
      "username": "ABCD-1234-EFGH",
      "session_id": "<unique_session_id>",
      "bytes_in": 5242880,
      "bytes_out": 1048576,
      "duration": 300
    }
    ↓
12. Application updates session statistics:
    - Track bandwidth usage
    - Check if quota exceeded (if applicable)
    - Update voucher usage time
    ↓
13. Session ends (timeout/user disconnect):
    MikroTik sends Accounting-Stop:
    - Acct-Status-Type: Stop
    - Acct-Terminate-Cause: <reason>
    - Total bytes/time
    ↓
14. RADIUS Server forwards final accounting to Application
    ↓
15. Application finalizes session:
    - Mark voucher as "expired" (if time-based)
    - Generate billing record
    - Update statistics
    - Clear cache entry
```

---

## Data Synchronization Strategy

### Sync Types

#### 1. Full Synchronization (Initial Setup)
**Trigger**: RADIUS server first connects to application

**Process**:
```
1. RADIUS server calls: GET /api/v1/radius/sync/full?radius_server_id=<id>
2. Application returns:
   {
     "vouchers": [
       {
         "code": "ABCD-1234-EFGH",
         "status": "active",
         "expires_at": "2026-02-10 23:59:59",
         "profile": {
           "session_timeout": 3600,
           "bandwidth_up": 512000,
           "bandwidth_down": 1024000
         },
         "router_ids": [1, 5, 12]  // Which routers can use this
       }
     ],
     "routers": [
       {
         "id": 1,
         "identifier": "Router-001",
         "nas_secret": "<encrypted>",
         "ip_address": "10.0.0.1"
       }
     ],
     "profiles": [...],
     "sync_token": "<unique_token>"  // For incremental sync
   }
3. RADIUS server stores in memory cache (Redis)
4. Logs sync completion in local log
```

**Frequency**: On RADIUS server startup, or manual trigger

#### 2. Incremental Synchronization (Delta Updates)
**Trigger**: Scheduled job every 5 minutes OR webhook notification

**Process**:
```
1. RADIUS server calls: GET /api/v1/radius/sync/incremental?since_token=<last_token>
2. Application returns only changes since last sync:
   {
     "changes": [
       {
         "type": "voucher",
         "action": "created",
         "data": {...}
       },
       {
         "type": "voucher",
         "action": "updated",
         "voucher_code": "ABCD-1234-EFGH",
         "changes": {"status": "expired"}
       },
       {
         "type": "voucher",
         "action": "deleted",
         "voucher_code": "WXYZ-9876-HIJK"
       }
     ],
     "sync_token": "<new_token>"
   }
3. RADIUS server applies delta changes to cache
4. Invalidates affected cache entries
```

**Frequency**: Every 5 minutes (configurable per RADIUS server)

#### 3. Real-time Push (Webhooks)
**Trigger**: Critical events in application (voucher created, expired, deleted)

**Process**:
```
1. Application event: VoucherCreated, VoucherExpired, etc.
2. System finds all RADIUS servers serving affected routers
3. For each RADIUS server:
   POST https://<radius_server_host>/webhook/sync
   Headers:
     X-Signature: <HMAC_signature>
     X-RADTik-Event: voucher.created
   Body:
     {
       "event": "voucher.created",
       "data": {...}
     }
4. RADIUS server verifies signature (HMAC with api_token)
5. RADIUS server updates cache immediately
6. Responds with 200 OK
```

**Events to Push**:
- Voucher created/updated/deleted
- Router disabled/enabled
- Profile bandwidth changed
- Emergency disconnect (fraud detection)

**Fallback**: If webhook fails, incremental sync will catch it within 5 minutes

---

## API Endpoint Design

### Application API (For RADIUS Servers)

All endpoints require Bearer token authentication (`api_token` from `radius_servers` table).

#### Authentication Endpoint
```
POST /api/v1/radius/authenticate
Content-Type: application/json
Authorization: Bearer <api_token>

Request:
{
  "username": "string (required)",
  "mac_address": "string (optional)",
  "router_identifier": "string (required)",
  "nas_ip": "string (required)",
  "nas_port": "integer (optional)",
  "calling_station_id": "string (optional)"
}

Response (Success - 200):
{
  "status": "accept",
  "attributes": {
    "Session-Timeout": 3600,
    "Idle-Timeout": 600,
    "WISPr-Bandwidth-Max-Down": 1024000,
    "WISPr-Bandwidth-Max-Up": 512000,
    "Acct-Interim-Interval": 300,
    "Reply-Message": "Welcome!"
  },
  "cache_ttl": 300,
  "user_id": 123  // Internal reference
}

Response (Reject - 200):
{
  "status": "reject",
  "reason": "Voucher expired",
  "Reply-Message": "Your voucher has expired. Please purchase a new one."
}

Response (Error - 422):
{
  "error": "Router not found or not associated with this RADIUS server"
}
```

#### Accounting Endpoint
```
POST /api/v1/radius/accounting
Authorization: Bearer <api_token>

Request:
{
  "type": "start|update|stop",
  "username": "string (required)",
  "session_id": "string (required)",
  "mac_address": "string (optional)",
  "router_identifier": "string (required)",
  "nas_ip": "string (required)",
  "timestamp": "ISO 8601 (required)",
  "bytes_in": "integer (for update/stop)",
  "bytes_out": "integer (for update/stop)",
  "duration": "integer (seconds, for update/stop)",
  "terminate_cause": "string (for stop)"
}

Response (200):
{
  "status": "recorded",
  "session_id": "abc123",
  "remaining_time": 2400,  // Seconds left
  "remaining_quota": 524288000  // Bytes left (if quota-based)
}
```

#### Full Synchronization Endpoint
```
GET /api/v1/radius/sync/full
Authorization: Bearer <api_token>
Query Parameters:
  - radius_server_id: <server_id> (optional, inferred from token)
  - include: vouchers,routers,profiles (comma-separated)

Response (200):
{
  "vouchers": [...],
  "routers": [...],
  "profiles": [...],
  "sync_token": "eyJ0eXAiOiJKV...",
  "generated_at": "2026-02-02T10:30:00Z",
  "total_records": 1523
}
```

#### Incremental Synchronization Endpoint
```
GET /api/v1/radius/sync/incremental
Authorization: Bearer <api_token>
Query Parameters:
  - since_token: <last_sync_token> (required)
  - radius_server_id: <server_id> (optional)

Response (200):
{
  "changes": [
    {
      "type": "voucher",
      "action": "created|updated|deleted",
      "entity_id": 456,
      "data": {...},  // Full object for created/updated
      "changed_fields": ["status", "expires_at"],  // For updated only
      "timestamp": "2026-02-02T10:25:00Z"
    }
  ],
  "sync_token": "eyJ0eXAiOiJKV...",
  "has_more": false
}
```

#### Health Check Endpoint
```
GET /api/v1/radius/health/<radius_server_id>
Authorization: Bearer <api_token>

Response (200):
{
  "status": "healthy",
  "last_sync": "2026-02-02T10:30:00Z",
  "routers_count": 45,
  "active_sessions": 234,
  "uptime": "15d 4h 23m"
}
```

### RADIUS Server API (For Application)

RADIUS server exposes webhook endpoint for real-time updates.

#### Webhook Endpoint
```
POST /webhook/sync
Headers:
  X-Signature: <HMAC_SHA256>
  X-RADTik-Event: <event_type>
Content-Type: application/json

Request:
{
  "event": "voucher.created|voucher.updated|voucher.deleted|router.disabled",
  "data": {...},
  "timestamp": "2026-02-02T10:30:00Z"
}

Response (200):
{
  "status": "processed",
  "cache_invalidated": true
}
```

---

## Security Considerations

### 1. Authentication & Authorization

**API Token Security**:
- Tokens stored encrypted in database (`Crypt::encrypt()`)
- Use Laravel Sanctum for token management
- Tokens have scopes: `radius:authenticate`, `radius:accounting`, `radius:sync`
- Tokens can be revoked instantly (blacklist in cache)
- Rate limiting: 1000 requests/minute per token

**RADIUS Shared Secrets**:
- 32-character random alphanumeric minimum
- Different secret for each router (NAS secret)
- Different secret for RADIUS server connection
- Secrets rotatable without downtime (grace period support)

**Webhook Signature Verification**:
```php
// Application side (sending webhook)
$signature = hash_hmac('sha256', $payload, $radiusServer->api_token);
// RADIUS server verifies signature matches
```

### 2. Network Security

**TLS/HTTPS Requirements**:
- All API communication MUST use HTTPS
- Certificate validation enforced
- Minimum TLS 1.2
- HSTS header enabled

**IP Whitelisting** (Optional):
- Allow `radius_servers` table to store allowed IPs
- Application API checks `X-Forwarded-For` / request IP
- Reject requests from non-whitelisted IPs

**RADIUS Protocol Security**:
- Use RADIUS over TLS (RadSec) when possible
- Shared secrets never transmitted (only hashed challenges)
- MikroTik supports RadSec since v6.45

### 3. Data Protection

**Encryption at Rest**:
- All sensitive fields encrypted: `secret`, `api_token`, `radius_nas_secret`
- Use Laravel's `Crypt` facade (AES-256-CBC)
- Key rotation strategy (re-encrypt on key change)

**Encryption in Transit**:
- HTTPS for all API calls
- RADIUS protocol uses MD5 hashing (older) or RadSec (modern)

**PII Handling**:
- MAC addresses hashed in logs (GDPR compliance)
- Usernames (voucher codes) are not PII
- IP addresses logged with retention policy (30 days)

### 4. Audit Logging

**Application Side**:
- Log all RADIUS server creation/updates (who, when, what changed)
- Log all API calls from RADIUS servers (endpoint, result, duration)
- Log failed authentication attempts (brute force detection)

**RADIUS Server Side**:
- Log all authentication requests (username, NAS, result)
- Log all sync operations (full/incremental, records affected)
- Log webhook deliveries (success/failure)

### 5. Rate Limiting & DDoS Protection

**API Rate Limits**:
- Authentication endpoint: 100 req/sec per token
- Accounting endpoint: 50 req/sec per token
- Sync endpoints: 10 req/min per token
- Return `429 Too Many Requests` with `Retry-After` header

**RADIUS Server Protection**:
- Connection pooling (max 1000 concurrent connections)
- Request queuing (handle bursts gracefully)
- Circuit breaker pattern (if app API down, use cached data)

---

## Migration Strategy

### Phase 1: Preparation (Week 1)
**Goal**: Build RADIUS infrastructure without disrupting existing system

**Tasks**:
1. Create database migrations:
   - `radius_servers` table
   - `radius_sync_logs` table
   - `radius_authentication_logs` table
   - Update `routers` table (add RADIUS columns)
2. Create Models:
   - `RadiusServer` with encryption casts
   - Relationships: `RadiusServer hasMany Routers`
3. Build API endpoints:
   - `/api/v1/radius/authenticate`
   - `/api/v1/radius/accounting`
   - `/api/v1/radius/sync/full`
   - `/api/v1/radius/sync/incremental`
4. Add middleware: `RadiusApiAuthentication`
5. Write comprehensive API tests (Pest)

**Success Criteria**:
- All migrations run cleanly
- API endpoints return expected responses in tests
- No impact on existing functionality

### Phase 2: UI Development (Week 2)
**Goal**: Allow users to create and manage RADIUS servers

**Tasks**:
1. Create Livewire components:
   - `app/Livewire/RadiusServer/Index.php` (list)
   - `app/Livewire/RadiusServer/Create.php` (create form)
   - `app/Livewire/RadiusServer/Edit.php` (edit form)
   - `app/Livewire/RadiusServer/Show.php` (details + setup instructions)
2. Add permissions:
   - `view_radius_servers`
   - `create_radius_servers`
   - `edit_radius_servers`
   - `delete_radius_servers`
3. Update router forms:
   - Add "RADIUS Server" dropdown
   - Add "Enable RADIUS" checkbox
   - Auto-generate NAS secret
   - Display MikroTik config commands
4. Create documentation page:
   - How to install FreeRADIUS
   - Configuration templates
   - Troubleshooting guide

**Success Criteria**:
- Users can create/edit/delete RADIUS servers via UI
- Router assignment to RADIUS server works
- MikroTik configuration commands generated correctly

### Phase 3: RADIUS Server Implementation (Week 3)
**Goal**: Set up actual FreeRADIUS server with sync mechanism

**Tasks**:
1. Install FreeRADIUS on test server:
   ```bash
   apt-get install freeradius freeradius-utils
   ```
2. Configure FreeRADIUS:
   - Custom module for API authentication
   - Python/Node.js script for RADTik API calls
   - Redis for caching
3. Implement sync service:
   - Full sync on startup
   - Incremental sync every 5 minutes
   - Webhook receiver endpoint
4. Implement accounting forwarding:
   - Parse accounting packets
   - Forward to RADTik API in batches
5. Configure NAS clients (MikroTik routers):
   - Add to FreeRADIUS `clients.conf`
   - Dynamic NAS client loading from RADTik API
6. Set up monitoring:
   - Prometheus metrics (auth rate, response time)
   - Health check endpoint
   - Alert on sync failures

**Success Criteria**:
- FreeRADIUS authenticates against RADTik API
- Caching reduces API calls by 90%+
- Accounting data flows to RADTik
- Average auth response time < 50ms

### Phase 4: Pilot Testing (Week 4)
**Goal**: Test with real routers and users in controlled environment

**Tasks**:
1. Select 5-10 test routers
2. Configure routers to use RADIUS:
   ```
   /radius add service=hotspot address=<radius_host>:1812 secret=<nas_secret>
   /ip hotspot profile set [find] use-radius=yes
   ```
3. Generate test vouchers
4. Simulate user connections:
   - Single-use vouchers
   - Time-limited vouchers
   - Bandwidth-limited vouchers
5. Monitor metrics:
   - Authentication success rate
   - Response times
   - Data sync accuracy
   - Accounting accuracy
6. Stress test:
   - 100 concurrent connections
   - 1000 auth requests/minute
7. Document issues and edge cases

**Success Criteria**:
- 99.9%+ authentication success rate
- All accounting data accurate (±1%)
- No data loss during sync
- System stable under load

### Phase 5: Production Rollout (Week 5-6)
**Goal**: Migrate all routers to RADIUS, deprecate API scripts

**Strategy**: Gradual rollout by user cohort

**Week 5**:
1. Enable RADIUS for new routers only:
   - New router creation defaults to RADIUS
   - Existing routers remain on API scripts
2. Send notification to all users:
   - Explain RADIUS benefits
   - Provide migration guide
   - Offer support for migration
3. Monitor adoption rate
4. Incentivize early adopters (discount, priority support)

**Week 6**:
1. Offer 1-click migration tool:
   - Livewire component: "Migrate to RADIUS"
   - System configures router automatically via API
   - Runs test authentication
   - Confirms success before finalizing
2. Set deprecation timeline:
   - API script method deprecated in 2 months
   - Notification banner for users still on API scripts
3. Scale RADIUS infrastructure:
   - Deploy additional RADIUS servers for redundancy
   - Set up geographic distribution
   - Configure load balancing

**Success Criteria**:
- 80%+ of routers migrated to RADIUS
- Zero critical incidents
- User satisfaction maintained or improved
- Support ticket volume within normal range

### Phase 6: Cleanup & Optimization (Week 7)
**Goal**: Remove legacy code, optimize performance

**Tasks**:
1. Remove API script installation code:
   - `app/MikroTik/Installer/ScriptInstaller.php` (archive)
   - Related Livewire components
   - Database migrations (keep for reference)
2. Archive documentation:
   - Move API script docs to `/docs/legacy/`
3. Optimize RADIUS performance:
   - Tune cache TTLs based on metrics
   - Implement connection pooling
   - Add CDN for distributed RADIUS servers
4. Enhanced monitoring:
   - Grafana dashboards for RADIUS metrics
   - Alert rules for anomalies
   - Capacity planning based on growth trends
5. Update user documentation:
   - Remove API script references
   - Expand RADIUS troubleshooting guide
   - Add video tutorials

**Success Criteria**:
- Codebase cleaner (fewer lines, less complexity)
- Average auth response time < 30ms
- 99.99% uptime over 30 days
- Documentation comprehensive and up-to-date

---

## Deployment Architecture

### Small Deployment (< 100 routers)
```
┌──────────────────────────────────┐
│   RADTik Application (Laravel)   │
│   - Database                     │
│   - API                          │
│   - UI                           │
└──────────────┬───────────────────┘
               │ HTTPS API
               ↓
┌──────────────────────────────────┐
│   Single RADIUS Server           │
│   - FreeRADIUS                   │
│   - Redis Cache                  │
│   - Sync Service                 │
└──────────────┬───────────────────┘
               │ RADIUS Protocol
               ↓
        MikroTik Routers (1-100)
```

**Specs**:
- RADIUS Server: 2 vCPU, 4GB RAM, 50GB SSD
- Redis: 512MB memory limit
- Expected load: 50 auth/sec, 500ms avg sync time

### Medium Deployment (100-1000 routers)
```
                    ┌──────────────────────┐
                    │  RADTik Application  │
                    │  (Load Balanced)     │
                    └──────────┬───────────┘
                               │ HTTPS API
                ┌──────────────┴───────────────┐
                ↓                              ↓
┌─────────────────────────┐    ┌─────────────────────────┐
│  RADIUS Server (US-East)│    │  RADIUS Server (US-West)│
│  - FreeRADIUS           │    │  - FreeRADIUS           │
│  - Redis Cluster        │    │  - Redis Cluster        │
│  - Sync Service         │    │  - Sync Service         │
└────────────┬────────────┘    └────────────┬────────────┘
             │                              │
             ↓                              ↓
    Routers (East)                  Routers (West)
```

**Specs**:
- RADIUS Servers: 4 vCPU, 8GB RAM, 100GB SSD each
- Redis Cluster: 3-node, 2GB per node
- Load Balancer: HAProxy/Nginx
- Expected load: 500 auth/sec, 200ms avg sync time

### Large Deployment (1000+ routers, Multi-Tenant)
```
                    ┌──────────────────────┐
                    │  RADTik Application  │
                    │  (K8s Cluster)       │
                    └──────────┬───────────┘
                               │ HTTPS API
        ┌──────────────────────┼──────────────────────┐
        ↓                      ↓                      ↓
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  RADIUS Pool │    │  RADIUS Pool │    │  RADIUS Pool │
│  (Region 1)  │    │  (Region 2)  │    │  (Region 3)  │
│  - 3 servers │    │  - 3 servers │    │  - 3 servers │
│  - Redis     │    │  - Redis     │    │  - Redis     │
│  - HAProxy   │    │  - HAProxy   │    │  - HAProxy   │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                   │
       ↓                   ↓                   ↓
   Routers             Routers             Routers
   (0-333)            (334-666)           (667-1000)
```

**Specs**:
- RADIUS Servers: 8 vCPU, 16GB RAM, 200GB SSD each (9 total)
- Redis: Sentinel setup, 3 master nodes, 3 replicas
- Geographic distribution: 3 regions
- CDN: Cloudflare for API calls
- Expected load: 2000+ auth/sec, 100ms avg sync time

---

## Monitoring & Maintenance

### Key Metrics to Track

#### Application Side
1. **API Performance**:
   - `/api/v1/radius/authenticate` response time (target: < 50ms)
   - `/api/v1/radius/accounting` throughput (req/sec)
   - Sync endpoint response time
   - Error rate by endpoint (target: < 0.1%)

2. **Data Accuracy**:
   - Voucher usage discrepancies (app vs accounting)
   - Session count mismatches
   - Sync lag (time between app event and RADIUS cache update)

3. **Business Metrics**:
   - RADIUS server adoption rate (% of routers)
   - Average routers per RADIUS server
   - User satisfaction scores (tickets related to auth)

#### RADIUS Server Side
1. **Authentication Metrics**:
   - Auth requests/sec (by NAS, by user)
   - Auth success rate (target: > 99.9%)
   - Auth response time (target: < 30ms)
   - Reject reasons distribution (expired, invalid, etc.)

2. **Cache Performance**:
   - Cache hit rate (target: > 90%)
   - Cache size (memory usage)
   - Eviction rate
   - Stale data incidents

3. **Sync Health**:
   - Last successful sync timestamp
   - Sync duration (full vs incremental)
   - Webhook delivery success rate
   - Records out of sync (count)

4. **System Health**:
   - CPU/Memory usage
   - Network I/O
   - Disk space
   - Process uptime

### Alerting Rules

**Critical Alerts** (Immediate Action):
- RADIUS server unreachable for 5+ minutes
- Auth success rate < 95%
- Sync failed 3 consecutive times
- Cache eviction rate > 10/sec (memory pressure)
- API response time > 500ms for 5 minutes

**Warning Alerts** (Monitor):
- Last sync > 15 minutes ago
- Cache hit rate < 85%
- Auth response time > 100ms avg
- Webhook delivery failures > 5% in 1 hour
- Disk usage > 80%

### Maintenance Tasks

**Daily** (Automated):
- Health check all RADIUS servers
- Aggregate accounting data
- Clean up old logs (> 30 days)
- Validate cache accuracy (sample 100 vouchers)

**Weekly**:
- Review error logs for patterns
- Performance trend analysis
- Capacity planning (project growth)
- Update RADIUS server configurations (if needed)

**Monthly**:
- Rotate API tokens (optional, for high-security)
- Full sync audit (verify all data consistency)
- Security patch updates
- Disaster recovery drill

---

## Troubleshooting Guide

### Common Issues & Solutions

#### Issue 1: "Authentication Always Fails"
**Symptoms**: All auth requests return Reject

**Diagnosis**:
1. Check RADIUS server can reach application API:
   ```bash
   curl -H "Authorization: Bearer <api_token>" \
        https://radtik.app/api/v1/radius/health/<server_id>
   ```
2. Verify API token valid and not expired
3. Check router's NAS secret matches `radius_servers` secret
4. Test voucher validity in application (not expired, belongs to router)

**Solution**:
- Regenerate API token if invalid
- Update NAS secret in MikroTik if mismatch
- Resync RADIUS server cache (full sync)

#### Issue 2: "Slow Authentication (> 1 second)"
**Symptoms**: Users report long wait on splash page

**Diagnosis**:
1. Check RADIUS server cache hit rate (should be > 90%)
2. Measure API endpoint response time
3. Check network latency (RADIUS server to application)
4. Review RADIUS server resource usage (CPU/memory)

**Solution**:
- Increase cache TTL (reduce API calls)
- Scale RADIUS server (add more resources)
- Deploy RADIUS server closer to routers (geo-distribution)
- Optimize database queries in API endpoints (add indexes)

#### Issue 3: "Accounting Data Missing"
**Symptoms**: Sessions not recorded, usage stats incorrect

**Diagnosis**:
1. Check MikroTik sending accounting packets:
   ```
   /radius monitor [find]
   ```
2. Verify RADIUS server receiving accounting (check logs)
3. Test accounting API endpoint manually
4. Check for dropped accounting packets (network issues)

**Solution**:
- Enable accounting in MikroTik hotspot profile
- Fix network connectivity between router and RADIUS server
- Increase accounting buffer size (batch sends)
- Implement retry mechanism for failed accounting posts

#### Issue 4: "Vouchers Not Syncing"
**Symptoms**: Newly created voucher not working immediately

**Diagnosis**:
1. Check last sync timestamp in `radius_sync_logs`
2. Verify webhook endpoint reachable (if using webhooks)
3. Test incremental sync endpoint manually
4. Check for sync errors in application logs

**Solution**:
- Trigger manual full sync
- Fix webhook signature verification (if failing)
- Reduce sync interval (from 5 min to 1 min)
- Implement cache invalidation on voucher create (don't wait for sync)

#### Issue 5: "RADIUS Server Shows Offline"
**Symptoms**: Health check fails, status = 'inactive'

**Diagnosis**:
1. Ping RADIUS server host
2. Check RADIUS service running:
   ```bash
   systemctl status freeradius
   ```
3. Test port accessibility:
   ```bash
   telnet <radius_host> 1812
   ```
4. Review RADIUS server logs for crash/errors

**Solution**:
- Restart RADIUS service
- Fix firewall rules (allow port 1812/1813)
- Increase RADIUS server resources (if OOM killed)
- Review and fix recent configuration changes

---

## Cost Analysis

### Infrastructure Costs (Monthly)

#### Small Deployment (< 100 routers)
| Component | Specs | Cost |
|-----------|-------|------|
| RADIUS Server (VPS) | 2 vCPU, 4GB RAM | $20 |
| Redis (Managed) | 512MB | $10 |
| Bandwidth | 500GB/month | $5 |
| **Total** | | **$35/month** |

**Cost per router**: $0.35/month

#### Medium Deployment (100-1000 routers)
| Component | Specs | Cost |
|-----------|-------|------|
| RADIUS Servers (2x) | 4 vCPU, 8GB RAM each | $80 |
| Redis Cluster | 3 nodes, 2GB each | $50 |
| Load Balancer | HAProxy VPS | $15 |
| Bandwidth | 2TB/month | $20 |
| Monitoring (Prometheus) | 2 vCPU, 4GB RAM | $20 |
| **Total** | | **$185/month** |

**Cost per router**: $0.19/month

#### Large Deployment (1000+ routers, Multi-Tenant)
| Component | Specs | Cost |
|-----------|-------|------|
| RADIUS Servers (9x) | 8 vCPU, 16GB RAM each | $540 |
| Redis Sentinel (6 nodes) | 4GB each | $180 |
| Load Balancers (3x regions) | | $60 |
| Bandwidth | 10TB/month | $100 |
| CDN (Cloudflare Pro) | | $20 |
| Monitoring Stack | Grafana Cloud | $50 |
| **Total** | | **$950/month** |

**Cost per router** (1000 routers): $0.95/month
**Cost per router** (5000 routers): $0.19/month

### Cost Savings vs Current Setup

| Aspect | Current (API Scripts) | Proposed (RADIUS) | Savings |
|--------|----------------------|-------------------|---------|
| Setup Time per Router | 60-90 seconds | 5-10 seconds | **85% faster** |
| API Calls per Auth | 1 (synchronous) | 0.1 (cached 90%) | **90% reduction** |
| Bandwidth Usage | High (frequent polling) | Low (push updates) | **70% reduction** |
| Failure Rate | 5-10% (timeouts) | < 0.1% | **98% improvement** |
| Support Tickets | 50/month (estimate) | 5/month (estimate) | **90% reduction** |
| Development Time | High (custom scripts) | Low (standard protocol) | **60% reduction** |

**Total Cost Savings**: Estimated $200-500/month in reduced support, bandwidth, and improved reliability (for medium deployment).

---

## Success Metrics (3-Month Post-Migration)

### Technical Metrics
- ✅ **Authentication Success Rate**: > 99.9%
- ✅ **Average Response Time**: < 30ms
- ✅ **Router Setup Time**: < 2 minutes
- ✅ **Data Sync Accuracy**: > 99.99%
- ✅ **System Uptime**: > 99.9%

### Business Metrics
- ✅ **User Satisfaction**: > 90% (survey)
- ✅ **Support Ticket Volume**: Reduced by > 70%
- ✅ **New Router Onboarding**: 3x faster
- ✅ **Operational Cost**: Reduced by > 50%
- ✅ **Scalability**: Support 5x more routers without infra changes

### Adoption Metrics
- ✅ **Migration Rate**: > 95% of routers
- ✅ **User Adoption**: > 90% of active users
- ✅ **RADIUS Server Deployment**: > 80% of resellers
- ✅ **Zero Downtime**: No service interruptions during migration

---

## Future Enhancements (Post-MVP)

### Phase 2 Features (6-12 months)
1. **Multi-RADIUS Failover**:
   - Routers configured with primary + backup RADIUS servers
   - Automatic failover in < 5 seconds
   - Health monitoring and auto-recovery

2. **Advanced Caching**:
   - Predictive cache warming (ML-based)
   - Multi-tier caching (L1: local, L2: Redis, L3: database)
   - Cache coherence across distributed RADIUS servers

3. **Analytics Dashboard**:
   - Real-time auth rate graphs
   - Geographic distribution maps
   - Performance heatmaps by router
   - Predictive capacity alerts

4. **Self-Service Diagnostics**:
   - User-facing tools to test RADIUS connectivity
   - Automated troubleshooting wizard
   - Export diagnostic reports for support

5. **RADIUS Server Templates**:
   - Pre-configured Docker images
   - One-click deployment to AWS/GCP/DigitalOcean
   - Auto-scaling groups based on load

### Phase 3 Features (12-24 months)
1. **Edge Computing**:
   - Deploy lightweight RADIUS servers on customer premises
   - Local authentication with eventual consistency
   - Offline mode (cached vouchers work without internet)

2. **Advanced Fraud Detection**:
   - ML models detect suspicious auth patterns
   - Automatic account suspension on fraud
   - Integration with threat intelligence feeds

3. **API Marketplace**:
   - Allow third-party RADIUS server implementations
   - SDK for custom authentication logic
   - Revenue sharing for premium RADIUS features

4. **Blockchain Voucher Verification** (if applicable):
   - Immutable voucher issuance records
   - Cross-platform voucher redemption
   - Anti-counterfeiting measures

---

## Conclusion

This migration from direct MikroTik API to RADIUS architecture solves the critical timeout/synchronization issues while providing:

1. **Immediate Benefits**:
   - Eliminate 405 timeout errors
   - 85% faster router setup
   - 90% reduction in API calls
   - 98% improvement in reliability

2. **Long-Term Advantages**:
   - Industry-standard AAA protocol
   - Horizontal scalability (add RADIUS servers as needed)
   - Geographic distribution for low latency
   - Multi-tenant ready architecture

3. **Operational Excellence**:
   - Reduced support burden (70% fewer tickets)
   - Simplified troubleshooting
   - Better observability and monitoring
   - Lower infrastructure costs at scale

**Recommended Next Steps**:
1. Review and approve this plan
2. Allocate development resources (1 backend dev, 1 DevOps engineer)
3. Set up test environment (Phase 1 prep)
4. Begin Phase 1 implementation (database + API endpoints)
5. Establish success criteria and KPIs
6. Schedule weekly progress reviews

**Estimated Timeline**: 7 weeks from approval to full production deployment

**Risk Level**: Low (gradual rollout, fallback to current system always available)

---

**Document Version**: 1.0  
**Created**: February 2, 2026  
**Author**: GitHub Copilot (Claude Sonnet 4.5)  
**Status**: Awaiting Approval
