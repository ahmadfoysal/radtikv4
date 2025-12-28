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

# If run from /system script, variables might be empty. Fetch from active list.
:if ([:len $u] = 0) do={
    :set u [/ip hotspot active get [find where address="$address"] user];
}

# Fetch MAC from active table if missing
:if ([:len $m] = 0 || $m = "00:00:00:00:00:00") do={
    :local activeId [/ip hotspot active find where user="$u"];
    :if ([:len $activeId] > 0) do={
        :set m [/ip hotspot active get $activeId mac-address];
    }
}

/ip hotspot user {
    :local uid [find where name="$u"];
    :if ([:len $uid] > 0) do={
        :local comment [get $uid comment];
        
        # 1. Activation Timestamp
        :if ([:find "$comment" "ACT="] = nil) do={
            :local date [/system clock get date];
            :local time [/system clock get time];
            set $uid comment=("ACT=$date $time | $comment");
            :log info ("RADTik: Activation Set for " . $u);
        }

        # 2. MAC Lock Logic
        :if ([:find "$comment" "LOCK=1"] != nil) do={
            :local smac [get $uid mac-address];
            :if ($smac = "00:00:00:00:00:00" || [:len $smac] = 0) do={
                :if ([:len $m] > 0) do={
                    set $uid mac-address=$m;
                    :log info ("RADTik: MAC " . $m . " locked to " . $u);
                }
            }
        }
    }
}
}
SCRIPT;
    }
}
