# NAS/MikroTik Binding - Implementation Checklist

Track implementation progress for the NAS binding feature.

**Status Legend**: ⬜ Not Started | 🔄 In Progress | ✅ Completed | ❌ Blocked

---

## Phase 1: Database & Models

### Migrations
- ⬜ Task 1.1: Migration - Add NAS fields to `routers` table
  - `nas_identifier` (string, unique, indexed)
  - `parent_router_id` (foreignId, nullable)
  - `is_nas_device` (boolean, default false)
  - `nas_group_enabled` (boolean, default false)
  - `radius_configured` (boolean, default false)

- ⬜ Task 1.2: Migration - Create `router_nas_config` table
  - Store RADIUS configuration details per router

- ⬜ Task 1.3: Migration - Add binding fields to `vouchers` table
  - `binding_strategy` (enum: 'none', 'pre_bind', 'auto_bind')
  - `bound_router_id` (foreignId, nullable)
  - `bound_at` (timestamp, nullable)
  - `allow_nas_group` (boolean, default false)

- ⬜ Task 1.4: Migration - Create `voucher_authentication_log` table
  - Track all authentication attempts with NAS info

- ⬜ Task 1.5: Data Migration - Populate NAS identifiers for existing routers

### Model Updates
- ⬜ Task 1.6: Update `Router` model
  - Add relationships: parent(), children(), nasConfig()
  - Add scopes: isParent(), isChild(), hasNasGroup()
  - Add accessor: getEffectiveNasIdentifier()

- ⬜ Task 1.7: Update `Voucher` model
  - Add relationship: boundRouter()
  - Add scopes: bound(), unbound(), preBound(), autoBound()
  - Add methods: bind(), unbind(), canAuthenticateFrom($router)

- ⬜ Task 1.8: Create `RouterNasConfig` model
  - Relationships and validation

- ⬜ Task 1.9: Create `VoucherAuthenticationLog` model
  - Logging methods

---

## Phase 2: NAS Identifier System

- ⬜ Task 2.1: Create `app/Services/NasIdentifierGenerator.php`
  - `generate(Router $router)` - Generate unique identifier
  - `regenerate(Router $router)` - Regenerate with validation
  - `validate(string $identifier)` - Validation logic

- ⬜ Task 2.2: Auto-generate on router creation
  - Add observer or event listener
  - Update `Router/Create.php` Livewire component

- ⬜ Task 2.3: Create command to populate existing routers
  - `php artisan radtik:generate-nas-identifiers`

- ⬜ Task 2.4: Add UI for viewing/editing NAS identifier
  - Update `Router/Edit.php` or `Router/Show.php`
  - Add "Regenerate" button with confirmation

---

## Phase 3: MikroTik Auto-Configuration

- ⬜ Task 3.1: Create `app/MikroTik/Actions/RadiusConfigurator.php`
  - `configureRadius(Router $router)` - Full RADIUS setup
  - `setNasIdentifier(Router $router)` - Set system identity
  - `enableRadiusAuth()` - Enable RADIUS in hotspot
  - `testConnection()` - Test RADIUS connectivity

- ⬜ Task 3.2: Create background job
  - `app/Jobs/ConfigureRouterRadius.php`
  - Handle failures and retries

- ⬜ Task 3.3: Add RADIUS configuration UI
  - New Livewire component: `Router/RadiusConfig.php`
  - Show configuration status
  - Manual trigger button
  - Test connection button

- ⬜ Task 3.4: Add settings for RADIUS defaults
  - Update `config/radtik.php` or settings table
  - Server IP, ports, shared secret template

---

## Phase 4: Voucher Binding Strategies

### UI Updates
- ⬜ Task 4.1: Update `Voucher/Generate.php` component
  - Add binding strategy radio buttons
  - Conditional MikroTik selection dropdown
  - NAS group checkbox (if applicable)
  - Preview binding details

### Backend Logic
- ⬜ Task 4.2: Implement pre-bind logic
  - Validate router selection
  - Set binding in voucher record
  - Pass to RADIUS sync

- ⬜ Task 4.3: Create binding service
  - `app/Services/VoucherBindingService.php`
  - `preBind(Voucher $voucher, Router $router)`
  - `autoBind(Voucher $voucher, Router $router)`
  - `unbind(Voucher $voucher)`
  - `canAuthenticate(Voucher $voucher, Router $router)`

- ⬜ Task 4.4: Implement NAS group logic
  - Parent/child validation
  - NAS identifier inheritance
  - Update `Router/Edit.php` for NAS group management

---

## Phase 5: RADIUS Integration

### Laravel API
- ⬜ Task 5.1: Create `app/Http/Controllers/Api/RadiusController.php`
  - `activateVoucher()` - Enhanced with NAS binding
  - `getRouterByNas($nasIdentifier)` - Router lookup
  - `checkVoucherBinding()` - Validate binding

- ⬜ Task 5.2: Add API routes in `routes/api.php`
  - Secure with middleware and API token

- ⬜ Task 5.3: Update `syncVouchers()` API method
  - Include NAS identifier in voucher data
  - Return binding information

### Python Scripts
- ⬜ Task 5.4: Update `radtik-radius/scripts/sync-vouchers.py`
  - Add NAS-Identifier to radcheck when bound
  - Handle NAS group inheritance
  - Sync binding changes

- ⬜ Task 5.5: Update `radtik-radius/scripts/check-activations.py`
  - Detect auto-bind candidates
  - Call Laravel with NAS identifier
  - Apply binding to RADIUS if instructed

- ⬜ Task 5.6: Update `radtik-radius/scripts/config.ini.example`
  - Add any new configuration options

### Database Schema (RADIUS)
- ⬜ Task 5.7: Update `radtik-radius/sqlite/radius.db`
  - Ensure NAS-Identifier support in radcheck
  - Document attribute usage

---

## Phase 6: Admin Management Features

### Router Management
- ⬜ Task 6.1: Update `Router/Index.php`
  - Add NAS identifier column
  - Show parent/child indicators
  - RADIUS config status badge

- ⬜ Task 6.2: Update `Router/Show.php`
  - Display NAS details section
  - Show RADIUS configuration
  - List child devices (if parent)

- ⬜ Task 6.3: Create `Router/ManageNasGroup.php` component
  - Enable/disable NAS grouping
  - Add/remove child devices
  - Manage inherited settings

### Voucher Management
- ⬜ Task 6.4: Update `Voucher/Index.php`
  - Add binding status column
  - Add bound router column
  - Filter by binding strategy

- ⬜ Task 6.5: Update `Voucher/Show.php`
  - Display binding details section
  - Show bound router
  - Show binding date
  - Authentication log preview

- ⬜ Task 6.6: Create `Voucher/ManageBinding.php` component
  - Change binding strategy
  - Manual bind/unbind actions
  - View full authentication history
  - Rebind to different router

---

## Phase 7: Settings & Configuration

- ⬜ Task 7.1: Add RADIUS settings section
  - Update `app/Livewire/Admin/Settings/General.php`
  - Or create new `Settings/Radius.php` component
  - Server IP, ports, shared secret defaults

- ⬜ Task 7.2: Add NAS binding feature settings
  - Enable/disable NAS binding
  - Default binding strategy
  - NAS identifier prefix customization

- ⬜ Task 7.3: Add permissions
  - `manage_nas_binding`
  - `configure_router_radius`
  - Update `PermissionSeed.php`

---

## Phase 8: Authentication Logging

- ⬜ Task 8.1: Create logging service
  - `app/Services/VoucherAuthenticationLogger.php`
  - Log all authentication attempts
  - Link to router via NAS identifier

- ⬜ Task 8.2: Create `Voucher/AuthenticationLog.php` component
  - Display per-voucher authentication history
  - Filter by date, router, status
  - Timeline visualization

- ⬜ Task 8.3: Add analytics dashboard
  - Usage by router
  - Binding patterns
  - Success/failure rates
  - Popular authentication times

---

## Phase 9: Testing

### Unit Tests
- ⬜ Task 9.1: Test NAS identifier generation
  - `tests/Unit/NasIdentifierGeneratorTest.php`
  - Uniqueness, format, regeneration

- ⬜ Task 9.2: Test voucher binding service
  - `tests/Unit/VoucherBindingServiceTest.php`
  - All binding strategies

- ⬜ Task 9.3: Test NAS group logic
  - Parent/child relationships
  - Identifier inheritance

### Feature Tests
- ⬜ Task 9.4: Test router RADIUS configuration
  - `tests/Feature/RouterRadiusConfigTest.php`
  - Auto-configuration flow

- ⬜ Task 9.5: Test voucher generation with binding
  - `tests/Feature/VoucherGenerationTest.php`
  - All binding strategies

- ⬜ Task 9.6: Test RADIUS API endpoints
  - `tests/Feature/RadiusApiTest.php`
  - Activation with binding

### Integration Tests
- ⬜ Task 9.7: Test Python script integration
  - Mock RADIUS database
  - Test sync-vouchers.py with bindings
  - Test check-activations.py with auto-bind

### End-to-End Tests
- ⬜ Task 9.8: E2E Test Scenario 1: Pre-bind
  - Generate voucher with pre-bind
  - Authenticate from bound router → Success
  - Authenticate from other router → Fail

- ⬜ Task 9.9: E2E Test Scenario 2: Auto-bind
  - Generate voucher with auto-bind
  - First authentication → Binding created
  - Subsequent auth from same router → Success
  - Auth from different router → Fail

- ⬜ Task 9.10: E2E Test Scenario 3: NAS Group
  - Setup parent router with children
  - Generate voucher bound to parent
  - Authenticate from child → Success (inherited)

- ⬜ Task 9.11: E2E Test Scenario 4: No binding
  - Generate voucher without binding
  - Authenticate from any router → Success

---

## Phase 10: Documentation

### User Documentation
- ⬜ Task 10.1: Create `docs/NAS_BINDING_USER_GUIDE.md`
  - What is NAS binding?
  - When to use each strategy
  - How to generate bound vouchers
  - Troubleshooting common issues

- ⬜ Task 10.2: Create `docs/NAS_GROUP_SETUP.md`
  - Setting up parent/child relationships
  - Use cases for NAS groups
  - Best practices

### Admin Documentation
- ⬜ Task 10.3: Create `docs/NAS_BINDING_ADMIN_GUIDE.md`
  - Managing NAS identifiers
  - Configuring RADIUS on routers
  - Managing voucher bindings
  - Viewing authentication logs

- ⬜ Task 10.4: Update `docs/ROUTER_MANAGEMENT.md`
  - Add NAS configuration section
  - Add NAS group management

### API Documentation
- ⬜ Task 10.5: Update `docs/API_DOCUMENTATION.md`
  - New RADIUS API endpoints
  - Request/response examples
  - Authentication with NAS binding

### Technical Documentation
- ⬜ Task 10.6: Update `radtik-radius/README.md`
  - NAS-Identifier attribute explanation
  - Binding logic in RADIUS
  - Python script changes

- ⬜ Task 10.7: Update database documentation
  - New tables and columns
  - Relationships diagram
  - Migration guide

---

## Phase 11: Deployment & Rollout

- ⬜ Task 11.1: Create deployment checklist
  - Backup procedures
  - Migration steps
  - Rollback plan

- ⬜ Task 11.2: Update `.env.example`
  - Add new configuration variables

- ⬜ Task 11.3: Create upgrade guide
  - For existing installations
  - Data migration steps

- ⬜ Task 11.4: Feature announcement
  - Release notes
  - Change log update
  - User notification

---

## Quality Assurance Checklist

- ⬜ All migrations have `down()` methods for rollback
- ⬜ All new features have permission checks
- ⬜ All database queries are indexed appropriately
- ⬜ All user inputs are validated
- ⬜ All API endpoints have authentication
- ⬜ All features work with existing multi-tenant permissions
- ⬜ All UI components follow MaryUI patterns
- ⬜ All code follows Laravel best practices
- ⬜ All features are tested (unit + feature + integration)
- ⬜ All documentation is complete and accurate

---

## Performance Optimization Checklist

- ⬜ Database indexes on all foreign keys
- ⬜ Eager loading for preventing N+1 queries
- ⬜ Caching for NAS identifiers
- ⬜ Batch operations in RADIUS sync
- ⬜ Queue jobs for heavy operations
- ⬜ API rate limiting configured

---

## Security Checklist

- ⬜ NAS identifiers are unique and validated
- ⬜ Shared secrets are encrypted in database
- ⬜ API tokens are validated in Python scripts
- ⬜ Permission checks on all admin actions
- ⬜ Audit trail for binding changes
- ⬜ Prevent circular parent-child references
- ⬜ Input sanitization for MikroTik API calls

---

## Progress Summary

**Overall Progress**: 0/100+ tasks completed

**Phase Status**:
- Phase 1 (Database): 0/9 ⬜
- Phase 2 (NAS System): 0/4 ⬜
- Phase 3 (Auto-Config): 0/4 ⬜
- Phase 4 (Binding): 0/4 ⬜
- Phase 5 (RADIUS): 0/7 ⬜
- Phase 6 (Admin UI): 0/6 ⬜
- Phase 7 (Settings): 0/3 ⬜
- Phase 8 (Logging): 0/3 ⬜
- Phase 9 (Testing): 0/11 ⬜
- Phase 10 (Docs): 0/7 ⬜
- Phase 11 (Deploy): 0/4 ⬜

---

**Last Updated**: 2026-02-15
**Estimated Completion**: 20-30 days
**Current Phase**: Not Started
