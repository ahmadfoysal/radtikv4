<?php

namespace App\MikroTik\Scripts;

use App\Models\Router;

class PullActiveUsersScript
{
    public static function name(): string
    {
        return 'RADTik-pull-active-users';
    }

    /**
     * Build RouterOS script that pulls valid users from API
     * and upserts them into /ip/hotspot/user.
     */
    public static function build(Router $router, string $baseUrl): string
    {
        $token = $router->app_key; // Assuming app_key is the token

        return <<<SCRIPT
# RADTik - Pull Active Hotspot Users
# Fetches valid users from API and syncs to MikroTik.
# Format: username;password;profile;comment

:local baseUrl "{$baseUrl}"
:local token   "{$token}"
:local dst     "radtik_active_users.txt"

# 1. Prepare Request
:local url (\$baseUrl . "?token=" . \$token . "&format=flat")
:log info "RADTik: Fetching active users list..."

/file remove [find name=\$dst]

# 2. Download File
:do {
    /tool fetch url=\$url mode=https http-method=get dst-path=\$dst check-certificate=no keep-result=yes
} on-error={
    :log error "RADTik: Download active users failed."
    :error "stop"
}

:delay 2s;

:local content ""
:if ([:len [/file find name=\$dst]] > 0) do={
    :set content [/file get \$dst contents]
} else={
    :log error "RADTik: File missing."
    :error "stop"
}

# 3. Process Data
:local lastEnd 0
:local fileLen [:len \$content]
:local processed 0

:log info "RADTik: Processing users..."

:while (\$lastEnd < \$fileLen) do={
    :local lineEnd [:find \$content "\\n" \$lastEnd]
    :if ([:typeof \$lineEnd] = "nil") do={ :set lineEnd \$fileLen }
    
    :local line [:pick \$content \$lastEnd \$lineEnd]
    :set lastEnd (\$lineEnd + 1)
    
    # Cleanup Carriage Return
    :if ([:len \$line] > 0 && [:pick \$line ([:len \$line]-1)] = "\\r") do={
        :set line [:pick \$line 0 ([:len \$line]-1)]
    }

    # Parse Line: user;pass;profile;comment
    :if ([:len \$line] > 0) do={
        :local s1 [:find \$line ";"]
        
        :if ([:typeof \$s1] != "nil") do={
            :local s2 [:find \$line ";" (\$s1 + 1)]
            :local s3 [:find \$line ";" (\$s2 + 1)]
            
            :if ([:typeof \$s2] != "nil" && [:typeof \$s3] != "nil") do={
                
                :local uName [:pick \$line 0 \$s1]
                :local uPass [:pick \$line (\$s1+1) \$s2]
                :local uProf [:pick \$line (\$s2+1) \$s3]
                :local uComm [:pick \$line (\$s3+1) [:len \$line]]

                # Validate
                :if (\$uName != "") do={
                    :local existingId [/ip hotspot user find name=\$uName]

                    :if ([:len \$existingId] > 0) do={
                        # Update existing
                        /ip hotspot user set \$existingId password=\$uPass profile=\$uProf comment=\$uComm
                        :log debug ("RADTik: Updated " . \$uName)
                    } else={
                        # Create new
                        /ip hotspot user add name=\$uName password=\$uPass profile=\$uProf comment=\$uComm
                        :log info ("RADTik: Added " . \$uName)
                    }
                    :set processed (\$processed + 1)
                }
            }
        }
    }
}

/file remove \$dst
:log info ("RADTik: Active user sync completed. Total processed: " . \$processed)
SCRIPT;
    }
}
