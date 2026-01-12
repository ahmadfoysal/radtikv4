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
                'title' => 'How to Connect Your MikroTik Router',
                'category' => 'Getting Started',
                'content' => <<<'MD'
# How to Connect Your MikroTik Router

## Prerequisites
- MikroTik router with RouterOS installed
- Network access to the router
- Admin credentials for the router

## Steps to Connect

1. **Login to RADTik**
   - Navigate to the Routers section
   - Click "Add Router" button

2. **Enter Router Details**
   - Router Name: Give it a descriptive name
   - Host/IP Address: Enter your router's IP address
   - Username: Usually "admin"
   - Password: Your router's admin password
   - API Port: Default is 8728

3. **Test Connection**
   - Click the "Test Connection" button
   - If successful, save the router

4. **Configure Hotspot**
   - Ensure your router has Hotspot configured
   - Set up user profiles matching your voucher templates

## Troubleshooting
- Ensure API is enabled on your MikroTik router
- Check firewall rules allow API access
- Verify credentials are correct
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Understanding WiFi Vouchers',
                'category' => 'Vouchers',
                'content' => <<<'MD'
# Understanding WiFi Vouchers

## What are WiFi Vouchers?
WiFi vouchers are access codes that provide temporary internet access through your hotspot network.

## Voucher Components
- **Username**: Unique identifier for the voucher
- **Password**: Access code (can be same as username)
- **Profile**: Defines speed, duration, and limits
- **Validity**: Time period the voucher is valid for

## Voucher Lifecycle

1. **Generated**: Voucher is created but not yet used
2. **Active**: User has logged in and is using the voucher
3. **Expired**: Voucher time limit has been reached
4. **Used**: Voucher has been fully consumed

## Best Practices
- Print vouchers on thermal paper for durability
- Include WiFi name and connection instructions
- Set appropriate validity periods
- Monitor active sessions regularly
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Managing Hotspot Users',
                'category' => 'Users',
                'content' => <<<'MD'
# Managing Hotspot Users

## User Types

### Single Users
- Created individually through the interface
- Custom usernames and passwords
- Ideal for permanent access accounts

### Voucher Users
- Generated in bulk
- Temporary access
- Pre-defined profiles

## User Management Tasks

### Creating Single Users
1. Go to Hotspot Users section
2. Click "Create Single User"
3. Enter username, password, and select profile
4. Assign to specific router

### Viewing Active Sessions
- See who's currently connected
- Monitor bandwidth usage
- Disconnect users if needed

### Managing User Profiles
- Each profile defines speed limits
- Set time limits and data quotas
- Configure shared users settings

## Tips
- Use descriptive usernames for single users
- Regularly clean up expired voucher users
- Monitor active sessions for unusual activity
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Payment Gateway Setup',
                'category' => 'Billing',
                'content' => <<<'MD'
# Payment Gateway Setup

## Available Payment Gateways

### Cryptomus (Cryptocurrency)
- Supports multiple cryptocurrencies
- USDT, BTC, ETH, and more
- International payments

### PayStation (Bangladesh)
- Local payment gateway
- Bkash, Nagad, cards supported
- BDT currency

## Configuration Steps

1. **Navigate to Payment Settings**
   - Access admin panel
   - Go to Payment Gateway section

2. **Configure Credentials**
   - Enter Merchant ID
   - Add API Key/Password
   - Set base URL if required

3. **Test Mode**
   - Enable test mode for testing
   - Use sandbox credentials
   - Test payment flow

4. **Activate Gateway**
   - Once tested, toggle active status
   - Gateway will appear at checkout

## Security
- Keep credentials secure
- Use environment variables in production
- Regularly check transaction logs
- Enable webhook verification
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Troubleshooting Router Connection Issues',
                'category' => 'Troubleshooting',
                'content' => <<<'MD'
# Troubleshooting Router Connection Issues

## Common Issues and Solutions

### Cannot Connect to Router

**Check API is Enabled**
```
/ip service print
/ip service enable api
```

**Verify API Port**
- Default port: 8728
- Check if port is open
- Verify firewall rules

### Authentication Failed

**Verify Credentials**
- Double-check username and password
- Ensure account has API access
- Try connecting via WinBox first

**User Permissions**
- User needs API permissions
- Check user group settings

### Connection Timeout

**Network Issues**
- Ping the router IP
- Check network connectivity
- Verify correct IP address

**Firewall Rules**
```
/ip firewall filter
add chain=input protocol=tcp dst-port=8728 action=accept
```

### SSL Certificate Errors
- Update RouterOS to latest version
- Check SSL certificate validity
- Disable SSL verification (not recommended for production)

## Need More Help?
Contact support with:
- Router model and RouterOS version
- Error messages
- Screenshot of connection attempt
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
