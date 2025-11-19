<?php

namespace App\MikroTik\Scripts;

use App\Models\Router;

class ProfileOnLoginScript
{
    public static function name(): string
    {
        return 'RADTik-profile-on-login';
    }

    public static function build(Router $router): string
    {
        $script = <<<'SCRIPT'
# RADTik - Profile On-Login Script
# - Adds activation timestamp to user comment (once, for all users)
# - Conditionally adds MAC binding based on profile comment flag "MB=1"

:local u $user
:local m $mac

:if ([:len $u] = 0) do={
    :log warning "RADTik: on-login called without user name"
    :return
}

:local uid [/ip/hotspot/user/find where name=$u]

:if ([:len $uid] = 0) do={
    :log warning ("RADTik: on-login user not found: " . $u)
    :return
}

# -------- Activation timestamp (for all RADTik users) --------
:local oldComment [/ip/hotspot/user/get $uid comment]

:local actPos [:find $oldComment "ACT="]

:if ($actPos = nil) do={
    :local date [/system clock get date]
    :local time [/system clock get time]
    :local ts ($date . " " . $time)

    :local newComment ("ACT=" . $ts)
    :if ([:len $oldComment] > 0) do={
        :set newComment ($newComment . " | " . $oldComment)
    }

    /ip/hotspot/user/set $uid comment=$newComment
    :log info ("RADTik: activation set for user " . $u . " at " . $ts)
}

# -------- Decide if MAC binding is enabled for this profile --------
:local profileName [/ip/hotspot/user/get $uid profile]
:local pid [/ip/hotspot/user/profile/find where name=$profileName]
:local pComment ""

:if ([:len $pid] > 0) do={
    :set pComment [/ip/hotspot/user/profile/get $pid comment]
}

:local mbPos [:find $pComment "MB=1"]
:local enableMacBind false

:if ($mbPos != nil) do={
    :set enableMacBind true
}

# -------- MAC binding (conditional) --------
:if ($enableMacBind = false) do={
    :log info ("RADTik: MAC binding disabled for profile " . $profileName)
    :return
}

:if ([:len $m] = 0) do={
    :log warning ("RADTik: no MAC for user " . $u)
    :return
}

:local bind [/ip/hotspot/ip-binding/find where mac-address=$m]

:if ([:len $bind] = 0) do={
    /ip/hotspot/ip-binding/add mac-address=$m type=bypassed comment=$u
    :log info ("RADTik: mac-binding added for user " . $u . " mac=" . $m)
} else={
    :log info ("RADTik: mac-binding already exists for " . $u)
}
SCRIPT;

        return $script;
    }
}
