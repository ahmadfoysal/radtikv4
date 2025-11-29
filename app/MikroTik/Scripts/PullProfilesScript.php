<?php

namespace App\MikroTik\Scripts;

use App\Models\Router;

class PullProfilesScript
{
    public static function name(): string
    {
        return 'RADTik-pull-profiles';
    }

    /**
     * Build RouterOS script that pulls hotspot user profiles
     * using the robust flat-file format.
     */
    public static function build(Router $router, string $baseUrl): string
    {
        $token = $router->app_key;

        // We use HEREDOC. Note: MikroTik variables ($var) must be escaped as \$var.
        // PHP variables ({$token}) are interpolated.
        return <<<SCRIPT
# RADTik - Pull & Sync Hotspot User Profiles
# Method: Flat File Parsing (Robust)
# Syncs profiles and sets the common on-login script "RADTik_Login_Logic"

:local baseUrl "{$baseUrl}"
:local token   "{$token}"
:local onLoginCmd "/system script run RADTik_Login_Logic"

# We request 'format=flat' for reliable parsing (Name;Shared;Rate)
:local url (\$baseUrl . "?token=" . \$token . "&format=flat")
:local dst "radtik_profiles.txt"

:log info ("RADTik: Fetching profiles from " . \$baseUrl)

# Remove old file
/file remove [find name=\$dst]

# Fetch
/tool fetch url=\$url mode=https http-method=get dst-path=\$dst check-certificate=no keep-result=yes
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

:log info ("RADTik: Processing Profiles...")

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
        # Format Expectation: Name;Shared;Rate[;Extra]
        
        :local s1 [:find \$line ";"]
        
        :if ([:typeof \$s1] != "nil") do={
            :local s2 [:find \$line ";" (\$s1 + 1)]
            
            # We need at least Name and Shared-Users delimiters
            :if ([:typeof \$s2] != "nil") do={
                
                :local pName   [:pick \$line 0 \$s1]
                :local pShared [:tonum [:pick \$line (\$s1+1) \$s2]]
                
                # Check for 3rd delimiter to determine where Rate ends
                :local s3 [:find \$line ";" (\$s2 + 1)]
                :local pRate ""
                
                :if ([:typeof \$s3] != "nil") do={
                    :set pRate [:pick \$line (\$s2+1) \$s3]
                } else={
                    # If no extra data, Rate is until the end of the line
                    :set pRate [:pick \$line (\$s2+1) [:len \$line]]
                }

                # --- Update / Create Profile ---
                :if (\$pName != "" && \$pName != "default") do={
                    :local existingId [/ip hotspot user profile find name=\$pName]

                    :if ([:len \$existingId] > 0) do={
                        :log info ("RADTik: Updating " . \$pName)
                        /ip hotspot user profile set \$existingId shared-users=\$pShared rate-limit=\$pRate on-login=\$onLoginCmd
                    } else={
                        :log info ("RADTik: Creating " . \$pName)
                        /ip hotspot user profile add name=\$pName shared-users=\$pShared rate-limit=\$pRate on-login=\$onLoginCmd
                    }
                    :set processed (\$processed + 1)
                }
            }
        }
    }
} while (\$lastEnd < \$contentLen)

:log info ("RADTik: DONE. Total processed: " . \$processed)
/file remove \$dst
SCRIPT;
    }
}
