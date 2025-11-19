<?php

namespace App\MikroTik\Scripts;

use App\Models\Router;

class RemoveOrphanProfilesScript
{
    public static function name(): string
    {
        return 'RADTik-remove-orphan-profiles';
    }

    /**
     * Build RouterOS script that removes local profiles
     * whose comments start with "RADTik" and do not exist in DB.
     */
    public static function build(Router $router, string $baseUrl): string
    {
        $script = <<<'SCRIPT'
# RADTik - Remove Orphan Hotspot Profiles
# Profiles with comments starting "RADTik" will be checked
# against the central database. Missing ones are removed.

/* Configuration */
:local BASE_URL "__BASE_URL__"
:local TOKEN "__TOKEN__"

:log info "RADTik: Starting orphan profile cleanup..."

:foreach p in=[/ip/hotspot/user/profile/find] do={

    :local comment [/ip/hotspot/user/profile/get $p comment]
    :if ([:len $comment] = 0) do={ :continue }

    # Only process RADTik profiles
    :if ([:pick $comment 0 6] != "RADTik") do={ :continue }

    :local name [/ip/hotspot/user/profile/get $p name]

    :local url ($BASE_URL . "?token=" . $TOKEN . "&name=" . $name)

    :log info ("RADTik: Checking profile in DB: " . $name)

    :local resp [/tool fetch url=$url http-method=get output=user]

    :local existsPos [:find $resp "\"exists\":true"]

    :if ($existsPos = nil) do={
        :log info ("RADTik: Removing orphan profile: " . $name)
        /ip/hotspot/user/profile/remove $p
    } else={
        :log info ("RADTik: Profile exists in DB, keeping: " . $name)
    }
}

:log info "RADTik: Orphan profile cleanup finished."
SCRIPT;

        return str_replace(
            ['__BASE_URL__', '__TOKEN__'],
            [$baseUrl, $router->app_key],
            $script
        );
    }
}
