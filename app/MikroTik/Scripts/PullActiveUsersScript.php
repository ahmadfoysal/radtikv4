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
     * Build RouterOS script that pulls active users from API
     * and upserts them into /ip/hotspot/user.
     */
    public static function build(Router $router, string $baseUrl): string
    {
        $script = <<<'SCRIPT'
# RADTik - Pull Active Hotspot Users
# Fetch active users from API and sync to MikroTik.

/* Configuration */
:local BASE_URL "__BASE_URL__"
:local TOKEN "__TOKEN__"

:local url ($BASE_URL . "?token=" . $TOKEN)
:log info ("RADTik: Pulling active users from " . $url)

:local data [/tool fetch url=$url http-method=get output=user]

:if ([:len $data] = 0) do={
    :log warning "RADTik: API returned empty response for active users"
    :error "No data"
}

:local pos [:find $data "\"users\""]

:if ($pos = nil) do={
    :log warning "RADTik: 'users' key not found in response"
    :error "Invalid JSON"
}

:set pos [:find $data "{" $pos]

:local processed 0

:while ($pos != nil) do={

    # username
    :local uKey [:find $data "\"username\"" $pos]
    :if ($uKey = nil) do={ :break }
    :local uStart [:find $data "\"" ($uKey + 11)]
    :local uEnd [:find $data "\"" ($uStart + 1)]
    :local username [:pick $data ($uStart + 1) $uEnd]

    # password
    :local pKey [:find $data "\"password\"" $uEnd]
    :local pStart [:find $data "\"" ($pKey + 11)]
    :local pEnd [:find $data "\"" ($pStart + 1)]
    :local password [:pick $data ($pStart + 1) $pEnd]

    # profile
    :local prKey [:find $data "\"profile\"" $pEnd]
    :local prStart [:find $data "\"" ($prKey + 10)]
    :local prEnd [:find $data "\"" ($prStart + 1)]
    :local profile [:pick $data ($prStart + 1) $prEnd]

    # comment
    :local cKey [:find $data "\"comment\"" $prEnd]
    :local cStart [:find $data "\"" ($cKey + 11)]
    :local cEnd [:find $data "\"" ($cStart + 1)]
    :local comment [:pick $data ($cStart + 1) $cEnd]

    :local existing [/ip/hotspot/user/find where name=$username]

    :if ([:len $existing] > 0) do={
        /ip/hotspot/user/set $existing \
            name=$username \
            password=$password \
            profile=$profile \
            comment=$comment

        :log info ("RADTik: Updated user from active list: " . $username)
    } else={
        /ip/hotspot/user/add \
            name=$username \
            password=$password \
            profile=$profile \
            comment=$comment

        :log info ("RADTik: Added user from active list: " . $username)
    }

    :set processed ($processed + 1)

    :set pos [:find $data "{" $cEnd]
}

:log info ("RADTik: Active user sync completed. Total=" . $processed)
SCRIPT;

        return str_replace(
            ['__BASE_URL__', '__TOKEN__'],
            [$baseUrl, $router->app_key],
            $script
        );
    }
}
