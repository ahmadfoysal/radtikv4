<?php

namespace Database\Seeders;

use App\Models\KnowledgebaseArticle;
use Illuminate\Database\Seeder;

class KnowledgebaseArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates helpful knowledgebase articles for users.
     */
    public function run(): void
    {
        $this->command->info('📚 Creating knowledgebase articles...');

        $articles = [
            [
                'title' => 'How do I add my first router?',
                'category' => 'Getting Started',
                'content' => <<<'MD'
To add your first router to RADTik, ensure your MikroTik router has API enabled on port 8728. Navigate to Dashboard → Routers → "Add Router". Enter your router name, IP address, admin username, and password. Select a voucher template and zone (optional). Click "Save Router" and RADTik will connect to your MikroTik device. The router will appear in your router list immediately.

**Quick checklist:**
- API enabled on router (`/ip service enable api`)
- Firewall allows port 8728
- Correct admin credentials
- Router is reachable from RADTik server

If connection fails, verify your router is online and API service is running. Check the error message for specific issues like authentication failures or timeout errors.
MD,
                'is_active' => true,
            ],
            [
                'title' => 'What is the difference between vouchers and single users?',
                'category' => 'Users',
                'content' => <<<'MD'
**Vouchers** are temporary access codes generated in bulk for guests, customers, or short-term users. They are anonymous, have expiry dates, and are ideal for cafes, hotels, or daily WiFi access. You can generate hundreds at once and print them for distribution.

**Single users** are permanent individual accounts created with specific usernames and passwords. They are perfect for staff, VIP customers, or long-term contracts. Single users don't expire automatically and can be tracked by name. Use them when you need accountability and personalized access.

**When to use vouchers:** Guest WiFi, daily passes, temporary access
**When to use single users:** Employees, monthly subscriptions, named accounts

Both types use the same profiles for speed and time limits.
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Why can\'t I add more routers?',
                'category' => 'Packages',
                'content' => <<<'MD'
You have reached your package limit for routers. Each subscription package has a maximum number of routers allowed. Check your current package details on the dashboard to see your limit.

**To add more routers:**
1. View your current package: Dashboard → Subscription
2. See max routers allowed vs your current usage
3. Upgrade to a higher package: Subscription → Upgrade
4. Select a package with more router slots
5. Complete payment
6. Immediately add more routers

Your current routers and all data remain intact when you upgrade. The upgrade takes effect instantly with no downtime. Some packages offer unlimited routers for enterprise users.

If you temporarily need to add a router, you can delete an unused router to free up a slot, then add the new one.
MD,
                'is_active' => true,
            ],
            [
                'title' => 'How do I create profiles with different speeds?',
                'category' => 'Configuration',
                'content' => <<<'MD'
Profiles control bandwidth speed, time limits, and access rules for WiFi users. To create speed-limited profiles:

1. Navigate to Dashboard → Profiles → "Add User Profile"
2. **Profile Name:** Use format like "10M-1Day" or "5Mbps-Hourly"
3. **Rate Limit:** Enter speed in format RX/TX
   - `5M` = 5 Mbps both directions
   - `10M/20M` = 10 Mbps upload, 20 Mbps download
   - `512k/2M` = 512 Kbps upload, 2 Mbps download
4. **Validity:** Set expiry time like `1h`, `1d`, `7d`, `30d`
5. **Shared Users:** Number of simultaneous devices allowed
6. **Price:** Optional for tracking/display
7. Click "Save Profile"

**Common profiles:**
- Quick Access: 5M / 1 hour
- Daily Pass: 10M / 1 day
- Weekly: 20M / 7 days
- Monthly: 50M / 30 days

Profiles can be assigned when generating vouchers or creating single users.
MD,
                'is_active' => true,
            ],
            [
                'title' => 'How does the grace period work?',
                'category' => 'Billing',
                'content' => <<<'MD'
The grace period is a buffer time after your subscription expires, allowing you to renew without immediate service interruption.

**How it works:**
- Your subscription expires on the due date
- System enters grace period (usually 3-7 days based on your package)
- You can still login and view your dashboard
- Existing users continue to work normally
- **You cannot create new resources:** No new routers, vouchers, or users
- **You can still edit** existing resources

**During grace period:**
- Large warning banner appears
- Days remaining countdown shown
- Email reminders sent
- Add balance and renew subscription to restore full access

**After grace period ends:**
- Account becomes suspended
- View-only access
- Cannot manage anything
- Existing users still work (no disruption to your customers)
- Renew anytime to restore access

**Best practice:** Enable auto-renewal to avoid grace period entirely. Keep sufficient balance in your account for automatic renewals.
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Can I print vouchers in custom formats?',
                'category' => 'Vouchers',
                'content' => <<<'MD'
Yes! RADTik includes 5 professional voucher templates optimized for different printing scenarios:

**Template 1 - Compact Grid:** 48 vouchers per A4 page, perfect for cardstock cutting
**Template 2 - Business Card:** Standard card size with large code display
**Template 3 - Thermal Receipt:** 80mm thermal printer format
**Template 4 - QR Code Style:** Includes scannable QR codes
**Template 5 - Vintage Ticket:** Decorative ticket-style design

**To select template:**
1. Edit your router settings
2. Choose voucher template from dropdown
3. Template applies to all vouchers for that router

**To print:**
1. Generate or view existing vouchers
2. Select vouchers to print (or print all)
3. Click "Print" button
4. Print dialog opens with chosen template
5. Adjust printer settings as needed

Each template includes router name, login code, validity period, profile name, and login address. Templates are print-optimized with proper sizing, borders, and color adjustments for best quality.

For bulk printing, select your template before generating vouchers. You can print hundreds at once efficiently.
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Why is my router showing as offline?',
                'category' => 'Troubleshooting',
                'content' => <<<'MD'
If your router shows offline status, RADTik cannot connect to the MikroTik API. Check these common causes:

**1. Router is actually offline**
- Power failure or reboot
- Network disconnected
- Wait for router to come back online

**2. API service disabled**
Connect via WinBox and check:
```
/ip service print
```
If API shows "disabled", enable it:
```
/ip service enable api
```

**3. Firewall blocking API port**
Check if port 8728 is allowed:
```
/ip firewall filter print
```
Add rule to allow API:
```
/ip firewall filter add chain=input protocol=tcp dst-port=8728 action=accept
```

**4. IP address changed**
- Router got new IP from DHCP
- Update router IP in RADTik settings
- Edit router → Update address → Save

**5. Credentials changed**
- Router password was changed
- Edit router in RADTik
- Update username/password
- Save changes

**6. Network connectivity**
- VPN disconnected
- ISP issue
- Ping router IP from RADTik server
- Check network path

**Quick test:** Try connecting to router via WinBox from same network as RADTik. If WinBox works but RADTik doesn't, issue is likely credentials or firewall.
MD,
                'is_active' => true,
            ],
            [
                'title' => 'How do I organize routers with zones?',
                'category' => 'Organization',
                'content' => <<<'MD'
Zones are location-based groups for organizing multiple routers. They are perfect for ISPs with coverage areas, businesses with multiple branches, or hotels with different buildings.

**Creating zones:**
1. Go to Dashboard → Zones
2. Enter zone name (e.g., "Downtown Branch", "North Coverage Area")
3. Add description (optional, like coverage details)
4. Pick a color for visual identification
5. Click "Create Zone"

**Assigning routers to zones:**
- When adding new router: Select zone from dropdown
- For existing routers: Edit router → Select zone → Update

**Benefits of zones:**
- **Visual organization:** Color-coded router lists
- **Easy filtering:** View routers by location
- **Reports by zone:** Track performance per area
- **Quick navigation:** Find routers by branch/location
- **Team management:** Assign resellers to specific zones

**Example use cases:**
- **ISP:** North Zone, South Zone, East Zone, West Zone
- **Hotel Chain:** Hotel A, Hotel B, Hotel C
- **Multi-branch cafe:** Downtown, Uptown, Suburb 1, Suburb 2
- **Campus network:** Building A, Building B, Library, Dorms

**Zone limits:**
Your subscription package may limit total zones. Check Subscription → Package Details. Some packages offer unlimited zones.

**Best practice:** Plan your zone structure before adding many routers. Consistent naming helps with scaling and reporting.
MD,
                'is_active' => true,
            ],
        ];

        foreach ($articles as $article) {
            KnowledgebaseArticle::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($article['title'])],
                $article
            );
        }

        $this->command->info('✅ Knowledgebase articles created');
    }
}
