<?php

namespace App\MikroTik\Scripts;

use App\Models\Router;

class PushActiveUsersScript
{
    public static function name(): string
    {
        return 'RADTik-push-user-stats';
    }

    public static function build(Router $router, string $baseUrl): string
    {
        $token = $router->app_key; // Assuming 'app_key' is the correct property

        return <<<SCRIPT
# RADTik - Push User Stats Script
# Method: POST (Token in URL, Data in Body)

:local baseUrl "{$baseUrl}"
:local token   "{$token}"

# Construct URL with Token
:local url (\$baseUrl . "?token=" . \$token)

:local payload ""
:local count 0

:log info "RADTik: Collecting user stats..."

# Loop through all users
:foreach i in=[/ip hotspot user find] do={
    :local cmt [/ip hotspot user get \$i comment]

    # Sync only users that contain an activation timestamp in their comment
    :if ([:find \$cmt "Act:"] != nil) do={
        
        :local name [/ip hotspot user get \$i name]
        :local mac  [/ip hotspot user get \$i mac-address]
        :local bin  [/ip hotspot user get \$i bytes-in]
        :local bout [/ip hotspot user get \$i bytes-out]
        :local upt  [/ip hotspot user get \$i uptime]

        :if ([:len \$mac] = 0) do={ :set mac "" }

        # Format: user;mac;bin;bout;upt;comment
        :set payload (\$payload . \$name . ";" . \$mac . ";" . \$bin . ";" . \$bout . ";" . \$upt . ";" . \$cmt . "\\n")
        :set count (\$count + 1)
    }
}

:if (\$count > 0) do={
    :log info ("RADTik: Pushing stats for " . \$count . " users...")
    
    # Send via POST, Token is in URL, Data is in Body
    /tool fetch url=\$url http-method=post http-data=\$payload keep-result=no
} else={
    :log info "RADTik: No users to sync."
}
SCRIPT;
    }
}
