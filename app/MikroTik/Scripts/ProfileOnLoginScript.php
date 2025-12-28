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
{
:local u "$user";
:local m $"mac-address";

# Recovery: Get data from active list if variables are empty
:if ([:len $u] = 0) do={
    :set u [/ip hotspot active get [find where address="$address"] user];
}
:if ([:len $m] = 0 || $m = "00:00:00:00:00:00") do={
    :set m [/ip hotspot active get [find where user="$u"] mac-address];
}

/ip hotspot user {
    :local uid [find where name="$u"];
    :if ([:len $uid] > 0) do={
        :local comment [get $uid comment];
        
        # 1. Activation Logic (Check if ACT= exists)
        :local isAct [:find "$comment" "ACT="];
        
        # If isAct is empty/nil, it means ACT= not found
        :if ([:len $isAct] = 0) do={
            :local date [/system clock get date];
            :local time [/system clock get time];
            :local newTS "ACT=$date $time";
            
            set $uid comment=("$newTS | $comment");
            :log info ("RADTik: Activation Set for $u");
            # Update local comment variable to include the new ACT tag
            :set comment ("$newTS | $comment");
        }

        # 2. MAC Lock Logic
        :if ([:find "$comment" "LOCK=1"] != nil) do={
            :local smac [get $uid mac-address];
            :if ($smac = "00:00:00:00:00:00" || [:len $smac] = 0) do={
                :if ([:len $m] > 0) do={
                    set $uid mac-address=$m;
                    :log info ("RADTik: MAC $m locked to $u");
                }
            }
        }
    }
}
}
SCRIPT;
    }
}
