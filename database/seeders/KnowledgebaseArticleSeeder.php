<?php

namespace Database\Seeders;

use App\Models\KnowledgebaseArticle;
use Illuminate\Database\Seeder;

class KnowledgebaseArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $articles = [
            [
                'title' => 'How to Add a MikroTik Router',
                'category' => 'getting-started',
                'content' => "Adding a MikroTik router to RADTik is simple and straightforward. Follow these steps:\n\n1. Navigate to Routers menu in the sidebar\n2. Click on 'Add New Router' button\n3. Fill in the required information:\n   - Router Name: Give your router a meaningful name\n   - IP Address: Enter the router's IP address\n   - Username: MikroTik admin username (usually 'admin')\n   - Password: Your MikroTik router password\n   - Port: API port (default: 8728)\n   - SSH Port: SSH port for remote management (default: 22)\n\n4. Select the Zone if you have multiple locations\n5. Choose a voucher template for printing\n6. Click 'Save' to add the router\n\nOnce added, the system will attempt to connect to your router. You can test the connection using the 'Ping' button on the router card.\n\nTroubleshooting:\n- Ensure MikroTik API is enabled on your router\n- Check firewall rules allow connections from your RADTik server\n- Verify username and password are correct\n- Make sure the router is reachable from your server",
                'is_active' => true,
            ],
            [
                'title' => 'Understanding Automatic Renewal',
                'category' => 'billing',
                'content' => "Automatic renewal is a powerful feature that ensures uninterrupted service for your routers.\n\nHow It Works:\nWhen automatic renewal is enabled for a package, the system will automatically attempt to renew the router's subscription when it's about to expire.\n\nRenewal Process:\n1. The system checks for expiring packages 24 hours before expiration\n2. If auto-renew is enabled and the user has sufficient balance, renewal is processed\n3. Payment is deducted from the user's account balance\n4. A new billing cycle begins immediately\n5. User receives notification of successful renewal\n\nEnabling Auto-Renewal:\n- Go to your router details page\n- Find the package information section\n- Toggle the 'Auto Renew' switch to ON\n- Ensure your account has sufficient balance\n\nBenefits:\n- No service interruption\n- Hands-free management\n- Automated billing\n- Email notifications keep you informed\n\nNote: Auto-renewal requires sufficient account balance. If balance is insufficient, renewal will fail and you'll receive a notification.",
                'is_active' => true,
            ],
            [
                'title' => 'How Vouchers Sync with MikroTik',
                'category' => 'vouchers',
                'content' => "RADTik provides seamless synchronization with MikroTik hotspot for voucher management.\n\nVoucher Generation:\nWhen you generate vouchers:\n- Vouchers are created in the RADTik database\n- They are NOT automatically pushed to MikroTik\n- Users are created in MikroTik when voucher is activated\n- System uses MikroTik API to manage users\n- Profile settings are synced from your MikroTik router\n\nSynchronization Features:\n1. Pull Active Users: Fetch currently active users from MikroTik\n2. Pull Inactive Users: Get inactive vouchers from MikroTik\n3. Push Active Users: Send activated vouchers to MikroTik\n4. Sync Orphans: Clean up users that exist in MikroTik but not in RADTik\n\nAPI Endpoints:\n- /mikrotik/api/pull-active-users\n- /mikrotik/api/pull-inactive-users\n- /mikrotik/api/push-active-users\n- /mikrotik/api/sync-orphans\n\nBest Practices:\n- Set up automatic sync using MikroTik schedulers\n- Monitor sync logs for any issues\n- Keep router subscription active for API access",
                'is_active' => true,
            ],
            [
                'title' => 'Package Management Guide',
                'category' => 'packages',
                'content' => "Packages define the subscription plans for routers in your RADTik system.\n\nWhat is a Package?\nA package is a subscription plan that includes:\n- User limit: Maximum number of vouchers/users\n- Billing cycle: Monthly, Quarterly, or Yearly\n- Price: Cost per billing cycle\n- Auto-renewal option\n- Validity period\n\nCreating a Package:\n1. Navigate to Packages menu\n2. Click 'Add Package'\n3. Fill in package details:\n   - Package Name (e.g., 'Basic Plan', 'Premium Plan')\n   - User Limit (e.g., 100, 500, 1000, or unlimited)\n   - Price per billing cycle\n   - Billing cycle (monthly/quarterly/yearly)\n   - Description\n\n4. Save the package\n\nAssigning Packages to Routers:\n- Go to the router details page\n- Select a package from available options\n- Set the start date and end date\n- Choose whether to allow auto-renewal\n- Save changes\n\nPackage Features:\n- Flexible user limits\n- Multiple billing cycles\n- Auto-renewal capability\n- Usage tracking\n- Expiration notifications\n\nMonitoring Usage:\nThe system tracks:\n- Total users created\n- Active users\n- Inactive users\n- Expired users\n- Usage percentage vs. limit\n\nUpgrading/Downgrading:\n- You can change packages at any time\n- Prorated billing is calculated automatically\n- Existing users are not affected\n- New limits apply from change date",
                'is_active' => true,
            ],
            [
                'title' => 'Payment & Billing System Usage',
                'category' => 'billing',
                'content' => "RADTik includes a comprehensive billing system for managing payments and account balances.\n\nAccount Balance:\nEach user has an account balance that can be used for:\n- Router package renewals\n- Additional services\n- One-time purchases\n\nAdding Balance:\n1. Navigate to Billing → Add Balance\n2. Enter the amount\n3. Select payment method:\n   - Manual payment (for admin/reseller)\n   - Online payment gateway (Cryptomus, PayStation)\n4. Complete the payment process\n5. Balance is added to your account immediately\n\nPayment Gateways:\nRADTik supports multiple payment gateways:\n- Cryptomus: Cryptocurrency payments\n- PayStation: Credit card and digital wallet payments\n\nConfiguring Payment Gateways (Admin only):\n1. Go to Admin Settings → Payment Gateways\n2. Enter API credentials for each gateway\n3. Set test mode for development\n4. Enable/disable specific gateways\n5. Save configuration\n\nInvoice Management:\nView all invoices in Billing → Invoices\nEach invoice shows:\n- Invoice number\n- Date\n- Amount\n- Status (paid/unpaid/cancelled)\n- Description\n- Download PDF option\n\nManual Balance Adjustment (Admin):\nAdmins can manually adjust user balances:\n1. Go to Billing → Manual Adjustment\n2. Select user\n3. Enter amount (positive to add, negative to deduct)\n4. Add description/reason\n5. Confirm adjustment\n\nAutomatic Billing:\n- System automatically deducts payment for renewals\n- Email notifications sent for all transactions\n- Failed payments trigger notifications\n- Transaction history available for auditing\n\nBalance Notifications:\n- Low balance warnings\n- Successful payment confirmations\n- Failed renewal notifications\n- Invoice generation alerts",
                'is_active' => true,
            ],
            [
                'title' => 'Managing User Profiles',
                'category' => 'profiles',
                'content' => "User profiles define bandwidth limits and other settings for vouchers and hotspot users.\n\nCreating a Profile:\n1. Navigate to Profile menu\n2. Click 'Add Profile'\n3. Enter profile details:\n   - Profile Name (e.g., '1 Mbps', '5 Mbps', '10 Mbps')\n   - Upload speed limit\n   - Download speed limit\n   - Session timeout\n   - Idle timeout\n   - MAC cookie timeout\n\n4. Save the profile\n\nProfile Settings Explained:\n- Upload Rate: Maximum upload speed (e.g., 1M, 5M, 10M)\n- Download Rate: Maximum download speed\n- Session Timeout: Maximum session duration\n- Idle Timeout: Disconnects if idle for specified time\n- MAC Cookie: Allows device to reconnect without re-authentication\n\nUsing Profiles with Vouchers:\nWhen generating vouchers:\n1. Select the router\n2. Choose a user profile\n3. Generate vouchers\n4. Vouchers inherit the profile settings\n\nProfile Synchronization:\n- MikroTik profiles can be pulled from router\n- Profile changes affect new sessions only\n- Existing sessions continue with old settings\n\nBest Practices:\n- Create profiles for different user tiers\n- Use clear, descriptive names\n- Test profiles before mass deployment\n- Document profile purposes\n- Keep profiles organized by speed/duration",
                'is_active' => true,
            ],
            [
                'title' => 'Zone Management for Multi-Location Setup',
                'category' => 'getting-started',
                'content' => "Zones help you organize routers by location or region in RADTik.\n\nWhat are Zones?\nZones are logical groupings for routers based on:\n- Physical location (City, District, Area)\n- Network segment\n- Client site\n- Building or floor\n\nCreating Zones:\n1. Navigate to Admin Settings → Zone Management\n2. Click 'Add Zone'\n3. Enter zone details:\n   - Zone Name (e.g., 'Downtown Office', 'North Branch')\n   - Description (optional)\n4. Save the zone\n\nAssigning Zones to Routers:\nWhen adding or editing a router:\n1. Select the appropriate zone from dropdown\n2. Save router configuration\n\nBenefits of Using Zones:\n- Better organization for multiple locations\n- Easy filtering and searching\n- Location-based reporting\n- Simplified management for large deployments\n- Quick identification of router locations\n\nViewing Routers by Zone:\n- Router list shows zone information\n- Filter routers by zone\n- Generate reports per zone\n- Monitor zone-specific performance\n\nMulti-Zone Best Practices:\n- Use consistent naming conventions\n- Create zones before adding routers\n- Map zones to physical locations\n- Document zone purposes\n- Review zone structure periodically",
                'is_active' => true,
            ],
        ];

        foreach ($articles as $article) {
            KnowledgebaseArticle::create($article);
        }
    }
}
