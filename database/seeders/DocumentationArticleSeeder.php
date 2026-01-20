<?php

namespace Database\Seeders;

use App\Models\DocumentationArticle;
use Illuminate\Database\Seeder;

class DocumentationArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates technical documentation articles.
     */
    public function run(): void
    {
        $this->command->info('📖 Creating documentation articles...');

        $articles = [
            [
                'title' => 'How to Add a Router',
                'category' => 'Getting Started',
                'content' => <<<'MD'
# How to Add a Router

## Prerequisites
Before adding a router to RADTik, ensure:
- You have an active subscription package
- MikroTik router with RouterOS installed
- API service enabled on the router
- Network access to the router
- Admin credentials

## Enabling API on MikroTik

Connect to your MikroTik router via WinBox or SSH and run:

```
/ip service print
/ip service enable api
/ip service set api port=8728
```

Verify API is running:
```
/ip service print
```

## Adding Router in RADTik

### Step 1: Navigate to Routers
- Login to RADTik dashboard
- Click on "Routers" in the sidebar
- Click "Add Router" button

### Step 2: Enter Router Details

**Router Name**: Give a descriptive name (e.g., "Main Office Router", "Branch 01 Gateway")

**IP / Host**: Enter router's IP address or hostname
- Example: `192.168.88.1` or `router.myisp.local`

**API Port**: Default is `8728`
- Use `8729` for API-SSL (recommended for production)

**Username**: MikroTik admin username (usually `admin`)

**Password**: Router admin password (stored encrypted)

**Login Address**: Hotspot login page URL
- Example: `http://10.5.50.1/login` or custom domain

**Voucher Template**: Select print template for vouchers

**Monthly ISP Cost**: Optional tracking of your ISP expenses

**Zone**: Assign to a location zone for organization

**Logo**: Upload custom logo for voucher branding

### Step 3: Save Router

Click "Save Router" to add the configuration.

## Package Limits

Your subscription package determines:
- **Max Routers**: Maximum routers you can add
- **Max Users Per Router**: Users per router limit
- **Max Zones**: Location zones allowed

Check your current usage on the dashboard.

## Troubleshooting

### Cannot Connect
- Verify API is enabled: `/ip service print`
- Check firewall rules allow port 8728
- Ping the router IP from server
- Verify username/password are correct

### Connection Timeout
- Ensure network path is accessible
- Check router firewall
- Verify port is correct

### Authentication Failed
- Confirm username has API permissions
- Try connecting via WinBox first
- Check user group has API policy

## Next Steps

After adding router:
1. Create user profiles for hotspot
2. Generate vouchers
3. Configure zones (optional)
4. View router statistics on dashboard
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Creating Hotspot Profiles',
                'category' => 'Configuration',
                'content' => <<<'MD'
# Creating Hotspot Profiles

## What are Hotspot Profiles?

Profiles define speed limits, time validity, and data quotas for WiFi users. Each voucher or single user is assigned a profile.

## Creating a Profile

### Navigate to Profiles
Dashboard → Profiles → "Add User Profile"

### Profile Fields

**Profile Name**: Unique identifier
- Format: `SPEED-DURATION`
- Examples: `10M-1d`, `5M-1h`, `50M-30d`
- Use letters, numbers, hyphens, underscores only

**Rate Limit**: Bandwidth speed control
- Format: `RX/TX` or single value for both
- Examples:
  - `5M` - 5 Mbps upload and download
  - `10M/20M` - 10M upload, 20M download
  - `512k/2M` - 512 Kbps up, 2 Mbps down

**Shared Users**: How many devices can use one account
- `1` - Single device only
- `2` - Two devices simultaneously
- Useful for family/multiple device access

**Validity**: How long the profile is valid
- Format: `NdNhNmNs`
- Examples:
  - `1h` - 1 hour
  - `1d` - 1 day
  - `7d` - 7 days
  - `30d` - 30 days
  - `2h30m` - 2 hours 30 minutes

**MAC Binding**: Lock user to specific device
- Enable for single device restriction
- Prevents account sharing

**Price**: Cost of profile (optional)
- For billing/tracking purposes
- Displayed on vouchers

**Description**: Notes about the profile
- Example: "Basic hourly access for cafe guests"

## Best Practices

### Naming Convention
Use clear, descriptive names:
- `Cafe-1Hour` instead of `profile1`
- `Premium-30Days` instead of `p30`
- `Student-Daily` instead of `sd`

### Speed Guidelines
Consider your total bandwidth:
- If you have 100 Mbps, don't oversell
- Calculate: (Users × Speed) ≤ Total Bandwidth
- Leave headroom for overhead

### Common Profiles

**Quick Access** (Cafes, Restaurants)
- 1 Hour / 5 Mbps / $1

**Daily Pass** (Hotels, Offices)
- 1 Day / 10 Mbps / $3

**Weekly** (Short-term rentals)
- 7 Days / 20 Mbps / $10

**Monthly** (Residential)
- 30 Days / 50 Mbps / $30

## Using Profiles

After creating profiles:
1. Assign to routers when generating vouchers
2. Select when creating single hotspot users
3. Profiles sync to MikroTik automatically

## Editing Profiles

To modify an existing profile:
1. Go to Profiles list
2. Click Edit icon
3. Update fields
4. Click "Update Profile"

**Note**: Changes affect new users only, not existing active sessions.

## Deleting Profiles

Profiles with active users cannot be deleted. First:
1. Wait for all users to expire
2. Or migrate users to another profile
3. Then delete the unused profile
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Generating Vouchers',
                'category' => 'Operations',
                'content' => <<<'MD'
# Generating Vouchers

## Overview

Vouchers are WiFi access codes that provide temporary internet access. RADTik allows bulk generation with customizable formats.

## Generate Vouchers

### Navigate to Generation
Dashboard → Vouchers → "Generate Vouchers"

### Configuration Options

**Select Router**: Choose which router will handle these vouchers
- Only shows routers you have access to

**Profile**: Select hotspot profile
- Determines speed, validity, and price
- Must create profiles first

**Quantity**: Number of vouchers to generate
- Range: 1 to 1000 per batch
- Limited by your package

**Code Length**: Character length of voucher code
- Range: 4 to 32 characters
- Recommended: 8-12 characters
- Longer = more secure, harder to type

**Prefix**: Optional code prefix
- Examples: `CAFE-`, `MTN-`, `DAY-`
- Helps categorize vouchers
- Max 10 characters

**Character Type**: Format of generated codes
- **Uppercase Letters**: ABC123 (easiest to read)
- **Lowercase Letters**: abc123
- **Numbers Only**: 123456
- **Mixed**: AbC123 (most secure)

**Serial Number**: Optional sequential numbering
- Starting number for series
- Example: Start at 1000 = CODE-1000, CODE-1001, etc.

## Generation Process

1. Fill in all required fields
2. Click "Generate Vouchers"
3. System creates vouchers in database
4. Vouchers ready to print immediately

## Viewing Vouchers

After generation:
- View in Vouchers list
- Filter by router, profile, status
- Search by username
- Export to PDF for printing

## Printing Vouchers

### Print Options
1. Single voucher: Click print icon
2. Bulk print: Select multiple → Print Selected
3. Batch print: Filter by criteria → Print All

### Print Templates
Choose from 5 professional templates:
- **Template 1**: Compact grid (48 per A4)
- **Template 2**: Business card style
- **Template 3**: Thermal receipt (80mm)
- **Template 4**: QR code style
- **Template 5**: Vintage ticket

Templates configured in Router settings.

## Voucher Status

**Inactive**: Generated, not yet used
**Active**: User logged in, currently valid
**Expired**: Validity period ended
**Disabled**: Manually deactivated

## Best Practices

### Quantity Planning
- Generate in reasonable batches
- Don't over-generate (wastage)
- Track usage patterns

### Code Format
For manual entry (cards, displays):
- 6-8 characters
- Uppercase only
- No ambiguous characters (0/O, 1/I)

For QR codes/print-only:
- 10-12 characters
- Mixed case
- Higher security

### Organization
- Use prefixes for different locations
- Name batches descriptively
- Track expiry dates

### Security
- Shorter validity = more secure
- Don't share unused vouchers publicly
- Deactivate if compromised

## Package Limits

Your subscription determines:
- Max vouchers per router
- Total vouchers across all routers
- Check dashboard for current usage

## Voucher Logs

Track voucher usage:
- First activation timestamp
- Total data usage
- Session history
- Export reports

Navigate to: Vouchers → Voucher Logs
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Managing Zones',
                'category' => 'Organization',
                'content' => <<<'MD'
# Managing Zones

## What are Zones?

Zones help organize routers by location, area, or branch. Think of them as folders for grouping routers.

## Use Cases

- **ISPs**: Group routers by coverage area (North Zone, South Zone)
- **Multi-branch**: Organize by branch location (HQ, Branch 1, Branch 2)
- **Hotels**: Separate by building or floor
- **Cafes**: Group by city or neighborhood

## Creating a Zone

### Navigate to Zones
Dashboard → Zones

### Zone Fields

**Zone Name**: Descriptive location name
- Examples: "Downtown Area", "Branch Office Dhaka", "Main Campus"
- Keep names clear and searchable

**Description**: Additional details (optional)
- Coverage area info
- Contact person
- Network notes

**Color Tag**: Visual identifier
- Choose a unique color per zone
- Makes router lists easier to scan
- Uses hex color picker

**Status**: Active/Inactive toggle
- Inactive zones hidden from router assignment
- Doesn't affect existing router assignments

## Assigning Routers to Zones

### During Router Creation
1. Add new router
2. Select zone from dropdown
3. Save router

### Update Existing Router
1. Edit router
2. Change zone assignment
3. Update

### Bulk Assignment
Currently manual - edit each router individually.

## Zone Statistics

View on Zones page:
- Number of routers per zone
- Router count displayed next to zone name
- Click zone name to filter routers

## Editing Zones

1. Click Edit icon on zone
2. Update fields
3. Click "Update Zone"

Changes reflect immediately on all assigned routers.

## Deleting Zones

**Requirements**:
- Zone must have zero routers assigned
- Detach all routers first
- Then delete zone

**Steps**:
1. Edit routers, remove zone assignment
2. Click Delete icon on empty zone
3. Confirm deletion

## Zone Colors

**Tips for choosing colors**:
- Use distinct colors for easy identification
- Consistent color scheme across system
- Consider color-blind friendly palette
- Default color: Blue (#2563eb)

**Suggested Palette**:
- Primary locations: Bright colors (Red, Blue, Green)
- Secondary locations: Muted tones
- Inactive/testing: Gray

## Package Limits

Your subscription controls max zones:
- Check Package details for limit
- Some packages have unlimited zones
- Upgrade if you need more

## Best Practices

### Naming Convention
Be consistent:
- `City - Branch`: Dhaka - Gulshan, Dhaka - Dhanmondi
- `Region - Area`: North - Uttara, South - Motijheel
- `Floor - Building`: Floor 1 - Building A

### Organization Strategy
- Start broad, refine later
- Don't over-categorize
- Group logically for your business
- Consider future growth

### Search and Filter
- Use zone names as keywords
- Filter router lists by zone
- Export reports per zone

## Reporting by Zone

Generate zone-based reports:
- Router performance per zone
- User count by location
- Revenue tracking
- Bandwidth usage

Navigate to: Reports → Filter by Zone
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Creating Single Hotspot Users',
                'category' => 'User Management',
                'content' => <<<'MD'
# Creating Single Hotspot Users

## Overview

Single hotspot users are permanent access accounts created individually, unlike temporary vouchers. Ideal for staff, VIPs, or long-term customers.

## When to Use

**Use Single Users for**:
- Staff/employee access
- VIP customers
- Long-term contracts
- Specific named accounts
- Accounts needing tracking

**Use Vouchers for**:
- Temporary guests
- Daily/hourly access
- Bulk generation
- Anonymous users

## Creating a User

### Navigate
Dashboard → Hotspot Users → "Create Single User"

### User Fields

**Select Router**: Choose router for this user
- User will connect to this specific router
- Can create same username on different routers

**Username**: Unique account identifier
- 3-64 characters
- Letters, numbers, hyphens, underscores
- Examples: `john.doe`, `staff001`, `vip-customer`
- Case-sensitive

**Password**: Access password
- 3-64 characters
- Secure for permanent accounts
- User will type this to login
- Can be simple for known users

**Hotspot Profile**: Select speed/time profile
- If blank, uses router default profile
- Override with custom profile
- Determines bandwidth and access

## User Creation Process

1. Fill in all required fields
2. Click "Create User"
3. System creates in database
4. Syncs to MikroTik router immediately
5. User can login right away

## After Creation

User account appears in:
- Hotspot Users list
- MikroTik router's hotspot user list
- Active when user logs in

## Viewing Users

Navigate to: Hotspot Users

**Information shown**:
- Username
- Assigned router
- Profile
- Status (active/inactive)
- Creation date
- Last login
- Data usage

## Editing Users

1. Click Edit icon on user
2. Update fields (except username on some routers)
3. Save changes
4. Syncs to router

**Editable fields**:
- Password
- Profile
- Status

## Deleting Users

**Steps**:
1. Click Delete icon
2. Confirm deletion
3. Removes from database and router

**Note**: Active sessions disconnect immediately.

## Active Sessions

View currently connected users:
- Navigate to: Dashboard → Active Sessions
- See who's online in real-time
- Disconnect users if needed

**Session info**:
- Username
- IP address
- MAC address
- Connected duration
- Data transferred
- Current bandwidth

## Disconnecting Users

From Active Sessions:
1. Find user
2. Click Disconnect button
3. User must re-login

## User Profiles

Assign different profiles for different user types:

**Staff**: Unlimited time, high speed
**VIP Customers**: High priority bandwidth
**Regular Users**: Standard limits
**Test Accounts**: Low limits for testing

## Best Practices

### Username Convention
- `staff.firstname`: staff.john
- `dept-name`: sales-manager
- `customer-id`: cust-10523
- Be consistent across organization

### Password Security
For permanent accounts:
- Use strong passwords
- Change periodically
- Don't share passwords
- Document safely

For temporary known users:
- Simple memorable passwords OK
- Clear communication
- Provide written credentials

### Tracking
- Keep notes in user descriptions
- Track creation dates
- Monitor usage patterns
- Remove unused accounts

### Limits
- Check package limits
- Max users per router
- Don't exceed capacity
- Clean up old accounts

## Troubleshooting

### User Cannot Login
- Verify username/password spelling
- Check router connection
- Ensure router is online
- Verify profile exists

### Already Online Error
- User may have active session
- Disconnect from Active Sessions
- Or wait for timeout

### Profile Not Working
- Verify profile exists on router
- Check profile speed limits
- Test with default profile
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Package Subscription System',
                'category' => 'Billing',
                'content' => <<<'MD'
# Package Subscription System

## Overview

RADTik uses a package-based subscription model. Your package determines the resources available to you.

## Package Features

Each package defines:
- **Max Routers**: Number of routers you can add
- **Max Users Per Router**: Hotspot users per router
- **Max Zones**: Location zones allowed
- **Max Vouchers Per Router**: Voucher limit per router
- **Grace Period**: Days after expiry before restrictions

## Available Packages

Packages are created by superadmin. Common tiers:

**Starter**: Small businesses
- 5 routers
- 100 users per router
- 3 zones
- 1000 vouchers per router

**Professional**: Medium businesses
- 20 routers
- 500 users per router
- 10 zones
- 5000 vouchers per router

**Enterprise**: Large ISPs
- Unlimited routers
- Unlimited users
- Unlimited zones
- Unlimited vouchers

## Checking Your Package

### Dashboard View
- Package name displayed on dashboard
- Current usage vs limits shown
- Expiry date visible

### Detailed View
Navigate to: Subscription → View Details

Shows:
- Package name and price
- All limits and current usage
- Subscription status
- Next billing date
- Auto-renewal status

## Package Limits

When you reach a limit:
- System prevents creating more resources
- Warning message shows current usage
- Prompt to upgrade package

**Example**:
```
Router Limit Reached
You have 5 of 5 routers allowed by your Starter package.
Upgrade to add more routers.
```

## Subscription States

**Active**: Full access to all features

**Grace Period**: Expired but still functional
- Days defined by package
- Usually 3-7 days
- Warning displayed
- Renew soon to avoid suspension

**Suspended**: Expired + grace period ended
- Can login and view
- Cannot create new resources
- Cannot generate vouchers
- Existing users still work

## Upgrading Package

1. Navigate to: Subscriptions
2. Click "Upgrade Package"
3. Select new package
4. Make payment
5. Instant upgrade

**Notes**:
- Prorated billing
- Takes effect immediately
- No downtime
- Previous limits removed

## Downgrading Package

To downgrade:
1. Reduce resources first (delete routers/zones)
2. Contact support
3. Schedule downgrade for next billing cycle

**Requirements**:
- Must be below new package limits
- Cannot have more resources than new package allows

## Auto-Renewal

Enable auto-renewal for uninterrupted service:
1. Go to Subscription settings
2. Enable "Auto-Renewal"
3. Payment charged automatically on expiry
4. Email notification sent

**Benefits**:
- No service interruption
- No grace period needed
- Automatic billing
- Email confirmations

## Payment Methods

Supported gateways:
- **Cryptomus**: Cryptocurrency (USDT, BTC, ETH)
- **PayStation**: Bangladesh local (Bkash, Nagad, Cards)

Add balance first, then subscribe:
1. Add Balance → Select gateway → Pay
2. Balance added to account
3. Purchase subscription from balance

## Invoices

All transactions recorded:
- Navigate to: Billing → Invoices
- View payment history
- Download invoice PDFs
- Track renewals

**Invoice details**:
- Invoice number
- Date and time
- Package name
- Amount paid
- Payment gateway
- Transaction ID

## Grace Period

After subscription expires:
- Access continues for grace period days
- Create/edit functions restricted
- Existing users work normally
- Red warning displayed

**During grace period**:
- Renew immediately
- Add balance and resubscribe
- Avoid suspension

## Notifications

Email notifications sent for:
- Subscription activated
- Renewal successful
- Expiry in 7 days
- Expiry in 3 days
- Expired (grace period started)
- Suspended

Enable/disable in: Settings → Notifications

## Best Practices

### Choose Right Package
- Estimate your needs
- Consider growth
- Better to have headroom
- Upgrade is easy

### Monitor Usage
- Check limits regularly
- Plan before reaching limits
- Upgrade proactively

### Enable Auto-Renewal
- Avoid interruptions
- Set and forget
- Keep balance sufficient

### Keep Balance
- Maintain balance for auto-renewal
- Top up before expiry
- Buffer amount recommended

## FAQs

**Q: What happens to my data if subscription expires?**
A: Data is safe. Access is restricted until renewal.

**Q: Can I change package mid-cycle?**
A: Yes, upgrade anytime. Downgrade at renewal.

**Q: Are there refunds?**
A: Contact support for refund policy.

**Q: Do unused resources roll over?**
A: No, limits are per subscription period.
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Support Ticket System',
                'category' => 'Support',
                'content' => <<<'MD'
# Support Ticket System

## Overview

The ticket system allows you to get help from support team. Create tickets for technical issues, questions, or feature requests.

## Creating a Ticket

### Navigate to Tickets
Dashboard → Tickets → "New Ticket"

### Ticket Fields

**Subject**: Brief description of issue
- Clear and specific
- Example: "Router connection failing" not "Help"

**Description**: Detailed explanation
- What's the problem?
- What have you tried?
- When did it start?
- Include error messages
- Add screenshots if helpful

**Priority**: Urgency level
- **Low**: General questions, non-urgent
- **Normal**: Standard issues, default
- **High**: Service affecting, urgent

## Ticket Workflow

**Open**: New ticket created
- Awaiting initial response
- Support team notified

**In Progress**: Support team working on it
- Investigation ongoing
- May request more info

**Solved**: Issue resolved
- Solution provided
- Can reopen if needed

**Closed**: Ticket completed
- No further action needed
- Archived for records

## Messaging on Tickets

### Replying to Tickets
1. Open ticket from list
2. Scroll to message box
3. Type your message
4. Click "Send Message"

**Messages appear**:
- Timeline format
- Your messages highlighted
- Support replies in thread
- Timestamps on all messages

### Message Notifications
Email sent when:
- Support replies to your ticket
- Status changes
- Ticket assigned

## Viewing Your Tickets

### Ticket List
Shows all your tickets:
- Subject and ID
- Current status
- Priority level
- Creation date
- Last update

### Filter by Status
Click status buttons:
- All
- Open
- In Progress
- Solved
- Closed

### Search Tickets
Use search box to find:
- By subject
- By ticket ID
- By keywords

## Ticket Details

Click on ticket to view:
- Full description
- Status and priority
- Creation date
- All messages
- Support team member assigned

## Best Practices

### Creating Effective Tickets

**Do**:
- Clear subject line
- Detailed description
- Include steps to reproduce
- Add relevant info (router name, voucher ID, etc.)
- Be specific

**Don't**:
- Vague subjects
- Missing details
- Multiple unrelated issues in one ticket
- ALL CAPS
- Demanding/rude language

### Response Time

Typical response times:
- **High Priority**: Within 4 hours
- **Normal Priority**: Within 24 hours
- **Low Priority**: Within 48 hours

*Times may vary based on support availability*

### Following Up

If no response after expected time:
- Reply to ticket with follow-up
- Don't create duplicate tickets
- Be patient

## Support Team Actions

Support can:
- View all your tickets
- Reply with solutions
- Change status
- Assign to specialists
- Request more information
- Close resolved tickets

You can:
- Create tickets
- View own tickets only
- Reply to messages
- Cannot change status
- Cannot assign tickets

## Common Ticket Types

**Technical Issues**:
- Router connection problems
- API errors
- Voucher generation issues
- User login failures

**Questions**:
- How to configure something
- Feature explanations
- Best practices
- Usage guidance

**Billing**:
- Payment issues
- Invoice questions
- Package upgrades
- Refund requests

**Feature Requests**:
- New functionality suggestions
- Improvements
- Integration requests

## After Resolution

When ticket marked **Solved**:
1. Review the solution
2. Test if issue fixed
3. Reply if still having problems
4. Ticket reopens automatically
5. Or confirm solved

## Tips for Quick Resolution

1. **Check Documentation First**: Many answers in knowledge base
2. **Search Closed Tickets**: Similar issues may be solved
3. **Include Details**: Router logs, error messages, screenshots
4. **One Issue Per Ticket**: Separate tickets for different problems
5. **Test Solutions**: Try suggested fixes, report results
6. **Be Responsive**: Reply promptly to support queries

## Emergency Support

For critical service-affecting issues:
1. Create HIGH priority ticket
2. Include "URGENT" in subject
3. Describe impact clearly
4. If available, use emergency contact

## Ticket History

All tickets preserved:
- Historical record
- Reference for future issues
- Track problem patterns
- Download tickets as needed
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Two-Factor Authentication Setup',
                'category' => 'Security',
                'content' => <<<'MD'
# Two-Factor Authentication (2FA) Setup

## What is 2FA?

Two-Factor Authentication adds an extra security layer to your account. Even if someone steals your password, they cannot login without your phone.

## Why Enable 2FA?

**Security Benefits**:
- Protects against password theft
- Prevents unauthorized access
- Secures sensitive data
- Required for compliance

**Recommended for**:
- Admin and superadmin accounts
- Accounts with payment access
- Multi-user environments
- High-value operations

## Enabling 2FA

### Prerequisites
- Smartphone with camera
- Authenticator app installed
  - Google Authenticator
  - Microsoft Authenticator
  - Authy
  - Any TOTP app

### Setup Steps

1. **Navigate to Settings**
   Dashboard → Profile → Two-Factor Authentication

2. **Enable 2FA**
   Click "Enable Two-Factor Authentication"

3. **Scan QR Code**
   - Open authenticator app
   - Tap "Add Account" or "+"
   - Scan QR code displayed on screen
   - Or manually enter secret key

4. **Verify Setup**
   - App generates 6-digit code
   - Enter code in RADTik
   - Click "Verify and Enable"

5. **Save Recovery Codes**
   - Download recovery codes
   - Store safely offline
   - Use if phone lost

## Logging In with 2FA

After password entry:
1. System prompts for 2FA code
2. Open authenticator app
3. View 6-digit code for RADTik
4. Enter code
5. Login successful

**Note**: Codes change every 30 seconds.

## Recovery Codes

During 2FA setup, you receive recovery codes.

**Important**:
- Download and save immediately
- Store in secure location
- Each code works only once
- Use if cannot access authenticator app

### Using Recovery Code
1. Login page → Enter password
2. Click "Use Recovery Code"
3. Enter one recovery code
4. Access granted
5. Setup 2FA again with new device

## Lost Phone / Device

If you lose device with authenticator:

### Option 1: Use Recovery Code
- Enter recovery code to login
- Setup 2FA on new device
- Generate new recovery codes

### Option 2: Contact Support
- Create support ticket
- Verify identity
- Support can disable 2FA
- Setup again after access restored

## Disabling 2FA

To turn off 2FA:
1. Navigate to Settings → 2FA
2. Click "Disable Two-Factor Authentication"
3. Enter current 2FA code
4. Confirm disable

**Not recommended for admin accounts**

## Multiple Devices

Setup on multiple devices:
1. During initial setup, scan QR with all devices
2. Or share same secret key
3. All devices generate same codes
4. Any device can be used for login

## Troubleshooting

### Code Not Working

**Check**:
- Code hasn't expired (30 sec limit)
- Device time is synchronized
- Trying latest code
- Caps lock not on

**Fix**:
- Wait for next code
- Sync phone time (Settings → Date & Time → Auto)
- Restart authenticator app

### Lost Authenticator App

1. Reinstall app
2. Use recovery code to login
3. Setup 2FA again
4. New QR code generated

### Wrong Time on Phone

Authenticator codes time-based:
- Phone time must be accurate
- Enable auto time sync
- Check timezone settings

### Recovery Codes Not Working

Each code works only once:
- Don't reuse codes
- Contact support if all codes used

## Best Practices

### Security Tips
- Don't share 2FA codes
- Keep recovery codes offline
- Don't screenshot QR code
- Use strong password too

### App Recommendations
**Google Authenticator**: Simple, reliable
**Microsoft Authenticator**: Cloud backup
**Authy**: Multi-device sync

### Backup Strategy
1. Save recovery codes securely
2. Setup on 2+ devices
3. Document account access procedure
4. Share recovery plan with team

## Admin Recommendations

For organizations:
1. **Require 2FA** for all admin accounts
2. **Document** recovery procedures
3. **Store** recovery codes in secure vault
4. **Regular** security audits
5. **Train** staff on 2FA use

## Team Access

When team member leaves:
1. Disable their account immediately
2. Reset 2FA for shared accounts
3. Generate new recovery codes
4. Update access documentation

## Mobile vs Desktop

2FA works on both:
- **Mobile**: Scan QR with phone camera
- **Desktop**: Use authenticator app on phone
- Same process for login

## FAQs

**Q: Is 2FA required?**
A: Optional but strongly recommended for admins.

**Q: Can I use SMS for 2FA?**
A: Currently only authenticator apps supported.

**Q: What if I lose recovery codes?**
A: Contact support to disable 2FA and reset.

**Q: Does 2FA slow down login?**
A: Adds 5-10 seconds, worth the security.

**Q: Can I trust authenticator apps?**
A: Yes, they don't need internet and are secure.
MD,
                'is_active' => true,
            ],
        ];

        foreach ($articles as $article) {
            DocumentationArticle::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($article['title'])],
                $article
            );
        }

        $this->command->info('✅ Documentation articles created');
    }
}
