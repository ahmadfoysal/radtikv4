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
     * Build RouterOS script with placeholders replaced.
     */
    public static function build(Router $router, string $baseUrl): string
    {
        $script = <<<'SCRIPT'
# RADTik - Pull Inactive Users Script
# Automatically pulls inactive vouchers from API
# and inserts them as hotspot users.

/system script environment set RADTIK_BASE_URL="__BASE_URL__"
:local BASE_URL $RADTIK_BASE_URL
:local TOKEN "__TOKEN__"

:local url ($BASE_URL . "?token=" . $TOKEN)
:log info ("RADTik: Pulling inactive users from " . $url)

:local data [/tool fetch url=$url http-method=get output=user]
:if ([:len $data] = 0) do={
    :log warning "RADTik: API returned empty response"
    :error "No data"
}

:local pos [:find $data "\"vouchers\""]
:if ($pos = nil) do={
    :log warning "RADTik: 'vouchers' key not found"
    :error "Invalid JSON"
}

# Move pointer to the first "{"
:set pos [:find $data "{" $pos]

:while ($pos != nil) do={

    # username
    :local uKey [:find $data "\"username\"" $pos]
    :if ($uKey = nil) do={ :break }
    :local uStart [:find $data "\"" ($uKey + 12)]
    :local uEnd [:find $data "\"" ($uStart + 1)]
    :local username [:pick $data ($uStart + 1) $uEnd]

    # password
    :local pKey [:find $data "\"password\"" $uEnd]
    :local pStart [:find $data "\"" ($pKey + 12)]
    :local pEnd [:find $data "\"" ($pStart + 1)]
    :local password [:pick $data ($pStart + 1) $pEnd]

    # profile
    :local prKey [:find $data "\"profile\"" $pEnd]
    :local prStart [:find $data "\"" ($prKey + 10)]
    :local prEnd [:find $data "\"" ($prStart + 1)]
    :local profile [:pick $data ($prStart + 1) $prEnd]

    # comment
    :local cKey [:find $data "\"comments\"" $prEnd]
    :local cStart [:find $data "\"" ($cKey + 11)]
    :local cEnd [:find $data "\"" ($cStart + 1)]
    :local comment [:pick $data ($cStart + 1) $cEnd]

    # If user already exists → skip
    :local exists [/ip/hotspot/user/find where name=$username]
    :if ([:len $exists] > 0) do={
        :log info ("RADTik: User already exists: " . $username)
    } else={
        /ip/hotspot/user/add name=$username password=$password profile=$profile comment=$comment
        :log info ("RADTik: Added hotspot user: " . $username)
    }

    :set pos [:find $data "{" $cEnd]
}
SCRIPT;

        return str_replace(
            ['__BASE_URL__', '__TOKEN__'],
            [$baseUrl, $router->app_key],
            $script
        );
    }
}
