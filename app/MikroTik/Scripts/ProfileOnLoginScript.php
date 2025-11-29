<?php

namespace App\MikroTik\Scripts;

use App\Models\Router;

class ProfileOnLoginScript
{
    public static function name(): string
    {
        return 'RADTik_Login_Logic';
    }

    public static function build(Router $router): string
    {
        return <<<'SCRIPT'
# RADTik - Profile On-Login Script
# 1. Adds "ACT=" timestamp to user comment if missing.
# 2. Checks USER comment for "LOCK=1". If found, locks MAC to user.

:local u $user
:local m $"mac-address"

# Safety check
:if ([:len $u] = 0) do={ :return }

:local uid [/ip hotspot user find name=$u]

:if ([:len $uid] = 0) do={
    :log warning ("RADTik: User not found: " . $u)
    :return
}

# --- 1. Activation Timestamp Logic ---
:local currentComment [/ip hotspot user get $uid comment]

# Check if already activated
:if ([:find $currentComment "ACT="] = nil) do={
    :local date [/system clock get date]
    :local time [/system clock get time]
    :local ts ($date . " " . $time)
    
    # Prepend activation time
    :local newComment ("ACT=" . $ts . " | " . $currentComment)
    
    /ip hotspot user set $uid comment=$newComment
    :set currentComment $newComment
    :log info ("RADTik: Activation set for " . $u)
}

# --- 2. MAC Lock Logic (Based on User Comment) ---

# Check if User comment contains "LOCK=1"
:if ([:find $currentComment "LOCK=1"] != nil) do={
    
    :local storedMac [/ip hotspot user get $uid mac-address]

    # Only lock if the MAC field is currently empty
    :if ([:len $storedMac] = 0) do={
        /ip hotspot user set $uid mac-address=$m
        :log info ("RADTik: MAC Locked for " . $u . " -> " . $m)
    }
}
SCRIPT;
    }
}
