<?php

namespace App\MikroTik\Scripts;

use App\Models\Router;

class RemoveOrphanUsersScript
{
    public static function name(): string
    {
        return 'RADTik-remove-orphan-users';
    }

    /**
     * Build RouterOS script that:
     *  - pulls the current RADTik usernames from Laravel (flat file response)
     *  - scans local hotspot users with comments starting with "RADTik"
     *  - deletes any local user that is not present in the pulled list
     */
    public static function build(Router $router, string $baseUrl): string
    {
        $token = $router->app_key;

        return <<<SCRIPT
# RADTik - Remove Orphan Hotspot Users
# Workflow:
#   1. Pull canonical user list from Laravel (flat format, one username per line)
#   2. Build lookup list in RouterOS
#   3. Remove local hotspot users whose comment starts with "RADTik" but are missing from the list

:local baseUrl "{$baseUrl}"
:local token   "{$token}"
:local dst     "radtik_valid_users.txt"

:local authHeader ("Authorization: Bearer " . \$token)
:local url (\$baseUrl . "?token=" . \$token . "&format=flat")

:log info "RADTik: Downloading valid user list for orphan cleanup..."

# Remove any previous file
/file remove [find name=\$dst]

# Fetch the list (HTTPS w/ Bearer header + token query)
/tool fetch url=\$url http-header-field=\$authHeader mode=https http-method=get dst-path=\$dst check-certificate=no keep-result=yes
:delay 2s;

:if ([:len [/file find name=\$dst]] = 0) do={
    :log error "RADTik: Failed to download user list."
    :error "fetch failed"
}

:local content [/file get \$dst contents]
:local contentLen [:len \$content]

:if (\$contentLen = 0) do={
    :log warning "RADTik: Empty user list received — aborting orphan cleanup."
    /file remove \$dst
    :error "empty list"
}

# Build lookup string like ;user1;user2; to safely check membership
:local allowed ";"
:local line ""
:local lastEnd 0

:do {
    :local lineEnd [:find \$content "\\n" \$lastEnd]
    :if ([:typeof \$lineEnd] = "nil") do={ :set lineEnd \$contentLen }
    :set line [:pick \$content \$lastEnd \$lineEnd]
    :set lastEnd (\$lineEnd + 1)

    # Strip CR for Windows line endings
    :if ([:len \$line] > 0 && [:pick \$line ([:len \$line]-1)] = "\\r") do={
        :set line [:pick \$line 0 ([:len \$line]-1)]
    }

    :if ([:len \$line] > 0) do={
        :set allowed (\$allowed . \$line . ";")
    }
} while (\$lastEnd < \$contentLen)

/file remove \$dst

:log info "RADTik: Valid usernames synced. Starting local cleanup..."

:local removed 0
:foreach u in=[/ip/hotspot/user/find] do={
    :local comment [/ip/hotspot/user/get \$u comment]
    :if ([:len \$comment] = 0) do={ :continue }

    # Process only RADTik-managed users
    :if ([:pick \$comment 0 6] != "RADTik") do={ :continue }

    :local username [/ip/hotspot/user/get \$u name]

    :if ([:find \$allowed (";" . \$username . ";")] = nil) do={
        :log info ("RADTik: Removing orphan hotspot user: " . \$username)
        /ip/hotspot/user/remove \$u
        :set removed (\$removed + 1)
    }
}

:log info ("RADTik: Orphan cleanup finished. Users removed: " . \$removed)
SCRIPT;
    }
}
