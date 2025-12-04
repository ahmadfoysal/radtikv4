<?php

namespace App\MikroTik\Scripts;

use App\Models\Router;

class CleanOrphanUsersScript
{
    public static function name(): string
    {
        return 'RADTik-clean-orphans';
    }

    /**
     * Build RouterOS script for smart orphan cleanup.
     * Logic: Sends local RADTik users to server -> Server returns list to DELETE.
     */
    public static function build(Router $router, string $baseUrl): string
    {
        // Use app_key as the token
        $token = $router->app_key;

        // We use HEREDOC syntax. MikroTik variables ($var) must be escaped as \$var.
        // PHP variables ({$token}) are interpolated.
        return <<<SCRIPT
# RADTik - Smart Orphan Cleanup
# Sends local user list to Server -> Server returns list of users to delete.

:local baseUrl "{$baseUrl}"
:local token   "{$token}"
:local dst     "radtik_delete_list.txt"

:log warning "RADTik: Starting Smart Cleanup..."

# 1. Collect Local RADTik Users
# We build a comma-separated string of usernames to send to server
:local userList ""
:local count 0

# Loop through users where comment starts with "RADTik"
:foreach u in=[/ip hotspot user find where comment~"^RADTik"] do={
    :local uname [/ip hotspot user get \$u name]
    
    # Append to list (u1,u2,u3...)
    :if (\$count = 0) do={
        :set userList \$uname
    } else={
        :set userList (\$userList . "," . \$uname)
    }
    :set count (\$count + 1)
}

:if (\$count = 0) do={
    :log info "RADTik: No local RADTik users found to check."
    :error "stop"
}

:log info ("RADTik: Validating " . \$count . " users with server...")

# 2. Send to Server & Receive Delete List
# We use POST method to handle large lists
:local url (\$baseUrl . "?token=" . \$token)

# Remove old response file
/file remove [find name=\$dst]

:do {
    # Send userList in http-data
    /tool fetch url=\$url http-method=post http-data=\$userList dst-path=\$dst mode=https check-certificate=no keep-result=yes
} on-error={
    :log error "RADTik: Server sync failed. Check internet or API url."
    :error "stop"
}

:delay 2s;

# 3. Process the Delete List returned by Server
:local content ""
:if ([:len [/file find name=\$dst]] > 0) do={
    :set content [/file get \$dst contents]
}

# If content is empty, it means server found no orphans
:if ([:len \$content] = 0) do={
    :log info "RADTik: Sync Complete. All users are valid."
    /file remove \$dst
    :error "stop"
}

:log warning "RADTik: Server identified orphans. Deleting..."

# Loop through the returned list (newline separated)
:local lastEnd 0
:local fileLen [:len \$content]
:local deleted 0

:while (\$lastEnd < \$fileLen) do={
    :local lineEnd [:find \$content "\\n" \$lastEnd]
    :if ([:typeof \$lineEnd] = "nil") do={ :set lineEnd \$fileLen }
    
    :local uname [:pick \$content \$lastEnd \$lineEnd]
    :set lastEnd (\$lineEnd + 1)
    
    # Cleanup Carriage Return (r) commonly found in HTTP responses
    :if ([:len \$uname] > 0 && [:pick \$uname ([:len \$uname]-1)] = "\\r") do={
        :set uname [:pick \$uname 0 ([:len \$uname]-1)]
    }

    # Delete the Orphan User
    :if ([:len \$uname] > 0) do={
        :local uid [/ip hotspot user find name=\$uname]
        :if ([:len \$uid] > 0) do={
            :log warning ("RADTik: Deleting Orphan -> " . \$uname)
            /ip hotspot user remove \$uid
            :set deleted (\$deleted + 1)
        }
    }
}

/file remove \$dst
:log warning ("RADTik: Cleanup Finished. Total Deleted: " . \$deleted)
SCRIPT;
    }
}
