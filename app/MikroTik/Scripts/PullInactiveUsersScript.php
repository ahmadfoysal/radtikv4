<?php

namespace App\MikroTik\Scripts;

use App\Models\Router;

class PullInactiveUsersScript
{
    public static function name(): string
    {
        return 'RADTik-pull-inactive-users';
    }

    /**
     * Build RouterOS script that pulls inactive users
     * using the robust flat-file format.
     */
    public static function build(Router $router, string $baseUrl): string
    {
        $token = $router->app_key;

        return <<<SCRIPT
# RADTik - Pull Inactive Users
# Method: Flat File Parsing (Robust)
# Format: Username;Password;Profile;Comment

:local baseUrl "{$baseUrl}"
:local token   "{$token}"

# Prepare Auth Header
:local authHeader ("Authorization: Bearer " . \$token)

# Request 'format=flat' for reliable parsing
:local url (\$baseUrl . "?token=" . \$token . "&format=flat")
:local dst "radtik_users.txt"

:log info ("RADTik: Fetching users from " . \$baseUrl)

# Remove old file
/file remove [find name=\$dst]

# Fetch
/tool fetch url=\$url http-header-field=\$authHeader mode=https http-method=get dst-path=\$dst check-certificate=no keep-result=yes
:delay 2s;

:if ([:len [/file find name=\$dst]] = 0) do={
    :log error "RADTik: Fetch failed"
    :error "fetch failed"
}

:local content [/file get \$dst contents]
:local contentLen [:len \$content]

:if (\$contentLen = 0) do={
    :log warning "RADTik: Empty response"
    /file remove \$dst
    :error "empty"
}

:local lineEnd 0
:local line ""
:local lastEnd 0
:local processed 0

:log info ("RADTik: Processing Users...")

# ---------- MAIN LOOP ----------
:do {
    :set lineEnd [:find \$content "\\n" \$lastEnd]
    :if ([:typeof \$lineEnd] = "nil") do={ :set lineEnd \$contentLen }
    :set line [:pick \$content \$lastEnd \$lineEnd]
    :set lastEnd (\$lineEnd + 1)

    # Cleanup Carriage Return
    :if ([:pick \$line ([:len \$line]-1)] = "\\r") do={
        :set line [:pick \$line 0 ([:len \$line]-1)]
    }

    :if ([:len \$line] > 0) do={
        # Format Expectation: User;Pass;Profile;Comment
        
        :local s1 [:find \$line ";"]
        
        :if ([:typeof \$s1] != "nil") do={
            :local s2 [:find \$line ";" (\$s1 + 1)]
            :local s3 [:find \$line ";" (\$s2 + 1)]
            
            # We need at least 3 delimiters for 4 fields
            :if ([:typeof \$s2] != "nil" && [:typeof \$s3] != "nil") do={
                
                :local uName [:pick \$line 0 \$s1]
                :local uPass [:pick \$line (\$s1+1) \$s2]
                :local uProf [:pick \$line (\$s2+1) \$s3]
                :local uCom  [:pick \$line (\$s3+1) [:len \$line]]

                # --- Create User if not exists ---
                :if (\$uName != "") do={
                    :local existingId [/ip hotspot user find name=\$uName]

                    :if ([:len \$existingId] = 0) do={
                        :log info ("RADTik: Adding User " . \$uName)
                        
                        # Add user with Profile and Comment (containing LOCK flag)
                        /ip hotspot user add name=\$uName password=\$uPass profile=\$uProf comment=\$uCom
                        
                        :set processed (\$processed + 1)
                    } else={
                         # Optional: Update existing user comment/profile if needed
                         # /ip hotspot user set \$existingId profile=\$uProf comment=\$uCom
                    }
                }
            }
        }
    }
} while (\$lastEnd < \$contentLen)

:log info ("RADTik: DONE. Total users processed: " . \$processed)
/file remove \$dst
SCRIPT;
    }
}
