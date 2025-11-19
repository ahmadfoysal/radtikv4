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
     * from the API and upserts them on MikroTik.
     */
    public static function build(Router $router, string $baseUrl): string
    {
        $script = <<<'SCRIPT'
# RADTik - Pull Hotspot Profiles
# Fetch profiles from API and sync to MikroTik.

/* Configuration */
:local BASE_URL "__BASE_URL__"
:local TOKEN "__TOKEN__"

# Common on-login command for all RADTik profiles
:local onLoginCmd "/system script run RADTik-profile-on-login"

:local url ($BASE_URL . "?token=" . $TOKEN)
:log info ("RADTik: Pulling profiles from " . $url)

:local data [/tool fetch url=$url http-method=get output=user]

:if ([:len $data] = 0) do={
    :log warning "RADTik: API returned empty response for profiles"
    :error "No data"
}

:local pos [:find $data "\"profiles\""]

:if ($pos = nil) do={
    :log warning "RADTik: 'profiles' key not found in response"
    :error "Invalid JSON"
}

:set pos [:find $data "{" $pos]

:while ($pos != nil) do={

    # name
    :local nKey [:find $data "\"name\"" $pos]
    :if ($nKey = nil) do={ :break }
    :local nStart [:find $data "\"" ($nKey + 7)]
    :local nEnd [:find $data "\"" ($nStart + 1)]
    :local name [:pick $data ($nStart + 1) $nEnd]

    # shared_users
    :local suKey [:find $data "\"shared_users\"" $nEnd]
    :local suStart [:find $data "\"" ($suKey + 15)]
    :local suEnd [:find $data "\"" ($suStart + 1)]
    :local sharedUsers [:pick $data ($suStart + 1) $suEnd]

    # rate_limit
    :local rlKey [:find $data "\"rate_limit\"" $suEnd]
    :local rlStart [:find $data "\"" ($rlKey + 13)]
    :local rlEnd [:find $data "\"" ($rlStart + 1)]
    :local rateLimit [:pick $data ($rlStart + 1) $rlEnd]

    # session_timeout
    :local stKey [:find $data "\"session_timeout\"" $rlEnd]
    :local stStart [:find $data "\"" ($stKey + 18)]
    :local stEnd [:find $data "\"" ($stStart + 1)]
    :local sessionTimeout [:pick $data ($stStart + 1) $stEnd]

    # comment
    :local cKey [:find $data "\"comment\"" $stEnd]
    :local cStart [:find $data "\"" ($cKey + 11)]
    :local cEnd [:find $data "\"" ($cStart + 1)]
    :local comment [:pick $data ($cStart + 1) $cEnd]

    # mac_binding
    :local mbKey [:find $data "\"mac_binding\"" $cEnd]
    :local mbStart [:find $data "\"" ($mbKey + 14)]
    :local mbEnd [:find $data "\"" ($mbStart + 1)]
    :local macBinding [:pick $data ($mbStart + 1) $mbEnd]

    # Normalize mac_binding flag (MB=1 or MB=0 inside comment)
    :local enableMacBind false
    :if (($macBinding = "1") || ($macBinding = "true") || ($macBinding = "yes")) do={
        :set enableMacBind true
    }

    # Build final comment with MB flag
    :local baseComment $comment

    # Strip existing MB=... if present
    :local mbPosInComment [:find $baseComment "MB="]
    :if ($mbPosInComment != nil) do={
        :set baseComment [:pick $baseComment 0 $mbPosInComment]
    }

    # Trim possible trailing space / separator
    :if ([:len $baseComment] > 0) do={
        :local lastChar [:pick $baseComment ([:len $baseComment] - 1) [:len $baseComment]]
        :if ($lastChar = " " || $lastChar = "|" || $lastChar = ",") do={
            :set baseComment [:pick $baseComment 0 ([:len $baseComment] - 1)]
        }
    }

    :local mbValue "0"
    :if ($enableMacBind) do={ :set mbValue "1" }

    :local finalComment $baseComment
    :if ([:len $finalComment] > 0) do={
        :set finalComment ($finalComment . " | MB=" . $mbValue)
    } else={
        :set finalComment ("MB=" . $mbValue)
    }

    :local existing [/ip/hotspot/user/profile/find where name=$name]

    :if ([:len $existing] > 0) do={

        /ip/hotspot/user/profile/set $existing \
            name=$name \
            shared-users=$sharedUsers \
            rate-limit=$rateLimit \
            session-timeout=$sessionTimeout \
            comment=$finalComment \
            on-login=$onLoginCmd

        :log info ("RADTik: Updated profile: " . $name)

    } else={

        /ip/hotspot/user/profile/add \
            name=$name \
            shared-users=$sharedUsers \
            rate-limit=$rateLimit \
            session-timeout=$sessionTimeout \
            comment=$finalComment \
            on-login=$onLoginCmd

        :log info ("RADTik: Added profile: " . $name)
    }

    :set pos [:find $data "{" $mbEnd]
}

:log info "RADTik: Profile sync completed."
SCRIPT;

        return str_replace(
            ['__BASE_URL__', '__TOKEN__'],
            [$baseUrl, $router->app_key],
            $script
        );
    }
}
