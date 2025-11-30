<?php

namespace App\MikroTik\Scripts;

use App\Models\Router;

class PushActiveUsersScript
{
    public static function name(): string
    {
        return 'RADTik-push-user-stats';
    }

    /**
     * Build RouterOS script for pushing user statistics to API.
     * Syncs: Mac, Bytes In/Out, Uptime, and Activation Comment.
     */
    public static function build(Router $router, string $baseUrl): string
    {
        $token = $router->token;

        return <<<SCRIPT
# RADTik - Push User Stats Script
# Sends stats for ALL activated users (offline or online) to API.
# Method: POST (to handle large data)
# Format: username;mac;bytes-in;bytes-out;uptime;comment

:local baseUrl "{$baseUrl}"
:local token   "{$token}"

# Auth Header
:local authHeader ("Authorization: Bearer " . \$token)

# URL (We use POST, so data goes in body)
:local url (\$baseUrl . "?token=" . \$token)

:local payload ""
:local count 0

:log info "RADTik: Collecting user stats..."

# Loop through ALL hotspot users
:foreach i in=[/ip hotspot user find] do={
    
    :local cmt [/ip hotspot user get \$i comment]

    # Only sync if user is Activated (contains "Act:") OR has usage
    :if ([:find \$cmt "Act:"] != nil || [/ip hotspot user get \$i bytes-out] > 0) do={
        
        :local name [/ip hotspot user get \$i name]
        :local mac  [/ip hotspot user get \$i mac-address]
        :local bin  [/ip hotspot user get \$i bytes-in]
        :local bout [/ip hotspot user get \$i bytes-out]
        :local upt  [/ip hotspot user get \$i uptime]

        # Handle empty MAC (if any)
        :if ([:len \$mac] = 0) do={ :set mac "" }

        # Append to payload (newline separated)
        # Format: user;mac;bin;bout;upt;comment
        :set payload (\$payload . \$name . ";" . \$mac . ";" . \$bin . ";" . \$bout . ";" . \$upt . ";" . \$cmt . "\\n")
        
        :set count (\$count + 1)
    }
}

:if (\$count > 0) do={
    :log info ("RADTik: Pushing stats for " . \$count . " users...")
    
    # Send via POST request
    /tool fetch url=\$url http-header-field=\$authHeader http-method=post http-data=\$payload keep-result=no
} else={
    :log info "RADTik: No activated users to sync."
}
SCRIPT;
    }
}
