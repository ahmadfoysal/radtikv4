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

:if ([:len $u] = 0) do={:set u [/ip hotspot active get [find where address="$address"] user]};

/ip hotspot user {
    :local uid [find where name="$u"];
    :if ([:len $uid] > 0) do={
        :local comment [get $uid comment];
        
        # Checking for ACT= using a more robust method
        :local isAct [:find "$comment" "ACT="];

        # If isAct is truly nil, it will return nothing (len=0)
        :if ([:len $isAct] = 0) do={
            :local date [/system clock get date];
            :local time [/system clock get time];
            :local newTS "ACT=$date $time";
            
            set $uid comment=("$comment | $newTS");
            :log info ("RADTik: Activation Set for " . $u);
        } else={
            :log info ("RADTik: Skipping. ACT= found at index " . $isAct);
        };

        # MAC Lock logic
        :if ([:find "$comment" "LOCK=1"] != nil) do={
            :local smac [get $uid mac-address];
            :if ($smac = "00:00:00:00:00:00" || [:len $smac] = 0) do={
                set $uid mac-address=$m;
            };
        };
    };
};
}
SCRIPT;
    }
}
