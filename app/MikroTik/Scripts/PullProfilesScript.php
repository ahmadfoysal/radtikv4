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
     * and injects the robust On-Login logic directly.
     */
    public static function build(Router $router, string $baseUrl): string
    {
        $token = $router->app_key;

        // The full On-Login script as a string for MikroTik
        // Escaping MikroTik variables (\$var) for PHP HEREDOC
        $onLoginScript = '{
            :local u "\$user";
            :local m \$"mac-address";

            :if ([:len \$u] = 0) do={:set u [/ip hotspot active get [find where address="\$address"] user]};

            /ip hotspot user {
                :local uid [find where name="\$u"];
                :if ([:len \$uid] > 0) do={
                    :local comment [get \$uid comment];
                    
                    :local isAct [:find "\$comment" "ACT="];

                    :if ([:len \$isAct] = 0) do={
                        :local date [/system clock get date];
                        :local time [/system clock get time];
                        :local newTS "ACT=\$date \$time";
                        
                        set \$uid comment=("\$newTS | \$comment");
                        :log info ("RADTik: Activation Set for " . \$u);
                    }
                    
                    :if ([:find "\$comment" "LOCK=1"] != nil) do={
                        :local smac [get \$uid mac-address];
                        :if (\$smac = "00:00:00:00:00:00" || [:len \$smac] = 0) do={
                            set \$uid mac-address=\$m;
                        };
                    };
                };
            };
        }';

        return <<<SCRIPT
# RADTik - Pull & Sync Hotspot User Profiles
# Method: Flat File Parsing with Inline On-Login Logic

:local baseUrl "{$baseUrl}"
:local token   "{$token}"
:local onLoginCmd "{$onLoginScript}"

:local url (\$baseUrl . "?token=" . \$token . "&format=flat")
:local dst "radtik_profiles.txt"

:log info ("RADTik: Fetching profiles from " . \$baseUrl)

/file remove [find name=\$dst]

/tool fetch url=\$url mode=https http-method=get dst-path=\$dst check-certificate=no keep-result=yes
:delay 2s;

:if ([:len [/file find name=\$dst]] = 0) do={
    :log error "RADTik: Fetch failed"
    :error "fetch failed"
}

:local content [/file get \$dst contents]
:local contentLen [:len \$content]

:local lineEnd 0
:local line ""
:local lastEnd 0
:local processed 0

:do {
    :set lineEnd [:find \$content "\\n" \$lastEnd]
    :if ([:typeof \$lineEnd] = "nil") do={ :set lineEnd \$contentLen }
    :set line [:pick \$content \$lastEnd \$lineEnd]
    :set lastEnd (\$lineEnd + 1)

    :if ([:pick \$line ([:len \$line]-1)] = "\\r") do={
        :set line [:pick \$line 0 ([:len \$line]-1)]
    }

    :if ([:len \$line] > 0) do={
        :local s1 [:find \$line ";"]
        :if ([:typeof \$s1] != "nil") do={
            :local s2 [:find \$line ";" (\$s1 + 1)]
            :if ([:typeof \$s2] != "nil") do={
                :local pName   [:pick \$line 0 \$s1]
                :local pShared [:tonum [:pick \$line (\$s1+1) \$s2]]
                :local s3 [:find \$line ";" (\$s2 + 1)]
                :local pRate ""
                :if ([:typeof \$s3] != "nil") do={
                    :set pRate [:pick \$line (\$s2+1) \$s3]
                } else={
                    :set pRate [:pick \$line (\$s2+1) [:len \$line]]
                }

                :if (\$pName != "" && \$pName != "default") do={
                    :local existingId [/ip hotspot user profile find name=\$pName]
                    :if ([:len \$existingId] > 0) do={
                        /ip hotspot user profile set \$existingId shared-users=\$pShared rate-limit=\$pRate on-login=\$onLoginCmd
                    } else={
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
