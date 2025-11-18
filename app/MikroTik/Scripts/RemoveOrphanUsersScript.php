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
     * Build RouterOS script that removes local hotspot users
     * whose comment starts with "RADTik" and do not exist in DB.
     */
    public static function build(Router $router, string $baseUrl): string
    {
        $script = <<<'SCRIPT'
# RADTik - Remove Orphan Hotspot Users
# Deletes local hotspot users whose comments start with "RADTik"
# when they are not found in the central database.

:local BASE_URL "__BASE_URL__"
:local TOKEN "__TOKEN__"

:log info "RADTik: Starting orphan user cleanup..."

:foreach u in=[/ip/hotspot/user/find] do={

    :local comment [/ip/hotspot/user/get $u comment]
    :if ([:len $comment] = 0) do={
        :continue
    }

    # Only process users whose comment starts with "RADTik"
    :if ([:pick $comment 0 6] != "RADTik") do={
        :continue
    }

    :local username [/ip/hotspot/user/get $u name]

    :local url ($BASE_URL . "?token=" . $TOKEN . "&username=" . $username)

    :log info ("RADTik: Checking user in DB: " . $username)

    :local resp [/tool fetch url=$url http-method=get output=user]

    # Expecting JSON like: {"exists":true} or {"exists":false}
    :local existsPos [:find $resp "\"exists\":true"]

    # If "exists":true not found in response, treat as not existing in DB
    :if ($existsPos = nil) do={
        :log info ("RADTik: Deleting orphan hotspot user: " . $username)
        /ip/hotspot/user/remove $u
    } else={
        :log info ("RADTik: User exists in DB, keeping: " . $username)
    }
}

:log info "RADTik: Orphan user cleanup finished."
SCRIPT;

        return str_replace(
            ['__BASE_URL__', '__TOKEN__'],
            [$baseUrl, $router->app_key],
            $script
        );
    }
}
