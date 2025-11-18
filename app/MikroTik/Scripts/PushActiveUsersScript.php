<?php

namespace App\MikroTik\Scripts;

use App\Models\Router;

class PushActiveUsersScript
{
    public static function name(): string
    {
        return 'RADTik-push-active-users';
    }

    /**
     * Build RouterOS script for pushing active user sessions to API.
     */
    public static function build(Router $router, string $baseUrl): string
    {
        $script = <<<'SCRIPT'
# RADTik - Push Active Users Script
# Sends hotspot active user sessions to API endpoint.

:local BASE_URL "__BASE_URL__"
:local TOKEN "__TOKEN__"

:local url ($BASE_URL . "?token=" . $TOKEN)
:local result ""

:foreach u in=[/ip/hotspot/active/find] do={

    :local user [/ip/hotspot/active/get $u user]
    :local mac  [/ip/hotspot/active/get $u mac-address]
    :local ip   [/ip/hotspot/active/get $u address]
    :local uptime [/ip/hotspot/active/get $u uptime]

    :local line ("user=" . $user . ";mac=" . $mac . ";ip=" . $ip . ";uptime=" . $uptime . "|")

    :set result ($result . $line)
}

# Call Laravel API
/tool fetch url=($url . "&data=" . $result) http-method=get keep-result=no

:log info "RADTik: Active hotspot users pushed to API"
SCRIPT;

        return str_replace(
            ['__BASE_URL__', '__TOKEN__'],
            [$baseUrl, $router->app_key],
            $script
        );
    }
}
