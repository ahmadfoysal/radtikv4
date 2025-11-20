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

    # comment (already contains MB= flag)
    :local cKey [:find $data "\"comment\"" $rlEnd]
    :local cStart [:find $data "\"" ($cKey + 11)]
    :local cEnd [:find $data "\"" ($cStart + 1)]
    :local comment [:pick $data ($cStart + 1) $cEnd]

    :local existing [/ip/hotspot/user/profile/find where name=$name]

    :if ([:len $existing] > 0) do={

        /ip/hotspot/user/profile/set $existing \
            name=$name \
            shared-users=$sharedUsers \
            rate-limit=$rateLimit \
            comment=$comment \
            on-login=$onLoginCmd

        :log info ("RADTik: Updated profile: " . $name)

    } else={

        /ip/hotspot/user/profile/add \
            name=$name \
            shared-users=$sharedUsers \
            rate-limit=$rateLimit \
            comment=$comment \
            on-login=$onLoginCmd

        :log info ("RADTik: Added profile: " . $name)
    }

    :set pos [:find $data "{" $cEnd]
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
